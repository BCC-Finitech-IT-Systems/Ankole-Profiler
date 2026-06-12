<?php

namespace App\Livewire\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DioceseManager extends Component
{
    use WithPagination;

    public $legal_name;
    public $display_name;
    public $code;
    public $contact_email;
    public $contact_phone;
    public $city;
    public $district;
    public $description;
    public $is_active = true;
    public $editing = false;
    public $dioceseId;

    public function mount(): void
    {
        Gate::authorize('manage-dioceses');
    }

    protected function rules(): array
    {
        return [
            'legal_name' => 'required|string|max:255|unique:organizations,legal_name,' . ($this->dioceseId ?? 'NULL'),
            'display_name' => 'nullable|string|max:255',
            'code' => 'required|string|max:20|unique:organizations,code,' . ($this->dioceseId ?? 'NULL'),
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function fillSampleData(): void
    {
        $this->legal_name = 'North Ankole Diocese';
        $this->display_name = 'North Ankole Diocese';
        $this->code = 'NAD-' . rand(100, 999);
        $this->contact_email = 'office@northankole.org';
        $this->contact_phone = '+256700000000';
        $this->city = 'Mbarara';
        $this->district = 'Mbarara';
        $this->description = 'Diocese covering the northern Ankole region.';
        $this->is_active = true;
    }

    public function create()
    {
        // Creating dioceses is top-level structure work; Org Admins only
        // maintain the diocese they already administer.
        abort_unless($this->user()->hasRole('Super Admin'), 403);
        $this->validate();

        Organization::create([
            'legal_name' => $this->legal_name,
            'display_name' => $this->display_name ?: $this->legal_name,
            'code' => $this->code,
            'category' => 'diocese',
            'organization_type' => 'branch',
            'parent_organization_id' => Organization::where('is_super', true)->value('id'),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'city' => $this->city,
            'district' => $this->district,
            'country' => 'UGA',
            'country_of_registration' => 'UGA',
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_super' => false,
        ]);

        session()->flash('success', 'Diocese created successfully.');
        $this->resetFields();
    }

    public function edit($id)
    {
        $diocese = $this->findManagedDiocese($id);

        $this->editing = true;
        $this->dioceseId = $diocese->id;
        $this->legal_name = $diocese->legal_name;
        $this->display_name = $diocese->display_name;
        $this->code = $diocese->code;
        $this->contact_email = $diocese->contact_email;
        $this->contact_phone = $diocese->contact_phone;
        $this->city = $diocese->city;
        $this->district = $diocese->district;
        $this->description = $diocese->description;
        $this->is_active = (bool) $diocese->is_active;
    }

    public function update()
    {
        $diocese = $this->findManagedDiocese($this->dioceseId);
        $this->validate();

        $diocese->update([
            'legal_name' => $this->legal_name,
            'display_name' => $this->display_name ?: $this->legal_name,
            'code' => $this->code,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'city' => $this->city,
            'district' => $this->district,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Diocese updated successfully.');
        $this->resetFields();
    }

    public function toggleActive($id)
    {
        $diocese = $this->findManagedDiocese($id);
        $diocese->update(['is_active' => !$diocese->is_active]);

        session()->flash('success', $diocese->is_active ? 'Diocese activated.' : 'Diocese deactivated.');
    }

    public function resetFields()
    {
        $this->reset([
            'legal_name', 'display_name', 'code', 'contact_email', 'contact_phone',
            'city', 'district', 'description', 'editing', 'dioceseId',
        ]);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.diocese-manager', [
            'dioceses' => $this->dioceseQuery()
                ->withCount('childOrganizations')
                ->orderBy('legal_name')
                ->paginate(10),
        ]);
    }

    /**
     * Dioceses this user may manage: Super Admin sees all; Org Admins only
     * the dioceses they hold an active affiliation with.
     */
    private function dioceseQuery()
    {
        $query = Organization::where('category', 'diocese');

        if (!$this->user()->hasRole('Super Admin')) {
            $query->whereIn('id', $this->user()->managedOrganizationIds());
        }

        return $query;
    }

    private function findManagedDiocese($id): Organization
    {
        return $this->dioceseQuery()->findOrFail($id);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
