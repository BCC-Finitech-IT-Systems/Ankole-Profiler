<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Person;
use App\Models\PersonAffiliation;

class StatsCards extends Component
{
    public $totalPersons;
    public $activeAffiliations;
    public $newThisMonth;
    public $pendingVerification;

    // Listen for organization switch
    protected $listeners = [
        'organizationSwitched' => 'refreshStats'
    ];

    public function mount()
    {
        $this->refreshStats();
    }

    public function refreshStats()
    {
        $orgId = current_organization_id();
        
        // Total persons in current organization
        $this->totalPersons = Person::whereHas('affiliations', function($query) use ($orgId) {
            $query->where('organization_id', $orgId);
        })->count();
        
        // Active affiliations
        $this->activeAffiliations = PersonAffiliation::where('organization_id', $orgId)
                                                    ->where('status', 'active')
                                                    ->count();
        
        // New this month
        $this->newThisMonth = PersonAffiliation::where('organization_id', $orgId)
                                              ->whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->count();
        
        // Pending verification
        $this->pendingVerification = Person::whereHas('affiliations', function($query) use ($orgId) {
            $query->where('organization_id', $orgId)
                  ->where('status', 'pending');
        })->count();
    }
    public function render()
    {
        return view('livewire.stats-cards');
    }
}
