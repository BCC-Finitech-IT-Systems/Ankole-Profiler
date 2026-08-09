<?php

namespace App\Livewire\LandParcels;

use App\Models\Department;
use App\Models\LandParcel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LandParcelDashboard extends Component
{
    private const REQUIRED_DOCUMENT_TYPES = ['purchase_agreement', 'survey_report'];

    public string $departmentFilter = '';
    public string $stageFilter = '';
    public string $districtFilter = '';

    protected $queryString = [
        'departmentFilter' => ['except' => ''],
        'stageFilter' => ['except' => ''],
        'districtFilter' => ['except' => ''],
    ];

    private function scopedQuery()
    {
        $user = Auth::user();
        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        return LandParcel::query()
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->stageFilter, fn ($q) => $q->where('stage', $this->stageFilter))
            ->when($this->districtFilter, fn ($q) => $q->where('district', 'like', "%{$this->districtFilter}%"));
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-land-dashboard'), 403);

        $parcels = $this->scopedQuery()->with('documents')->get();

        $delayed = $parcels->filter(fn (LandParcel $p) => $p->isStalled());
        $expiringLeases = $parcels->filter(fn (LandParcel $p) => $p->isLeaseExpiringSoon());
        $missingDocuments = $parcels->filter(function (LandParcel $p) {
            $currentTypes = $p->documents->where('is_current', true)->pluck('document_type');
            return collect(self::REQUIRED_DOCUMENT_TYPES)->diff($currentTypes)->isNotEmpty();
        });

        $departmentIds = Department::whereIn('organization_id', $user->managedOrganizationIds())
            ->pluck('id')->merge($user->managedDepartmentIds())->unique();

        return view('livewire.land-parcels.land-parcel-dashboard', [
            'total' => $parcels->count(),
            'untitledCount' => $parcels->where('stage', '!=', 'title_issued')->count(),
            'delayedCount' => $delayed->count(),
            'delayedParcels' => $delayed->values(),
            'disputedCount' => $parcels->where('stage', 'disputed')->count(),
            'expiringLeaseCount' => $expiringLeases->count(),
            'expiringLeases' => $expiringLeases->values(),
            'missingDocumentsCount' => $missingDocuments->count(),
            'completedTitlesCount' => $parcels->where('stage', 'title_issued')->count(),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
