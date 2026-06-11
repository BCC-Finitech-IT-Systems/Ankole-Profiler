<?php

namespace App\Livewire\Person;

use App\Models\EmailAddress;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\AllowedEmailDomain;
use App\Models\Department;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

#[\Livewire\Attributes\Layout('layouts.auth-card')]
class PersonSelfRegistrationComponent extends Component
{
    // Step navigation removed
    use WithFileUploads;

    // Step navigation removed
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
        'department_id' => '',
    ];

    // Documents step removed
    public $availableDepartments = null;

    public function fillSampleData(): void
    {
        $dept = \App\Models\Department::where('is_active', true)->first();
        $this->form = [
            'given_name'    => 'Grace',
            'middle_name'   => 'Atuheire',
            'family_name'   => 'Tumusiime',
            'date_of_birth' => '1995-03-22',
            'gender'        => 'Female',
            'phone'         => '+256782345678',
            'email'         => 'grace.tumusiime@example.com',
            'address'       => '45 Kamukuzi Hill, Mbarara',
            'country'       => 'Uganda',
            'district'      => 'Mbarara',
            'city'          => 'Mbarara',
            'role_type'     => 'STAFF',
            'role_title'    => 'Sunday School Teacher',
            'department_id' => $dept?->id ?? '',
        ];
    }

    public function mount()
    {
        $this->availableDepartments = collect(Department::where('is_active', true)->get());

        Log::debug('Available departments during mount', ['departments' => $this->availableDepartments]);

        if ($this->availableDepartments->isEmpty()) {
            session()->flash('error', 'No departments are available for registration.');
        }
    }


    public function submit()
    {
        // Public endpoint: limit registration attempts per IP. The throttle
        // must live here (not on the GET route) because Livewire submits
        // through /livewire/update.
        $rateLimiterKey = 'self-register:' . request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);

            throw ValidationException::withMessages([
                'form.email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($rateLimiterKey, 600);

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
                'form.department_id' => 'required|exists:departments,id',
            ]);

        // Debug log for organization_id
        Log::debug('Department ID during registration', ['department_id' => $this->form['department_id']]);

        // Check if email exists and is not verified
        $existingUser = User::where('email', $this->form['email'])->first();
        if ($existingUser) {
            if (!$existingUser->hasVerifiedEmail()) {
                $existingUser->sendEmailVerificationNotification();
                session()->flash('info', 'This email is already registered but not verified. Verification email sent.');
                return;
            } else {
                session()->flash('error', 'Email address already exists and is verified.');
                return;
            }
        }

        $temporaryPassword = Str::random(10);
        $superOrganization = Organization::where('is_super', true)->firstOrFail();
        $user = null;
        DB::beginTransaction();
        try {
            // Create User first
            $user = User::create([
                'name' => $this->form['given_name'] . ' ' . $this->form['family_name'],
                'email' => $this->form['email'],
                'password' => bcrypt($temporaryPassword),
            ]);

            Log::info('User created', ['user_id' => $user->id]);

            // Create Person with user_id
            $person = Person::create([
                'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
                'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier(),
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
                'classification' => ['STAFF'],
                'created_by' => $user->id,
            ]);
            Log::info('Person created', ['person_id' => $person->id]);

            $this->createContactInformation($person);
            Log::info('Contact information created', ['person_id' => $person->id]);

            PersonAffiliation::create([
                'person_id' => $person->id,
                'organization_id' => $superOrganization->id,
                'department_id' => $this->form['department_id'],
                'role_type' => $this->form['role_type'] ?? 'STAFF',
                'role_title' => $this->form['role_title'] ?? 'Department Admin',
                'start_date' => now(),
                'status' => 'active',
                'created_by' => $user->id,
                'user_id' => $user->id, // Capture user_id in PersonAffiliation
            ]);
            Log::info('Person affiliation created', ['person_id' => $person->id]);

            $user->assignRole('Person');
            Log::info('Role assigned', ['user_id' => $user->id]);

            // Send custom email verification notification with plain text temporary password
            $user->sendEmailVerificationNotification($temporaryPassword);
            Log::info('Custom verification notification sent with temporary password', ['user_id' => $user->id]);

            DB::commit();
            Log::info('DB commit successful', ['user_id' => $user->id, 'person_id' => $person->id]);

            // Only send welcome email after user verifies their email (handled elsewhere, not here)

            return redirect()->route('login')->with('self_register_success', true);
        } catch (\Exception $e) {
            if ($user) {
                $user->delete();
            }
            DB::rollBack();

            // Map technical error to user-friendly message
            $errorMessage = 'Registration failed. Please try again later.';
            if (str_contains($e->getMessage(), "Column 'organization_id' cannot be null")) {
                $errorMessage = 'The selected organization is invalid. Please select a valid organization.';
            }

            session()->flash('error', $errorMessage);
            session()->flash('error_reason', $e->getMessage()); // Keep technical error for debugging
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
            $email = $this->form['email'];
            $domain = strtolower(substr(strrchr($email, "@"), 1));

            $allowed = AllowedEmailDomain::where('domain', $domain)
                ->where('is_active', true)
                ->exists();

            if (! $allowed) {
                session()->flash('error', 'Registration failed.');
                session()->flash('error_reason', 'Your organization is not authorized to register.');
                throw ValidationException::withMessages([
                    'email' => 'Your organization is not authorized to register.',
                ]);
            }

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

        // ...existing code...re
    }
    public function render()
    {
        return view('livewire.person.person-self-registration');
    }
}
