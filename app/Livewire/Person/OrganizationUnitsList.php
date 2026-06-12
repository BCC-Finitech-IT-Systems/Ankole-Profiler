<?php

namespace App\Livewire\Person;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Helpers\OrganizationHelper;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;

class OrganizationUnitsList extends Component
{
    public $units;

    public function mount()
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        $this->units = $organization
            ? OrganizationUnit::where('organization_id', $organization->id)->get()
            : collect();
    }

    public function applyToJoin($unitId)
    {
        $user = Auth::user();

        if (!$user || !$user->person) {
            session()->flash('error', 'You must have a person profile to apply.');
            return;
        }

        $unit = $this->units->firstWhere('id', (int) $unitId);

        if (!$unit) {
            session()->flash('error', 'Unit not found in your organization.');
            return;
        }

        $alreadyExists = OrganizationUnitApplication::where('organization_id', $unit->organization_id)
            ->where('unit_id', $unit->id)
            ->where('person_id', $user->person->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyExists) {
            session()->flash('error', 'You have already applied to this unit.');
            return;
        }

        OrganizationUnitApplication::create([
            'organization_id' => $unit->organization_id,
            'unit_id'         => $unit->id,
            'person_id'       => $user->person->id,
            'status'          => 'pending',
        ]);

        session()->flash('message', 'Application to join unit submitted! Await admin approval.');
    }

    public function render()
    {
        return view('livewire.person.organisation-units-list', [
            'units' => $this->units,
        ]);
    }
}
