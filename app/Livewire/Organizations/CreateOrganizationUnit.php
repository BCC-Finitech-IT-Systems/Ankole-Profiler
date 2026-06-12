<?php

namespace App\Livewire\Organizations;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\UnitPersonRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateOrganizationUnit extends Component
{
    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Step 1: Basic Info
    public $name = '';
    public $code = '';
    public $description = '';
    public $parent_unit_id = null;
    public $organization_id = null;
    public $department_id = null;      // NEW — links unit to a department
    public $unit_type = '';
    public $community = '';
    public $ministry_committee = '';
    public $administrative_office = '';

    // Step 2: Leadership & Governance
    public $unit_head = null;
    public $assistant_leader = null;
    public $leadership_committee = [];
    public $appointment_dates = '';
    public $reporting_line = '';

    // Step 3: Purpose & Mission
    public $mission = '';
    public $objectives = '';
    public $activities = '';
    public $target_audience = '';

    // Step 4: Contact Information
    public $official_email = '';
    public $phone_contact = '';
    public $physical_location = '';
    public $website = '';
    public $social_links = '';

    // Step 5: Operational Details
    public $unit_category = '';
    public $operational_status = 'active';
    public $date_established = '';
    public $faith_based = false;
    public $socio_economic = false;
    public $support_services = false;

    // Step 6: Membership Metadata
    public $membership_type = '';
    public $membership_eligibility = '';
    public $membership_capacity = '';
    public $join_requests_enabled = false;

    // Step 7: Events & Programs Metadata
    public $recurring_programs = '';
    public $event_schedule = '';
    public $promotion_permissions = '';
    public $resource_access_requirements = '';

    // Step 8: Showcase & Marketplace Support
    public $showcase_permissions = '';
    public $product_categories_allowed = '';
    public $approval_workflow = '';
    public $commission_structure = '';

    // Step 9: Initial members/roles — array of {person_id, role, can_view, can_edit, can_approve}
    public array $initialMembers = [];

    public bool $is_active = true;
    public array $orgOptions = [];
    public array $departmentOptions = [];

    // Per-member search state
    public array $memberSearchQueries = [];
    public array $memberSearchResults = [];
    public array $memberSelectedNames  = [];

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->can('assign-organization-unit')) {
            $this->orgOptions = Organization::orderBy('legal_name')->get(['id', 'legal_name'])->toArray();
        } else {
            $this->organization_id = user_current_organization()?->id;
        }

        $this->loadDepartments();
    }

    public function updatedOrganizationId(): void
    {
        $this->department_id = null;
        $this->loadDepartments();
    }

    private function loadDepartments(): void
    {
        $query = Department::query()->where('is_active', true)->orderBy('name');
        if ($this->organization_id) {
            $query->where('organization_id', $this->organization_id);
        }
        $this->departmentOptions = $query->get(['id', 'name'])->toArray();
    }

    public function addInitialMember(): void
    {
        $i = count($this->initialMembers);
        $this->initialMembers[]      = ['person_id' => null, 'role' => 'member', 'can_view' => true, 'can_edit' => false, 'can_approve' => false];
        $this->memberSearchQueries[$i] = '';
        $this->memberSearchResults[$i] = [];
        $this->memberSelectedNames[$i]  = '';
    }

    public function removeInitialMember(int $index): void
    {
        array_splice($this->initialMembers, $index, 1);
        array_splice($this->memberSearchQueries, $index, 1);
        array_splice($this->memberSearchResults, $index, 1);
        array_splice($this->memberSelectedNames, $index, 1);
        // Re-index
        $this->initialMembers      = array_values($this->initialMembers);
        $this->memberSearchQueries = array_values($this->memberSearchQueries);
        $this->memberSearchResults = array_values($this->memberSearchResults);
        $this->memberSelectedNames  = array_values($this->memberSelectedNames);
    }

    public function searchMemberPerson(int $index, string $query): void
    {
        $this->memberSearchQueries[$index] = $query;

        if (strlen(trim($query)) < 2) {
            $this->memberSearchResults[$index] = [];
            return;
        }

        $this->memberSearchResults[$index] = \App\Models\Person::query()
            ->where(function ($q) use ($query) {
                $q->whereRaw("CONCAT(given_name, ' ', family_name) LIKE ?", ["%{$query}%"])
                  ->orWhere('given_name', 'like', "%{$query}%")
                  ->orWhere('family_name', 'like', "%{$query}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->map(fn($p) => ['id' => $p->id, 'name' => trim("{$p->given_name} {$p->family_name}")])
            ->toArray();
    }

    public function selectMemberPerson(int $index, int $personId, string $name): void
    {
        $this->initialMembers[$index]['person_id'] = $personId;
        $this->memberSelectedNames[$index]          = $name;
        $this->memberSearchQueries[$index]          = '';
        $this->memberSearchResults[$index]          = [];
    }

    public function clearMemberPerson(int $index): void
    {
        $this->initialMembers[$index]['person_id'] = null;
        $this->memberSelectedNames[$index]          = '';
        $this->memberSearchQueries[$index]          = '';
        $this->memberSearchResults[$index]          = [];
    }

    public function goToStep(int $step): void
    {
        if ($step > 0 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    private function validateCurrentStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'name'            => 'required|string|max:255',
                'code'            => 'required|string|max:50|unique:organization_units,code',
                'organization_id' => 'required|exists:organizations,id',
            ]);
        }
    }

    public function fillSampleData(): void
    {
        $org  = Organization::where('is_super', false)->first();
        $dept = Department::where('is_active', true)->first();

        // Step 1
        $this->name                  = 'Youth Fellowship';
        $this->code                  = 'YF-' . rand(100, 999);
        $this->description           = 'A unit dedicated to youth spiritual growth and community service activities.';
        $this->organization_id       = $org?->id;
        $this->department_id         = $dept?->id;
        $this->unit_type             = 'Fellowship';
        $this->community             = 'Mbarara Central';
        $this->ministry_committee    = 'Youth & Young Adults';
        $this->administrative_office = 'Diocese of Ankole';
        // Step 2
        $this->appointment_dates  = '2024-2026';
        $this->reporting_line     = 'Parish Priest';
        // Step 3
        $this->mission          = 'To nurture the spiritual, social, and intellectual development of youth in the diocese.';
        $this->objectives       = 'Weekly Bible study, monthly outreach, annual youth camp.';
        $this->activities       = 'Bible study, sports, community service, choir.';
        $this->target_audience  = 'Youth aged 15–35';
        // Step 4
        $this->official_email     = 'youth@ankole.org';
        $this->phone_contact      = '+256701234567';
        $this->physical_location  = 'St. Peter\'s Hall, Church Road, Mbarara';
        // Step 5
        $this->unit_category      = 'Youth';
        $this->operational_status = 'active';
        $this->date_established   = '2010-01-15';
        $this->faith_based        = true;
        // Step 6
        $this->membership_type        = 'permanent';
        $this->membership_eligibility = 'Open to all baptised youth aged 15–35';
        $this->membership_capacity    = '200';
        $this->join_requests_enabled  = true;
    }

    public function submit(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user?->can('create-units'), 403);

        $this->validate([
            'name'                       => 'required|string|max:255',
            'code'                       => 'required|string|max:50|unique:organization_units,code',
            'organization_id'            => 'required|exists:organizations,id',
            'department_id'              => 'nullable|exists:departments,id',
            'official_email'             => 'nullable|email|max:255',
            'initialMembers.*.person_id' => 'nullable|exists:persons,id',
        ], [
            'initialMembers.*.person_id.exists' => 'One or more selected persons could not be found. Please use the search to select a valid person.',
        ]);

        DB::transaction(function () use ($user) {
            $unit = OrganizationUnit::create([
                'organization_id'        => $this->organization_id,
                'department_id'          => $this->department_id,
                'name'                   => $this->name,
                'code'                   => $this->code,
                'description'            => $this->description,
                'parent_unit_id'         => $this->parent_unit_id,
                'unit_type'              => $this->unit_type,
                'community'              => $this->community,
                'ministry_committee'     => $this->ministry_committee,
                'administrative_office'  => $this->administrative_office,
                'unit_head'              => $this->unit_head,
                'assistant_leader'       => $this->assistant_leader,
                'leadership_committee'   => $this->leadership_committee ?: null,
                'appointment_dates'      => $this->appointment_dates,
                'reporting_line'         => $this->reporting_line,
                'mission'                => $this->mission,
                'objectives'             => $this->objectives,
                'activities'             => $this->activities,
                'target_audience'        => $this->target_audience,
                'official_email'         => $this->official_email,
                'phone_contact'          => $this->phone_contact,
                'physical_location'      => $this->physical_location,
                'website'                => $this->website,
                'social_links'           => $this->social_links,
                'unit_category'          => $this->unit_category,
                'operational_status'     => $this->operational_status,
                'date_established'       => $this->date_established ?: null,
                'faith_based'            => $this->faith_based,
                'socio_economic'         => $this->socio_economic,
                'support_services'       => $this->support_services,
                'membership_type'        => $this->membership_type,
                'membership_eligibility' => $this->membership_eligibility,
                'membership_capacity'    => $this->membership_capacity ?: null,
                'join_requests_enabled'  => $this->join_requests_enabled,
                'recurring_programs'     => $this->recurring_programs,
                'event_schedule'         => $this->event_schedule,
                'promotion_permissions'  => $this->promotion_permissions,
                'resource_access_requirements' => $this->resource_access_requirements,
                'showcase_permissions'   => $this->showcase_permissions,
                'product_categories_allowed' => $this->product_categories_allowed,
                'approval_workflow'      => $this->approval_workflow,
                'commission_structure'   => $this->commission_structure,
                'is_active'              => $this->is_active,
            ]);

            // Step 9: save initial members into unit_person_roles (not JSON blob)
            foreach ($this->initialMembers as $member) {
                if (empty($member['person_id'])) {
                    continue;
                }
                UnitPersonRole::firstOrCreate(
                    [
                        'unit_id'   => $unit->id,
                        'person_id' => $member['person_id'],
                        'role'      => $member['role'] ?? 'member',
                    ],
                    [
                        'can_view'    => (bool) ($member['can_view'] ?? true),
                        'can_edit'    => (bool) ($member['can_edit'] ?? false),
                        'can_approve' => (bool) ($member['can_approve'] ?? false),
                        'granted_by'  => Auth::id(),
                        'granted_at'  => now(),
                    ]
                );
            }
        });

        session()->flash('success', 'Organization unit created successfully.');
        $this->redirect(route('organization-units.index'));
    }

    public function render()
    {
        $units = OrganizationUnit::when($this->organization_id, fn($q) => $q->where('organization_id', $this->organization_id))
            ->orderBy('name')
            ->get(['id', 'name', 'parent_unit_id']);

        return view('livewire.organizations.create-organization-unit', [
            'units'             => $units,
            'orgOptions'        => $this->orgOptions,
            'departmentOptions' => $this->departmentOptions,
        ]);
    }
}
