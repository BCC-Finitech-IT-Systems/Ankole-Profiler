<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrganizationSwitcher extends Component
{
    public $currentOrganizationId;
    public $currentOrganizationName;
    public $currentOrganizationLogo;
    public $availableOrganizations = [];
    public $isOpen = false;
    public $searchTerm = '';
    public $userRole = 'User';
    public $canSwitchOrganizations = false;

    protected $listeners = [
        'organizationSwitched' => '$refresh',
        'refreshOrganizations' => 'loadAvailableOrganizations',
    ];

    public function mount()
    {
        $this->loadCurrentOrganization();
        $this->loadAvailableOrganizations();
        $this->checkSwitchPermission();
    }

    public function loadCurrentOrganization()
    {
        $this->currentOrganizationId = session('current_organization_id');
        $this->currentOrganizationName = session('current_organization_name', 'Select Organization');
        $this->currentOrganizationLogo = session('current_organization_logo');
    }

    public function loadAvailableOrganizations()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            $this->availableOrganizations = [];
            return;
        }

        $organizations = $user->accessibleOrganizations();

        if (!empty($this->searchTerm)) {
            $searchLower = strtolower($this->searchTerm);
            $organizations = $organizations->filter(function ($org) use ($searchLower) {
                return str_contains(strtolower($org->display_name), $searchLower)
                    || str_contains(strtolower($org->code), $searchLower);
            });
        }

        $this->availableOrganizations = $organizations->map(function ($org) use ($user) {
            return [
                'id' => $org->id,
                'code' => $org->code,
                'display_name' => $org->display_name,
                'legal_name' => $org->legal_name,
                'logo_path' => $org->logo_path,
                'category' => $org->category,
                'user_role' => $user->getRoleInOrganization($org->id)?->name ?? 'Admin',
                'is_primary' => $org->id === (int) $this->currentOrganizationId,
                'site_count' => $org->sites()->count(),
            ];
        })->values()->toArray();
    }

    public function checkSwitchPermission()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            $this->canSwitchOrganizations = false;
            $this->userRole = 'Guest';
            return;
        }

        if ($user->hasRole('Super Admin')) {
            $this->canSwitchOrganizations = true;
            $this->userRole = 'Super Admin';
            return;
        }

        if (empty($this->availableOrganizations)) {
            $this->loadAvailableOrganizations();
        }

        $this->canSwitchOrganizations = count($this->availableOrganizations) > 1;
        $this->userRole = $user->getRoleInOrganization($this->currentOrganizationId)?->name ?? 'User';
    }

    public function switchOrganization($organizationId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canAccessOrganization($organizationId)) {
            session()->flash('error', 'You do not have access to this organization.');
            return;
        }

        $organization = Organization::find($organizationId);

        if (!$organization) {
            session()->flash('error', 'Organization not found.');
            return;
        }

        session([
            'current_organization_id' => $organization->id,
            'current_organization_name' => $organization->display_name,
            'current_organization_code' => $organization->code,
            'current_organization_logo' => $organization->logo_path,
        ]);

        $this->currentOrganizationId = $organization->id;
        $this->currentOrganizationName = $organization->display_name;
        $this->currentOrganizationLogo = $organization->logo_path;
        $this->isOpen = false;

        $this->emit('organizationSwitched', $organization->id);

        Log::info('Organization context switched', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'organization_name' => $organization->display_name,
        ]);

        session()->flash('message', "Switched to {$organization->display_name}");

        return redirect()->route('dashboard');
    }

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;

        if (!$this->isOpen) {
            $this->searchTerm = '';
            $this->loadAvailableOrganizations();
        }
    }

    public function updatedSearchTerm()
    {
        $this->loadAvailableOrganizations();
    }

    public function closeDropdown()
    {
        $this->isOpen = false;
        $this->searchTerm = '';
        $this->loadAvailableOrganizations();
    }

    public function render()
    {
        return view('livewire.organization-switcher');
    }
}
