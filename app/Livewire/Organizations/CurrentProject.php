<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use App\Models\PersonAffiliation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CurrentProject extends Component
{
    public ?Organization $organization = null;
    public int $membersCount = 0;
    public int $activeMembersCount = 0;
    public int $sitesCount = 0;

    public function mount()
    {
        $this->organization = user_current_organization();

        if (!$this->organization) {
            return;
        }

        $this->membersCount = PersonAffiliation::where('organization_id', $this->organization->id)->count();
        $this->activeMembersCount = PersonAffiliation::where('organization_id', $this->organization->id)
            ->where('status', 'active')
            ->count();
        $this->sitesCount = $this->organization->sites()->count();
    }

    public function render()
    {
        return view('livewire.organizations.current-project');
    }
}
