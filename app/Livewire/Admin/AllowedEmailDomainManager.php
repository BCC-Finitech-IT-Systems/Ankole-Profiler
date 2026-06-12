<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use App\Models\AllowedEmailDomain;
use App\Models\Organization;
use Livewire\WithPagination;

class AllowedEmailDomainManager extends Component
{
    use WithPagination;

    public $domain;
    public $organization_id;
    public $is_active = true;
    public $editing = false;
    public $domainId;
    public $filteredOrganizations = [];
    public $organizationSearch = '';

    public function mount(): void
    {
        Gate::authorize('manage-email-domains');
    }

    protected $rules = [
        'domain' => 'required|string|unique:allowed_email_domains,domain',
        'organization_id' => 'nullable|exists:organizations,id',
        'is_active' => 'boolean',
    ];

    public function fillSampleData(): void
    {
        $org = Organization::query()->first();
        $this->domain          = '@ankole.org';
        $this->organization_id = $org?->id;
        $this->is_active       = true;
    }

    public function create()
    {
        Gate::authorize('manage-email-domains');
        $this->validate();

        AllowedEmailDomain::create([
            'domain' => $this->domain,
            'organization_id' => $this->organization_id,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Allowed email domain created successfully.');
        $this->resetFields();
    }

    public function edit($id)
    {
        $this->editing = true;
        $domain = AllowedEmailDomain::findOrFail($id);
        $this->domainId = $domain->id;
        $this->domain = $domain->domain;
        $this->organization_id = $domain->organization_id;
        $this->is_active = $domain->is_active;
    }

    public function update()
    {
        Gate::authorize('manage-email-domains');
        $this->validate();

        $domain = AllowedEmailDomain::findOrFail($this->domainId);
        $domain->update([
            'domain' => $this->domain,
            'organization_id' => $this->organization_id,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Allowed email domain updated successfully.');
        $this->resetFields();
    }

    public function delete($id)
    {
        Gate::authorize('manage-email-domains');
        AllowedEmailDomain::findOrFail($id)->delete();
        session()->flash('success', 'Allowed email domain deleted successfully.');
    }

    private function resetFields()
    {
        $this->domain = '';
        $this->organization_id = null;
        $this->is_active = true;
        $this->editing = false;
        $this->domainId = null;
    }

    public function render()
    {
        return view('livewire.admin.allowed-email-domain-manager', [
            'domains' => AllowedEmailDomain::paginate(10),
            'organizations' => Organization::all(),
        ]);
    }

    public function getFilteredOrganizationsProperty()
    {
        return \App\Models\Organization::where('name', 'like', '%' . $this->organizationSearch . '%')->get();
    }
}
