<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\PersonAffiliation;
use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public Organization $organization;
    public $showAddAdminModal = false;

    public function mount($id)
    {
        $this->organization = Organization::findOrFail($id);
    }

    public function hasAdmin()
    {
        return User::where('organization_id', $this->Organization->id)
                   ->role('Organization Admin')
                   ->exists();
    }

    public function render()
    {
        $this->organization->loadMissing(['parentOrganization', 'childOrganizations', 'sites']);

        $departments = $this->organization->departments()
            ->withCount('units')
            ->orderBy('name')
            ->get();

        $units = OrganizationUnit::where('organization_id', $this->organization->id)
            ->with('department')
            ->withCount(['personAffiliations as active_members_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $membershipSummary = PersonAffiliation::where('organization_id', $this->organization->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.organizations.show', [
            'departments' => $departments,
            'units' => $units,
            'membershipSummary' => $membershipSummary,
        ]);
    }
}
