<?php

namespace App\Livewire\Organizations;

use Livewire\Component;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;
use App\Models\PersonAffiliation;
use Illuminate\Support\Facades\Auth;

class ApplyToUnit extends Component
{
    public $unit;
    public $isMember = false;
    public $hasPendingApplication = false;

    public function mount($unitId)
    {
        $this->unit = OrganizationUnit::findOrFail($unitId);

        $user = Auth::user();
        if ($user && $user->person) {
            $affiliation = PersonAffiliation::where('person_id', $user->person->id)
                ->where('domain_record_type', 'unit')
                ->where('domain_record_id', $this->unit->id)
                ->first();

            $this->isMember = $affiliation && $affiliation->status === 'active';
            $this->hasPendingApplication = $affiliation && $affiliation->status === 'inactive';
        }
    }

    public function apply()
    {
        $user = Auth::user();
        if (!$user || !$user->person) {
            session()->flash('error', 'You must be logged in as a person to apply.');
            return;
        }

        $personId = $user->person->id;

        $alreadyExists = OrganizationUnitApplication::where('organization_id', $this->unit->organization_id)
            ->where('unit_id', $this->unit->id)
            ->where('person_id', $personId)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyExists) {
            session()->flash('error', 'You have already applied to this unit.');
            return;
        }

        OrganizationUnitApplication::create([
            'organization_id' => $this->unit->organization_id,
            'unit_id'         => $this->unit->id,
            'person_id'       => $personId,
            'status'          => 'pending',
        ]);

        $this->hasPendingApplication = true;
        session()->flash('success', 'Application submitted! Await admin approval.');
    }

    public function render()
    {
        return view('livewire.organizations.apply-to-unit');
    }
}
