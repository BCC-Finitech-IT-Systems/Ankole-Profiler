<?php

namespace App\Livewire\Organizations;

use App\Models\OrganizationUnitApplication;
use App\Models\PersonAffiliation;
use App\Models\UnitPersonRole;
use App\Models\User;
use App\Services\UnitRoleAssigner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReviewUnitApplications extends Component
{
    public $selectedApplication = null;
    public $selectedIds = [];
    public $selectAll = false;

    /**
     * The pending applications this user is allowed to act on. Shared by
     * render() and the select-all handler so both always agree on the set.
     */
    protected function pendingApplicationsQuery()
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = OrganizationUnitApplication::pending()->with(['person', 'unit']);

        if ($user && !$user->hasRole('Super Admin')) {
            $query->whereIn('organization_id', $user->managedOrganizationIds());
        }

        return $query;
    }

    public function updatedSelectAll($value): void
    {
        $this->selectedIds = $value
            ? $this->pendingApplicationsQuery()->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function updatedSelectedIds(): void
    {
        $total = $this->pendingApplicationsQuery()->count();

        $this->selectAll = $total > 0 && count($this->selectedIds) === $total;
    }

    public function selectApplication($id)
    {
        $this->selectedApplication = OrganizationUnitApplication::find($id);
    }

    public function approve($id)
    {
        $application = OrganizationUnitApplication::where('status', 'pending')->findOrFail($id);
        Gate::authorize('approve', $application);

        /** @var User $user */
        $user = Auth::user();

        DB::transaction(function () use ($application, $user) {
            $this->createMembership($application, $user);
            $application->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);
        });

        $this->selectedApplication = null;
    }

    public function reject($id)
    {
        $application = OrganizationUnitApplication::where('status', 'pending')->findOrFail($id);
        Gate::authorize('approve', $application);

        $application->update(['status' => 'rejected']);
        $this->selectedApplication = null;
    }

    public function bulkApprove()
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && $user->can('bulk-approve-unit-membership'), 403);

        $managedOrgIds = $user->managedOrganizationIds();

        $applications = OrganizationUnitApplication::whereIn('id', $this->selectedIds)
            ->pending()
            ->whereIn('organization_id', $managedOrgIds)
            ->get();

        DB::transaction(function () use ($applications, $user) {
            foreach ($applications as $application) {
                if (!Gate::check('approve', $application)) {
                    continue;
                }
                $this->createMembership($application, $user);
                $application->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);
            }
        });

        $this->selectedIds = [];
        $this->selectAll   = false;
    }

    public function bulkReject()
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && $user->can('bulk-approve-unit-membership'), 403);

        $managedOrgIds = $user->managedOrganizationIds();

        $ids = OrganizationUnitApplication::whereIn('id', $this->selectedIds)
            ->pending()
            ->whereIn('organization_id', $managedOrgIds)
            ->pluck('id');

        OrganizationUnitApplication::whereIn('id', $ids)->update(['status' => 'rejected']);

        $this->selectedIds = [];
        $this->selectAll   = false;
    }

    /**
     * Create PersonAffiliation + UnitPersonRole when an application is approved.
     */
    private function createMembership(OrganizationUnitApplication $application, User $user): void
    {
        $unit = $application->unit;
        $roleType = $unit ? (new UnitRoleAssigner())->memberRoleTypeFor($unit) : null;

        $affiliation = PersonAffiliation::firstOrCreate(
            [
                'person_id'            => $application->person_id,
                'organization_id'      => $application->organization_id,
                'organization_unit_id' => $application->unit_id,
            ],
            [
                'role_type'   => $roleType?->code ?? 'MEMBER',
                'status'      => 'active',
                'start_date'  => now(),
                'permissions' => ['view'],
                'created_by'  => $user->id,
            ]
        );

        if (!$affiliation->wasRecentlyCreated) {
            $affiliation->update(['status' => 'active', 'updated_by' => $user->id]);
        }

        UnitPersonRole::firstOrCreate(
            [
                'unit_id'      => $application->unit_id,
                'person_id'    => $application->person_id,
                'role_type_id' => $roleType?->id,
            ],
            [
                'role'        => $roleType ? strtolower($roleType->code) : 'member',
                'can_view'    => true,
                'can_edit'    => false,
                'can_approve' => false,
                'granted_by'  => $user->id,
                'granted_at'  => now(),
            ]
        );
    }

    public function render()
    {
        return view('livewire.organizations.review-unit-applications', [
            'applications' => $this->pendingApplicationsQuery()->get(),
        ]);
    }
}
