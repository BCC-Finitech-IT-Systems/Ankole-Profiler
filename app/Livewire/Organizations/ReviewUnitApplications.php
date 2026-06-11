<?php

namespace App\Livewire\Organizations;

use App\Models\OrganizationUnitApplication;
use App\Models\PersonAffiliation;
use App\Models\UnitPersonRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReviewUnitApplications extends Component
{
    public $selectedApplication = null;
    public $selectedIds = [];

    public function selectApplication($id)
    {
        $this->selectedApplication = OrganizationUnitApplication::find($id);
    }

    public function approve($id)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !Gate::allows('approve-unit-membership')) {
            $user ? redirect()->route('dashboard') : abort(403);
        }

        $application = OrganizationUnitApplication::find($id);
        if (!$application || $application->status !== 'pending') {
            return;
        }

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
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !Gate::allows('approve-unit-membership')) {
            $user ? redirect()->route('dashboard') : abort(403);
        }

        $application = OrganizationUnitApplication::find($id);
        if ($application && $application->status === 'pending') {
            $application->update(['status' => 'rejected']);
        }

        $this->selectedApplication = null;
    }

    public function bulkApprove()
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user && Gate::allows('bulk-approve-unit-membership'), 403);

        $applications = OrganizationUnitApplication::whereIn('id', $this->selectedIds)
            ->pending()
            ->get();

        DB::transaction(function () use ($applications, $user) {
            foreach ($applications as $application) {
                $this->createMembership($application, $user);
                $application->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);
            }
        });

        $this->selectedIds = [];
    }

    public function bulkReject()
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user && Gate::allows('bulk-approve-unit-membership'), 403);

        OrganizationUnitApplication::whereIn('id', $this->selectedIds)
            ->pending()
            ->update(['status' => 'rejected']);

        $this->selectedIds = [];
    }

    /**
     * Create PersonAffiliation + UnitPersonRole when an application is approved.
     * Uses the direct organization_unit_id FK (not the old domain_record_id pattern).
     */
    private function createMembership(object $application, object $user): void
    {
        // Upsert: if person already has an affiliation for this org+unit, reactivate it
        $affiliation = PersonAffiliation::firstOrCreate(
            [
                'person_id'            => $application->person_id,
                'organization_id'      => $application->organization_id,
                'organization_unit_id' => $application->unit_id,
            ],
            [
                'role_type'   => 'MEMBER',
                'status'      => 'active',
                'start_date'  => now(),
                'permissions' => ['view'],
                'created_by'  => $user->id,
            ]
        );

        if (!$affiliation->wasRecentlyCreated) {
            $affiliation->update(['status' => 'active', 'updated_by' => $user->id]);
        }

        // Grant a default 'member' role in the unit_person_roles table
        UnitPersonRole::firstOrCreate(
            [
                'unit_id'   => $application->unit_id,
                'person_id' => $application->person_id,
                'role'      => 'member',
            ],
            [
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
        /** @var User|null $user */
        $user = Auth::user();

        $query = OrganizationUnitApplication::pending()
            ->with(['person', 'unit'])
            ->when($user?->organization_id, fn ($q, $orgId) => $q->forOrganization($orgId));

        return view('livewire.organizations.review-unit-applications', [
            'applications' => $query->get(),
        ]);
    }
}
