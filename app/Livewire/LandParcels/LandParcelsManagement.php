<?php

namespace App\Livewire\LandParcels;

use App\Models\Department;
use App\Models\LandParcel;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LandParcelsManagement extends Component
{
    use WithPagination;

    public string $stageFilter = '';
    public ?int $departmentFilter = null;
    public string $districtFilter = '';
    public bool $disputedOnly = false;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $organization_id = null;
    public ?int $department_id = null;
    public string $reference_number = '';
    public string $property_name = '';
    public string $location = '';
    public string $district = '';
    public string $sub_county = '';
    public string $parish = '';
    public string $village = '';
    public string $acreage = '';
    public string $tenure_type = '';
    public string $current_use = '';
    public string $acquisition_date = '';
    public string $acquisition_details = '';

    protected $queryString = [
        'stageFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
        'districtFilter' => ['except' => ''],
    ];

    public function updatingStageFilter(): void { $this->resetPage(); }
    public function updatingDepartmentFilter(): void { $this->resetPage(); }
    public function updatingDistrictFilter(): void { $this->resetPage(); }
    public function updatingDisputedOnly(): void { $this->resetPage(); }

    public function updatedOrganizationId(): void
    {
        $this->department_id = null;
    }

    public function create(): void
    {
        $this->authorize('create', LandParcel::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $parcel = LandParcel::findOrFail($id);
        $this->authorize('update', $parcel);

        $this->editingId = $id;
        $this->organization_id = $parcel->organization_id;
        $this->department_id = $parcel->department_id;
        $this->reference_number = $parcel->reference_number;
        $this->property_name = $parcel->property_name;
        $this->location = $parcel->location ?? '';
        $this->district = $parcel->district ?? '';
        $this->sub_county = $parcel->sub_county ?? '';
        $this->parish = $parcel->parish ?? '';
        $this->village = $parcel->village ?? '';
        $this->acreage = (string) ($parcel->acreage ?? '');
        $this->tenure_type = $parcel->tenure_type ?? '';
        $this->current_use = $parcel->current_use ?? '';
        $this->acquisition_date = $parcel->acquisition_date?->format('Y-m-d') ?? '';
        $this->acquisition_details = $parcel->acquisition_details ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->organization_id = null;
        $this->department_id = null;
        $this->reference_number = '';
        $this->property_name = '';
        $this->location = '';
        $this->district = '';
        $this->sub_county = '';
        $this->parish = '';
        $this->village = '';
        $this->acreage = '';
        $this->tenure_type = '';
        $this->current_use = '';
        $this->acquisition_date = '';
        $this->acquisition_details = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $user = Auth::user();
        $parcel = $this->editingId ? LandParcel::findOrFail($this->editingId) : null;

        $this->authorize($parcel ? 'update' : 'create', $parcel ?? LandParcel::class);

        $allowedOrgIds = $this->organizationOptions()->pluck('id');

        $this->validate([
            'organization_id' => ['required', Rule::in($allowedOrgIds)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('organization_id', $this->organization_id)],
            'reference_number' => [
                'required', 'string', 'max:100',
                Rule::unique('land_parcels', 'reference_number')
                    ->where('organization_id', $this->organization_id)
                    ->ignore($this->editingId),
            ],
            'property_name' => 'required|string|max:255',
            'acreage' => 'nullable|numeric|min:0',
            'acquisition_date' => 'nullable|date',
        ]);

        $data = [
            'organization_id' => $this->organization_id,
            'department_id' => $this->department_id,
            'reference_number' => $this->reference_number,
            'property_name' => $this->property_name,
            'location' => $this->location ?: null,
            'district' => $this->district ?: null,
            'sub_county' => $this->sub_county ?: null,
            'parish' => $this->parish ?: null,
            'village' => $this->village ?: null,
            'acreage' => $this->acreage !== '' ? $this->acreage : null,
            'tenure_type' => $this->tenure_type ?: null,
            'current_use' => $this->current_use ?: null,
            'acquisition_date' => $this->acquisition_date ?: null,
            'acquisition_details' => $this->acquisition_details ?: null,
            'updated_by' => $user->id,
        ];

        if ($parcel) {
            $parcel->update($data);
            AuditLogger::record($parcel, 'land_parcel.updated', [], $parcel->organization_id);
            session()->flash('success', 'Land parcel updated.');
        } else {
            $data['created_by'] = $user->id;
            $data['stage'] = 'unregistered';
            $parcel = LandParcel::create($data);
            AuditLogger::record($parcel, 'land_parcel.created', [], $parcel->organization_id);
            session()->flash('success', 'Land parcel registered.');
        }

        $this->closeModal();
    }

    private function organizationOptions()
    {
        $user = Auth::user();
        $directIds = $user->managedOrganizationIds();
        $viaDepartments = Department::whereIn('id', $user->managedDepartmentIds())->pluck('organization_id');

        return Organization::whereIn('id', $directIds->merge($viaDepartments)->unique())->orderBy('display_name')->get(['id', 'display_name', 'legal_name']);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-land-parcels'), 403);

        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        $query = LandParcel::with(['department', 'organization', 'responsiblePerson'])
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->stageFilter, fn ($q) => $q->where('stage', $this->stageFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->districtFilter, fn ($q) => $q->where('district', 'like', "%{$this->districtFilter}%"))
            ->when($this->disputedOnly, fn ($q) => $q->where('stage', 'disputed'))
            ->orderByDesc('created_at');

        return view('livewire.land-parcels.land-parcels-management', [
            'parcels' => $query->paginate(15),
            'organizations' => $this->organizationOptions(),
            'departments' => Department::whereIn('id', $deptIds)->orWhereIn('organization_id', $orgIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
