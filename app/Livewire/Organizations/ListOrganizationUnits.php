<?php

namespace App\Livewire\Organizations;

use Livewire\Component;
use App\Models\OrganizationUnit;
use Illuminate\Support\Facades\Auth;
use App\Models\PersonAffiliation;
use App\Exports\UnitMembersExport;
use Illuminate\Support\Facades\DB;
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

    // Delete unit action (stub)
    public function deleteUnit($unitId)
    {
        // Implement your delete logic here, e.g. show confirmation and delete
        $unit = OrganizationUnit::find($unitId);
        if ($unit) {
            $unit->delete();
            $this->updateUnits();
        }
    }


    public function exportUnitMembers($unitId)
    {
        $unit = OrganizationUnit::findOrFail($unitId);
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
        $this->selectedUnit = OrganizationUnit::with(['organization', 'department'])->find($unitId);
        $this->checkMembership();
        $this->dispatch('open-unit-details-drawer');
    }

    public function checkMembership()
    {
        $user = Auth::user();
        if (!$user || !$this->selectedUnit) {
            $this->isMember = false;
            $this->applicationStatus = null;
            return;
        }
        $affiliation = PersonAffiliation::where('person_id', $user->id)
            ->where('organization_id', $this->selectedUnit->organization_id)
            ->where('domain_record_type', 'unit')
            ->where('domain_record_id', $this->selectedUnit->id)
            ->first();
        if ($affiliation) {
            $this->isMember = in_array($affiliation->status, ['active', 'approved']);
            $this->applicationStatus = $affiliation->status;
        } else {
            $this->isMember = false;
            $this->applicationStatus = null;
        }
    }

    public function applyToJoin()
    {
        $user = Auth::user();
        if (!$user || !$this->selectedUnit) return;

        // Check for existing pending application
        $existing = DB::table('organization_unit_applications')
            ->where('organization_id', $this->selectedUnit->organization_id)
            ->where('unit_id', $this->selectedUnit->id)
            ->where('person_id', $user->id)
            ->where('status', 'pending')
            ->first();
        if ($existing) {
            $this->applicationStatus = 'pending';
            return;
        }

        // Create new application
        $appId = DB::table('organization_unit_applications')->insertGetId([
            'organization_id' => $this->selectedUnit->organization_id,
            'unit_id' => $this->selectedUnit->id,
            'person_id' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->applicationStatus = 'pending';

        // Notify all admins of the organization (optional: you may want to update notification logic to use the new application model)
        $admins = \App\Models\User::where('organization_id', $this->selectedUnit->organization_id)
            ->role('Organization Admin')
            ->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewUnitApplicationSubmitted((object)[
                'person' => $user->person,
                'organizationUnit' => $this->selectedUnit,
            ]));
        }
    }

    public function render()
    {
        return view('livewire.organizations.list-organization-units');
    }
}
