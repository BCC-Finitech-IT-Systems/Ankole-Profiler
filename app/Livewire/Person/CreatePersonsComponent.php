<?php

namespace App\Livewire\Person;

use App\Models\EmailAddress;
use Livewire\Component;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Models\DepartmentSubCategory;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CreatePersonsComponent extends Component
{
    public $form = [
        'given_name' => '',
        'middle_name' => '',
        'family_name' => '',
        'date_of_birth' => '',
        'gender' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'country' => 'Uganda',
        'district' => '',
        'city' => '',
        'role_type' => 'STAFF',
        'role_title' => '',
        'organization_id' => '',
        'assign_as_project_head' => false, // New field for Project Head assignment
    ];

    public $availableOrganizations = [];
    public $userDepartmentId = null;
    public $userDepartmentName = null;
    public $isOrgAdmin = false;
    public $isSuperAdmin = false;
    public $canAssignProjectHead = false;

    // Available role options for the dropdown
    public $roleOptions = [
        'person' => 'Staff/Person',
        'project_head' => 'Project Head',
    ];

    public function mount()
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        $this->isSuperAdmin = $authUser && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('Super Admin');

        $this->isOrgAdmin = $authUser && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('Organization Admin') && !$authUser->hasRole('Super Admin');

        // Both Super Admin and Organization Admin can assign Project Heads
        $this->canAssignProjectHead = $this->isSuperAdmin || $this->isOrgAdmin;

        if ($this->isOrgAdmin && $authUser->person) {
            // Get the Org Admin's department from their affiliation
            $affiliation = PersonAffiliation::where('person_id', $authUser->person->id)
                ->where('status', 'active')
                ->whereNotNull('department_id')
                ->first();

            if ($affiliation && $affiliation->department_id) {
                $this->userDepartmentId = $affiliation->department_id;
                $department = \App\Models\Department::with('subCategories')->find($affiliation->department_id);
                $this->userDepartmentName = $department?->name;

                // Get sub-category names for this department
                $subCategoryNames = $department
                    ? $department->subCategories->pluck('name')->map(fn($n) => strtolower(trim($n)))->filter()->values()
                    : collect();

                if ($subCategoryNames->isNotEmpty()) {
                    // Load organizations whose category matches the department's sub-categories
                    $this->availableOrganizations = Organization::query()
                        ->where('is_super', false)
                        ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all())
                        ->orderBy('legal_name')
                        ->get()
                        ->toArray();
                }
            }

            // Set default organization_id if available
            if (!empty($this->availableOrganizations)) {
                $this->form['organization_id'] = $this->availableOrganizations[0]['id'];
            }
        } else {
            // Super Admin: load all organizations
            $this->availableOrganizations = Organization::where('is_super', false)
                ->orderBy('legal_name')
                ->get()
                ->toArray();

            if (!empty($this->availableOrganizations)) {
                $this->form['organization_id'] = $this->availableOrganizations[0]['id'];
            }
        }
    }

    /**
     * Get existing Project Heads for a specific organization
     */
    public function getProjectHeadsForOrganization($organizationId)
    {
        return Person::whereHas('user', function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'Project Head');
            });
        })
        ->whereHas('affiliations', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId)
                ->where('status', 'active');
        })
        ->with(['user', 'affiliations' => function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId)->where('status', 'active');
        }])
        ->get();
    }

    public function submit()
    {
        $this->validate([
            'form.given_name' => 'required|string|max:255',
            'form.family_name' => 'required|string|max:255',
            'form.date_of_birth' => 'required|date',
            'form.gender' => ['required', Rule::in(['Male', 'Female'])],
            'form.phone' => 'required|string|max:20|unique:phones,number',
            'form.email' => 'required|email|unique:users,email',
            'form.address' => 'required|string',
            'form.country' => 'required|string',
            'form.district' => 'required|string',
            'form.city' => 'required|string',
            'form.role_title' => 'required|string',
            'form.organization_id' => 'required|exists:organizations,id',
            'form.assign_as_project_head' => 'boolean',
        ]);

        // Validate that the current user can assign Project Head role
        if ($this->form['assign_as_project_head'] && !$this->canAssignProjectHead) {
            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'You do not have permission to assign Project Head role.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
            return;
        }

        // Check if email exists and is not verified
        $existingUser = User::where('email', $this->form['email'])->first();
        if ($existingUser) {
            if (!$existingUser->hasVerifiedEmail()) {
                $existingUser->sendEmailVerificationNotification();
                session()->flash('info', 'This email is already registered but not verified. Verification email sent.');
                return;
            } else {
                $this->dispatch('swal', [
                    'position' => 'top-end',
                    'icon' => 'error',
                    'title' => 'Email address already exists and is verified.',
                    'showConfirmButton' => false,
                    'timer' => 1500
                ]);
                return;
            }
        }

        $temporaryPassword = Str::random(10);
        $user = null;
        DB::beginTransaction();
        try {
            // Create User first
            $user = User::create([
                'name' => $this->form['given_name'] . ' ' . $this->form['family_name'],
                'email' => $this->form['email'],
                'password' => bcrypt($temporaryPassword),
            ]);

            // Store the temporary password in the database
            $user->temporary_password = $temporaryPassword;
            $user->save();

            // Store the temporary password encrypted in cache for later use
            try {
                if (!empty($temporaryPassword)) {
                    Cache::put('temp_password_user_' . $user->id, Crypt::encryptString($temporaryPassword), now()->addDays(7));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cache temporary password for user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            Log::info('User created', ['user_id' => $user->id]);

            $currentOrganization = user_current_organization();
            $authUser = Auth::user();

            if ($this->isOrgAdmin) {
                // Org Admin: validate the selected organization is in their department scope
                if (empty($this->form['organization_id']) || !\App\Models\Organization::find($this->form['organization_id'])) {
                    session()->flash('error', 'Please select a valid project (organization).');
                    DB::rollBack();
                    return;
                }
            } elseif (!$this->isSuperAdmin) {
                if (!$currentOrganization) {
                    session()->flash('error', 'You are not associated with any organization.');
                    DB::rollBack();
                    return;
                }
                $this->form['organization_id'] = $currentOrganization->id;
            } else {
                // Validate that the organization_id is provided for Super Admins
                if (empty($this->form['organization_id']) || !\App\Models\Organization::find($this->form['organization_id'])) {
                    session()->flash('error', 'Please select a valid organization.');
                    DB::rollBack();
                    return;
                }
            }

            $selectedOrgId = $this->form['organization_id'];

            Log::info('Organization selection', [
                'selected_org_id' => $selectedOrgId,
                'is_org_admin' => $this->isOrgAdmin,
                'is_super_admin' => $this->isSuperAdmin,
                'assign_as_project_head' => $this->form['assign_as_project_head'],
            ]);

            // Determine classification based on role assignment
            $classification = $this->form['assign_as_project_head']
                ? ['STAFF', 'PROJECT_HEAD']
                : ['STAFF'];

            // Create Person with user_id
            $person = Person::create([
                'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
                'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier(),
                'organization_id' => $selectedOrgId,
                'given_name' => $this->form['given_name'],
                'middle_name' => $this->form['middle_name'],
                'family_name' => $this->form['family_name'],
                'date_of_birth' => $this->form['date_of_birth'],
                'gender' => $this->form['gender'],
                'country' => $this->form['country'],
                'district' => $this->form['district'],
                'address' => $this->form['address'],
                'city' => $this->form['city'],
                'user_id' => $user->id,
                'classification' => json_encode($classification),
                'created_by' => Auth::id(),
            ]);
            Log::info('Person created', ['person_id' => $person->id]);

            $this->createContactInformation($person);
            Log::info('Contact information created', ['person_id' => $person->id]);

            // Determine department_id
            $departmentId = $this->userDepartmentId;
            if (!$departmentId) {
                $selectedOrg = Organization::find($selectedOrgId);
                if ($selectedOrg && $selectedOrg->category) {
                    $subCategory = DepartmentSubCategory::whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [strtolower(trim($selectedOrg->category))]
                    )->first();
                    $departmentId = $subCategory?->department_id;
                }
                Log::info('Derived department_id from organization category', [
                    'organization_id' => $selectedOrgId,
                    'category' => $selectedOrg->category ?? null,
                    'department_id' => $departmentId,
                ]);
            }

            // Determine role_title based on Project Head assignment
            $roleTitle = $this->form['assign_as_project_head']
                ? ($this->form['role_title'] ?: 'Project Head')
                : ($this->form['role_title'] ?: 'Staff');

            PersonAffiliation::create([
                'person_id' => $person->id,
                'organization_id' => $selectedOrgId,
                'department_id' => $departmentId,
                'role_type' => $this->form['role_type'] ?? 'STAFF',
                'role_title' => $roleTitle,
                'start_date' => now(),
                'status' => 'active',
                'created_by' => Auth::id(),
                'user_id' => $user->id,
            ]);
            Log::info('Person affiliation created', ['person_id' => $person->id]);

            // Assign appropriate Spatie role based on selection
            if ($this->form['assign_as_project_head']) {
                $user->assignRole('Project Head');
                Log::info('Project Head role assigned', ['user_id' => $user->id, 'organization_id' => $selectedOrgId]);

                // Also assign Person role as base role
                if (!$user->hasRole('Person')) {
                    $user->assignRole('Person');
                }
            } else {
                $user->assignRole('Person');
                Log::info('Person role assigned', ['user_id' => $user->id]);
            }

            DB::commit();
            Log::info('DB commit successful', ['user_id' => $user->id, 'person_id' => $person->id]);

            $successMessage = $this->form['assign_as_project_head']
                ? 'Project Head registered successfully!'
                : 'Person registered successfully!';

            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'success',
                'title' => $successMessage,
                'showConfirmButton' => false,
                'timer' => 2000
            ]);

            return redirect()->route('persons.all');
        } catch (\Exception $e) {
            if ($user) {
                $user->delete();
            }
            DB::rollBack();
            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'Registration failed. Please try again.',
                'showConfirmButton' => false,
                'timer' => 1500
            ]);
            Log::error('Registration DB error: ' . $e->getMessage(), ['exception' => $e]);
            return;
        }
    }

    private function createContactInformation(Person $person)
    {
        // Phone
        if (!empty($this->form['phone'])) {
            Phone::create([
                'person_id' => $person->id,
                'phone_id' => \App\Helpers\IdGenerator::generatePhoneId(),
                'number' => $this->form['phone'],
                'type' => 'mobile',
                'is_primary' => true,
                'status' => 'active',
                'created_by' => $person->user_id,
            ]);
        }

        // Email
        if (!empty($this->form['email'])) {
            EmailAddress::create([
                'person_id' => $person->id,
                'email_id' => \App\Helpers\IdGenerator::generateEmailId(),
                'email' => $this->form['email'],
                'type' => 'personal',
                'is_primary' => true,
                'status' => 'active',
                'created_by' => $person->user_id,
            ]);
        }
    }

    public function resetForm()
    {
        $this->form = [
            'given_name' => '',
            'middle_name' => '',
            'family_name' => '',
            'date_of_birth' => '',
            'gender' => '',
            'phone' => '',
            'email' => '',
            'national_id' => '',
            'address' => '',
            'city' => '',
            'district' => '',
            'country' => 'Uganda',
            'role_type' => 'STAFF',
            'role_title' => '',
            'site' => '',
            'start_date' => '',
            'organization_id' => '',
            'organization' => '',
            'assign_as_project_head' => false,
        ];
    }

    public function render()
    {
        return view('livewire.person.person-create-component', [
            'availableOrganizations' => $this->availableOrganizations,
            'canAssignProjectHead' => $this->canAssignProjectHead,
        ]);
    }
}
