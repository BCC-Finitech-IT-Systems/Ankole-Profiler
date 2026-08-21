<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component {
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $statusFilter = '';
    public $categoryFilter = '';
    public $parentOrganizationFilter = '';
    public $districtFilter = '';
    public $organizationTypeFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
        'statusFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'parentOrganizationFilter' => ['except' => ''],
        'districtFilter' => ['except' => ''],
        'organizationTypeFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingParentOrganizationFilter()
    {
        $this->resetPage();
    }

    public function updatingDistrictFilter()
    {
        $this->resetPage();
    }

    public function updatingOrganizationTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->categoryFilter = '';
        $this->parentOrganizationFilter = '';
        $this->districtFilter = '';
        $this->organizationTypeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sortField = 'legal_name';
        $this->sortDirection = 'asc';
        $this->perPage = 10;
        $this->resetPage();
    }

    protected function accessibleOrganizationIds()
    {
        return auth()->user()->accessibleOrganizations()->pluck('id');
    }

    protected function filterParams(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'category' => $this->categoryFilter,
            'parent_organization_id' => $this->parentOrganizationFilter,
            'district' => $this->districtFilter,
            'organization_type' => $this->organizationTypeFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    protected function buildOrganizationsQuery()
    {
        return Organization::query()
            ->whereIn('id', $this->accessibleOrganizationIds())
            ->with('parentOrganization')
            ->withCount([
                'departments',
                'organizationUnits',
                'sites',
                'personAffiliations as active_members_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->filterForList($this->filterParams())
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function getOrganizationsProperty()
    {
        return $this->buildOrganizationsQuery()->paginate($this->perPage);
    }

    public function getCategoriesProperty()
    {
        return [
            'hospital' => 'Hospital/Health Facility',
            'school' => 'School/Educational Institution',
            'sacco' => 'SACCO/Financial Cooperative',
            'parish' => 'Parish/Religious Organization',
            'corporate' => 'Corporate/Business',
            'government' => 'Government Agency',
            'ngo' => 'NGO/Non-Profit',
            'other' => 'Other'
        ];
    }

    public function getParentOrganizationOptionsProperty()
    {
        $accessibleIds = $this->accessibleOrganizationIds();

        $parentIds = Organization::whereIn('id', $accessibleIds)
            ->whereNotNull('parent_organization_id')
            ->pluck('parent_organization_id')
            ->unique();

        return Organization::whereIn('id', $parentIds)
            ->orderBy('legal_name')
            ->pluck('legal_name', 'id');
    }

    public function getDistrictOptionsProperty()
    {
        return Organization::whereIn('id', $this->accessibleOrganizationIds())
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');
    }

    public function getOrganizationTypeOptionsProperty()
    {
        return Organization::whereIn('id', $this->accessibleOrganizationIds())
            ->whereNotNull('organization_type')
            ->where('organization_type', '!=', '')
            ->distinct()
            ->orderBy('organization_type')
            ->pluck('organization_type');
    }

    public $confirmingDeleteId = null;


    public function confirmDelete($id)
    {
        // Authorise on open as well as on submit: confirmingDeleteId is a public
        // property, so a client can set it directly without ever calling this.
        $organization = $this->findAccessibleOrganization($id);
        Gate::authorize('delete', $organization);

        $this->confirmingDeleteId = $organization->id;
    }


    public function deleteOrganization()
    {
        $organization = $this->findAccessibleOrganization($this->confirmingDeleteId);
        Gate::authorize('delete', $organization);

        $organization->delete();

        session()->flash('message', 'Organization deleted successfully.');
        $this->confirmingDeleteId = null;
    }

    /**
     * Resolve an organization the current user can actually reach. Scoping the
     * lookup as well as running the policy means an id outside the user's scope
     * 404s rather than reaching the policy at all.
     */
    protected function findAccessibleOrganization($id): Organization
    {
        return Organization::whereIn('id', $this->accessibleOrganizationIds())
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.organizations.index', [
            'organizations' => $this->organizations,
            'categories' => $this->categories,
            'parentOrganizationOptions' => $this->parentOrganizationOptions,
            'districtOptions' => $this->districtOptions,
            'organizationTypeOptions' => $this->organizationTypeOptions,
        ]);
    }
}
