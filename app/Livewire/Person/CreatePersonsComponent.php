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
    ];

    public $availableOrganizations = [];
    public $userDepartmentId = null;
    public $userDepartmentName = null;
    public $isOrgAdmin = false;

    public function mount()
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        $this->isOrgAdmin = $authUser && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('Organization Admin') && !$authUser->hasRole('Super Admin');

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
            $this->availableOrganizations = Organization::all()->toArray();

            if (!empty($this->availableOrganizations)) {
                $this->form['organization_id'] = $this->availableOrganizations[0]['id'];
            }
        }
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
            ]);

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
                'password' => bcrypt($temporaryPassword), // Save the temporary password
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
            // Check the LOGGED-IN user's role, not the newly created user
            $authUser = Auth::user();
            $isSuperAdmin = $authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('Super Admin');

            if ($this->isOrgAdmin) {
                // Org Admin: validate the selected organization is in their department scope
                if (empty($this->form['organization_id']) || !\App\Models\Organization::find($this->form['organization_id'])) {
                    session()->flash('error', 'Please select a valid project (organization).');
                    return;
                }
                // Keep the user-selected organization_id from the dropdown
            } elseif (!$isSuperAdmin) {
                if (!$currentOrganization) {
                    session()->flash('error', 'You are not associated with any organization.');
                    return;
                }
                $this->form['organization_id'] = $currentOrganization->id;
            } else {
                // Validate that the organization_id is provided for Super Admins
                if (empty($this->form['organization_id']) || !\App\Models\Organization::find($this->form['organization_id'])) {
                    session()->flash('error', 'Please select a valid organization.');
                    return;
                }
            }

            // Use the selected organization from the dropdown for both Person and PersonAffiliation
            $selectedOrgId = $this->form['organization_id'];

            Log::info('Organization selection', [
                'selected_org_id' => $selectedOrgId,
                'is_org_admin' => $this->isOrgAdmin,
                'is_super_admin' => $isSuperAdmin,
                'form_organization_id' => $this->form['organization_id'],
            ]);

            // Create Person with user_id
            $person = Person::create([
                'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
                'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier(),
                'organization_id' => $selectedOrgId, // The selected project/organization
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
                'classification' => json_encode(['STAFF']),
                'created_by' => $user->id,
            ]);
            Log::info('Person created', ['person_id' => $person->id]);

            $this->createContactInformation($person);
            Log::info('Contact information created', ['person_id' => $person->id]);

            // Determine department_id: already set for Org Admin, derive from org's category for others
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

            PersonAffiliation::create([
                'person_id' => $person->id,
                'organization_id' => $selectedOrgId, // Always use the selected organization
                'department_id' => $departmentId, // Derived from org's category or Org Admin's affiliation
                'role_type' => $this->form['role_type'] ?? 'STAFF',
                'role_title' => $this->form['role_title'] ?? 'Organization Admin',
                'start_date' => now(),
                'status' => 'active',
                'created_by' => $user->id,
                'user_id' => $user->id,
            ]);
            Log::info('Person affiliation created', ['person_id' => $person->id]);

            // Assign Organization Admin role
            $user->assignRole('Person');
            Log::info('Role assigned', ['user_id' => $user->id]);

            DB::commit();
            Log::info('DB commit successful', ['user_id' => $user->id, 'person_id' => $person->id]);

            session()->flash('info', 'Registered.');
            // $this->dispatch('swal', [
            //     'position' => 'top-end',
            //     'icon' => 'success',
            //     'title' => 'Registration successful! Please check your email to verify your account.',
            //     'showConfirmButton' => false,
            //     'timer' => 1500
            // ]);
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

        // ...existing code...
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
            'country' => 'UGA',
            'role_type' => '',
            'role_title' => '',
            'site' => '',
            'start_date' => '',
            'organization_id' => '',
            'organization' => '',
        ];

    }

    public function render()
    {
        return view('livewire.person.person-create-component', [
            'availableOrganizations' => $this->availableOrganizations,
        ]);
    }
}
