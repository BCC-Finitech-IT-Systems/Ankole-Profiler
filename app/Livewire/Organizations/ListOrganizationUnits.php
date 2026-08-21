<?php

namespace App\Livewire\Organizations;

use Livewire\Component;
use App\Models\OrganizationUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\PersonAffiliation;
use App\Exports\UnitMembersExport;
use Maatwebsite\Excel\Facades\Excel;


class ListOrganizationUnits extends Component {
    public $search = '';
    public $statusFilter = '';
    public $movingUnitId = null;
    public $newParentId = null;
    protected $listeners = ['selectUnit', 'editUnit', 'deleteUnit'];
    public $units;
    public $unitTree;
    public $selectedUnit = null;
    public $isMember = false;
    public $applicationStatus = null;

    // Edit unit action (stub)
    public function editUnit($unitId)
    {
        // Implement your edit logic here, e.g. open an edit modal or redirect
        // Example: $this->selectedUnit = OrganizationUnit::find($unitId);
        // $this->dispatch('open-edit-unit-modal');
    }

    public function deleteUnit($unitId)
    {
        $unit = OrganizationUnit::findOrFail($unitId);
        Gate::authorize('manage', $unit);

        $unit->delete();
        $this->updateUnits();
    }


    public function exportUnitMembers($unitId)
    {
        // This exports member PII, so it needs the same scope check as viewing
        // the unit — without it any id could be exported by any signed-in user.
        $unit = OrganizationUnit::findOrFail($unitId);
        Gate::authorize('view', $unit);

        $fileName = 'unit_members_' . $unit->code . '.xlsx';
        return Excel::download(new UnitMembersExport($unitId), $fileName);
    }

    public function startMove($unitId)
    {
        $this->movingUnitId = $unitId;
        $this->newParentId = null;
    }

    public function moveUnit()
    {
        if ($this->movingUnitId !== null) {
            $unit = OrganizationUnit::find($this->movingUnitId);
            if ($unit) {
                Gate::authorize('manage', $unit);

                $unit->parent_unit_id = $this->newParentId;
                $unit->save();
                $this->units = OrganizationUnit::where('is_active', true)->get();
                $this->unitTree = $this->buildTree($this->units);
            }
        }
        $this->movingUnitId = null;
        $this->newParentId = null;
    }

    public function mount()
    {
        $this->updateUnits();
    }

    public function updatedSearch()
    {
        $this->updateUnits();
    }

    public function updatedStatusFilter()
    {
        $this->updateUnits();
    }

    public function updateUnits()
    {
        $query = OrganizationUnit::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }
        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active' ? 1 : 0);
        } else {
            $query->where('is_active', true);
        }
        $this->units = $query->get();
        $this->unitTree = $this->buildTree($this->units);
    }

    // Build a nested tree from a flat collection
    protected function buildTree($units, $parentId = null)
    {
        $branch = [];
        foreach ($units as $unit) {
            if ($unit->parent_unit_id == $parentId) {
                $children = $this->buildTree($units, $unit->id);
                if ($children) {
                    $unit->children = $children;
                }
                $branch[] = $unit;
            }
        }
        return $branch;
    }

    public function selectUnit($unitId)
    {
        $unit = OrganizationUnit::with(['organization', 'department'])->findOrFail($unitId);
        Gate::authorize('view', $unit);

        $this->selectedUnit = $unit;
        $this->checkMembership();
        $this->dispatch('open-unit-details-drawer');
    }

    public function checkMembership()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $personId = $user?->personId();
        if (!$personId || !$this->selectedUnit) {
            $this->isMember = false;
            $this->applicationStatus = null;
            return;
        }
        $affiliation = PersonAffiliation::where('person_id', $personId)
            ->where('organization_id', $this->selectedUnit->organization_id)
            ->where('organization_unit_id', $this->selectedUnit->id)
            ->first();
        if ($affiliation) {
            $this->isMember = $affiliation->status === 'active';
            $this->applicationStatus = $affiliation->status;
        } else {
            $application = \App\Models\OrganizationUnitApplication::where('unit_id', $this->selectedUnit->id)
                ->where('person_id', $personId)
                ->latest()
                ->first();
            $this->isMember = false;
            $this->applicationStatus = $application?->status;
        }
    }

    public function applyToJoin()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || !$this->selectedUnit) return;

        $personId = $user->personId();
        if (!$personId) {
            session()->flash('error', 'Your account has no person profile yet. Please complete your profile before applying.');
            return;
        }

        $application = \App\Models\OrganizationUnitApplication::firstOrCreate(
            [
                'organization_id' => $this->selectedUnit->organization_id,
                'unit_id' => $this->selectedUnit->id,
                'person_id' => $personId,
            ],
            ['status' => 'pending']
        );
        $this->applicationStatus = $application->status;

        if (!$application->wasRecentlyCreated) {
            return;
        }

        // Notify admins who hold an active affiliation with this organization
        $admins = \App\Models\User::role('Organization Admin')
            ->whereHas('person.affiliations', function ($q) {
                $q->where('organization_id', $this->selectedUnit->organization_id)->active();
            })
            ->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewUnitApplicationSubmitted($application));
        }
    }

    public function render()
    {
        return view('livewire.organizations.list-organization-units');
    }
}
