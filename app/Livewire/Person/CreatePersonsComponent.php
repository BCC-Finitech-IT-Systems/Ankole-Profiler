<?php

namespace App\Livewire\Person;

use App\Models\EmailAddress;
use Livewire\Component;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
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

    public function mount()
    {
        $this->availableOrganizations = Organization::all()->toArray();

        // Set default organization_id if available
        if (!empty($this->availableOrganizations)) {
            $this->form['organization_id'] = $this->availableOrganizations[0]['id'];
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
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('Super Admin');

            if (!$isSuperAdmin) {
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

            // Create Person with user_id
            $person = Person::create([
                'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
                'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier(),
                'organization_id' => $this->form['organization_id'], // Use form input for Super Admin or current organization for others
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

            PersonAffiliation::create([
                'person_id' => $person->id,
                'organization_id' => $currentOrganization->id, // Use current user's organization
                'role_type' => $this->form['role_type'] ?? 'STAFF',
                'role_title' => $this->form['role_title'] ?? 'Organization Admin',
                'start_date' => now(),
                'status' => 'active',
                'created_by' => $user->id,
                'user_id' => $user->id, // Capture user_id in PersonAffiliation
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
