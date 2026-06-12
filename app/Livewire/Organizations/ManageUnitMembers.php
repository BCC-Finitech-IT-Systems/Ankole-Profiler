<?php

namespace App\Livewire\Organizations;

use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\RoleType;
use App\Models\UnitPersonRole;
use App\Models\User;
use App\Services\UnitRoleAssigner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ManageUnitMembers extends Component
{
    public int $unitId;
    public ?OrganizationUnit $unit = null;

    // Add-member form
    public bool $showAddForm = false;
    public string $personSearch = '';
    public ?int $selectedPersonId = null;
    public string $selectedPersonName = '';
    public ?int $newRoleTypeId = null;
    public bool $newCanView = true;
    public bool $newCanEdit = false;
    public bool $newCanApprove = false;

    // Confirm revoke
    public ?int $confirmRevokeId = null;

    // Search results for person picker
    public array $personResults = [];

    protected $listeners = ['unitSelected' => 'loadUnit'];

    public function mount(int $unitId): void
    {
        $this->unitId = $unitId;
        $this->unit = OrganizationUnit::find($unitId);

        Gate::authorize('manage', $this->unit);
    }

    public function loadUnit(int $unitId): void
    {
        $this->unitId = $unitId;
        $this->unit = OrganizationUnit::find($unitId);

        Gate::authorize('manage', $this->unit);

        $this->resetAddForm();
    }

    public function updatedPersonSearch(): void
    {
        if (strlen($this->personSearch) < 2) {
            $this->personResults = [];
            return;
        }

        $this->personResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->personSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->personSearch}%")
                  ->orWhere('person_id', 'like', "%{$this->personSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name', 'person_id'])
            ->toArray();
    }

    public function selectPerson(int $personId, string $name): void
    {
        $this->selectedPersonId = $personId;
        $this->selectedPersonName = $name;
        $this->personSearch = $name;
        $this->personResults = [];
    }

    public function addMember(): void
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        Gate::authorize('manage', $this->unit);

        $availableIds = $this->availableRoleTypes()->pluck('id')->toArray();

        $this->validate([
            'selectedPersonId' => 'required|exists:persons,id',
            'newRoleTypeId'    => ['required', 'integer', 'in:' . implode(',', $availableIds)],
            'newCanView'       => 'boolean',
            'newCanEdit'       => 'boolean',
            'newCanApprove'    => 'boolean',
        ]);

        $roleType = RoleType::findOrFail($this->newRoleTypeId);

        // Defense in depth: confirm the chosen RoleType belongs to this unit's department.
        $assigner = new UnitRoleAssigner();
        abort_unless($assigner->isValidForUnit($roleType, $this->unit), 422);

        DB::transaction(function () use ($authUser, $roleType) {
            UnitPersonRole::firstOrCreate(
                [
                    'unit_id'      => $this->unitId,
                    'person_id'    => $this->selectedPersonId,
                    'role_type_id' => $roleType->id,
                ],
                [
                    'role'        => strtolower($roleType->code),
                    'can_view'    => $this->newCanView,
                    'can_edit'    => $this->newCanEdit,
                    'can_approve' => $this->newCanApprove,
                    'granted_by'  => $authUser->id,
                    'granted_at'  => now(),
                ]
            );

            PersonAffiliation::firstOrCreate(
                [
                    'person_id'            => $this->selectedPersonId,
                    'organization_id'      => $this->unit->organization_id,
                    'organization_unit_id' => $this->unitId,
                ],
                [
                    'role_type'   => $roleType->code,
                    'status'      => 'active',
                    'start_date'  => now(),
                    'permissions' => array_values(array_filter([
                        $this->newCanView    ? 'view'    : null,
                        $this->newCanEdit    ? 'edit'    : null,
                        $this->newCanApprove ? 'approve' : null,
                    ])),
                    'created_by' => $authUser->id,
                ]
            );
        });

        $this->resetAddForm();
        $this->dispatch('memberAdded');
    }

    public function confirmRevoke(int $roleId): void
    {
        $this->confirmRevokeId = $roleId;
    }

    public function cancelRevoke(): void
    {
        $this->confirmRevokeId = null;
    }

    public function revokeRole(): void
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        Gate::authorize('manage', $this->unit);

        $role = UnitPersonRole::find($this->confirmRevokeId);
        if ($role && $role->unit_id === $this->unitId) {
            $role->update([
                'revoked_at' => now(),
                'revoked_by' => $authUser->id,
            ]);
        }

        $this->confirmRevokeId = null;
        $this->dispatch('memberRevoked');
    }

    public function updatePermissions(int $roleId, string $permission, bool $value): void
    {
        Gate::authorize('manage', $this->unit);

        $role = UnitPersonRole::find($roleId);
        if ($role && $role->unit_id === $this->unitId) {
            $role->update(["can_{$permission}" => $value]);
        }
    }

    private function availableRoleTypes()
    {
        if (!$this->unit) {
            return collect();
        }

        $query = RoleType::active();

        if ($this->unit->department_id) {
            $query->forDepartment($this->unit->department_id);
        } else {
            $query->whereNull('department_id');
        }

        return $query->orderBy('name')->get();
    }

    private function resetAddForm(): void
    {
        $this->showAddForm      = false;
        $this->personSearch     = '';
        $this->selectedPersonId = null;
        $this->selectedPersonName = '';
        $this->newRoleTypeId    = null;
        $this->newCanView       = true;
        $this->newCanEdit       = false;
        $this->newCanApprove    = false;
        $this->personResults    = [];
    }

    public function render()
    {
        $members = UnitPersonRole::with(['person', 'roleType'])
            ->where('unit_id', $this->unitId)
            ->whereNull('revoked_at')
            ->orderBy('role')
            ->get();

        return view('livewire.organizations.manage-unit-members', [
            'members'           => $members,
            'availableRoleTypes' => $this->availableRoleTypes(),
        ]);
    }
}
