<?php

namespace App\Livewire\Organizations;

use App\Models\DepartmentSubCategory;
use App\Models\Organization;
use App\Models\OrganizationSite;
use App\Mail\AdminWelcomeEmail;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Create extends Component
{
    use WithFileUploads;

    // Import functionality
    public $importFile;
    protected $importRules = [
        'importFile' => 'required|file|mimes:csv,txt,xlsx,xls',
    ];

    // Step Management
    public $currentStep = 1;
    public $totalSteps = 6;

    // Step 1: Organization Category Selection
    public $category = '';

    // Step 2: Basic Information
    public $legal_name = '';
    public $display_name = '';
    public $code = '';
    public $organization_type = 'STANDALONE';
    public $parent_organization_id = null;
    public $registration_number = '';
    public $tax_identification_number = '';
    public $country_of_registration = 'UGA';
    public $date_established = '';
    public $website_url = '';
    public $contact_email = '';
    public $contact_phone = '';
    public $description = '';

    // Step 3: Address Information
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $district = '';
    public $postal_code = '';
    public $country = 'UGA';
    public $latitude = '';
    public $longitude = '';

    // Step 4: Contact Persons & Regulatory
    public $regulatory_body = '';
    public $license_number = '';
    public $license_issue_date = '';
    public $license_expiry_date = '';
    public $accreditation_status = 'NOT_APPLICABLE';
    public $primary_contact_name = '';
    public $primary_contact_title = '';
    public $primary_contact_email = '';
    public $primary_contact_phone = '';
    public $secondary_contact_name = '';
    public $secondary_contact_email = '';
    public $secondary_contact_phone = '';

    public $admin_assignment_type = 'defer'; // Options: 'primary', 'secondary', 'custom', 'defer'
    public $custom_admin_name = '';
    public $custom_admin_email = '';
    public $custom_admin_phone = '';
    public $custom_admin_title = ''; // Added missing field
    public $custom_admin_role = 'SYSTEM_ADMIN'; // Default role for system admin
    public $send_welcome_email = true; // Option to send welcome email

    // Step 5: Category-Specific Details
    public $hospital_details = [];
    public $school_details = [];
    public $sacco_details = [];
    public $parish_details = [];
    public $corporate_details = [];

    // Step 6: System Configuration
    public $bank_name = '';
    public $bank_account_number = '';
    public $bank_branch = '';
    public $default_currency = 'UGX';
    public $timezone = 'Africa/Kampala';
    public $default_language = 'en';


    public function importOrganizations()
    {
        $this->validate($this->importRules);

        try {
            Log::info('Starting organization import', [
                'file' => $this->importFile ? $this->importFile->getClientOriginalName() : null,
                'size' => $this->importFile ? $this->importFile->getSize() : null,
                'mime' => $this->importFile ? $this->importFile->getMimeType() : null,
            ]);
            $import = new \App\Imports\OrganizationsImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $this->importFile);
            $summary = $import->results['summary'];
            $details = $import->results['details'];
            $success = $summary['success'] ?? 0;
            $failed = $summary['failed'] ?? 0;
            $total = $summary['total'] ?? 0;

            Log::info('Organization import completed', [
                'success' => $success,
                'failed' => $failed,
                'total' => $total,
                'details' => $details,
            ]);

            if ($failed > 0) {
                $errorDetails = collect($details)->where('status', 'failed')->map(function($row) {
                    return 'Row ' . $row['row'] . ': ' . $row['message'];
                })->implode(' | ');
                session()->flash('error', "Imported $success of $total organizations. $failed failed. Errors: $errorDetails");
            } else {
                session()->flash('success', "Imported $success of $total organizations. $failed failed.");
                redirect('organizations');
            }
        } catch (\Exception $e) {
            Log::error('Organization import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    protected $rules = [
        // Step 1 - category validated dynamically from department_sub_categories table
        'category' => 'required|exists:department_sub_categories,name',

        // Step 2
        'legal_name' => 'required|string|max:255|unique:Organizations,legal_name',
        'code' => 'required|string|max:20|unique:Organizations,code',
        'organization_type' => 'required|in:HOLDING,SUBSIDIARY,STANDALONE',
        'registration_number' => 'required|string|unique:Organizations,registration_number',
        'contact_email' => 'required|email|unique:Organizations,contact_email',
        'contact_phone' => 'required|string',
        'date_established' => 'required|date|before_or_equal:today',

        // Step 3
        'address_line_1' => 'required|string',
        'city' => 'required|string',
        'country' => 'required|string|size:3',

        // Step 4
        'primary_contact_name' => 'required|string',
        'primary_contact_email' => 'required|email',
        'primary_contact_phone' => 'required|string',

        // Secondary contact (conditional)
        'secondary_contact_name' => 'required_if:admin_assignment_type,secondary|string|max:255',
        'secondary_contact_email' => 'required_if:admin_assignment_type,secondary|email|unique:users,email',
        'secondary_contact_phone' => 'required_if:admin_assignment_type,secondary|string|max:20',

        // Admin Assignment (conditional)
        'custom_admin_name' => 'required_if:admin_assignment_type,custom|string|max:255',
        'custom_admin_email' => 'required_if:admin_assignment_type,custom|email|unique:users,email',
        'custom_admin_phone' => 'required_if:admin_assignment_type,custom|string|max:20',
        'custom_admin_title' => 'nullable|string|max:100',
    ];

    public function mount()
    {
        // Initialize category-specific arrays
        $this->initializeCategoryDetails();
    }

    public function initializeCategoryDetails()
    {
        $this->hospital_details = [
            'hospital_type' => '',
            'ownership_type' => '',
            'bed_capacity' => '',
            'operating_theaters' => '',
            'has_emergency_department' => false,
            'has_icu' => false,
            'has_laboratory' => false,
            'has_pharmacy' => false,
            'has_radiology' => false,
            'has_ambulance' => false,
            'medical_license_number' => '',
            'moh_registration_number' => '',
            'nhis_accreditation' => false,
            'insurance_providers' => [],
            'specializations' => [],
            'accreditations' => [],
            'emergency_hotline' => '',
        ];

        $this->school_details = [
            'school_type' => '',
            'school_level' => '',
            'ownership' => '',
            'gender_composition' => '',
            'boarding_type' => '',
            'curriculum' => '',
            'student_capacity' => '',
            'current_enrollment' => '',
            'number_of_classrooms' => '',
            'number_of_teachers' => '',
            'moe_registration_number' => '',
            'uneb_center_number' => '',
            'facilities' => [
                'has_library' => false,
                'has_computer_lab' => false,
                'has_science_labs' => false,
                'has_sports_facilities' => false,
                'has_canteen' => false,
                'has_medical_room' => false,
                'has_transport' => false,
                'has_dormitories' => false,
            ],
            'academic_calendar' => [
                'structure' => '',
                'term1_start' => '',
                'term1_end' => '',
                'term2_start' => '',
                'term2_end' => '',
                'term3_start' => '',
                'term3_end' => '',
            ]
        ];

        $this->sacco_details = [
            'sacco_type' => '',
            'membership_type' => '',
            'bond_of_association' => '',
            'registration_authority' => '',
            'tier_level' => '',
            'central_bank_license' => '',
            'minimum_share_capital' => '',
            'share_value' => '',
            'minimum_shares' => '',
            'maximum_shares' => '',
            'entrance_fee' => '',
            'registration_fee' => '',
            'current_total_members' => '',
            'active_members' => '',
            'savings_products' => [],
            'loan_products' => [],
            'interest_rates' => [
                'savings_rate' => '',
                'loan_rate' => '',
                'penalty_rate' => '',
                'processing_fee' => '',
            ],
            'loan_terms' => [
                'minimum_amount' => '',
                'maximum_amount' => '',
                'minimum_period_months' => '',
                'maximum_period_months' => '',
                'loan_to_share_ratio' => '',
            ],
            'services' => [
                'number_of_branches' => '',
                'mobile_money_integration' => false,
                'mobile_money_providers' => [],
                'agency_banking' => false,
                'atm_services' => false,
                'online_banking' => false,
            ]
        ];

        $this->parish_details = [
            'denomination' => '',
            'church_type' => '',
            'archdiocese_diocese' => '',
            'mother_church' => '',
            'patron_saint' => '',
            'registered_members' => '',
            'active_members' => '',
            'sub_parishes' => '',
            'outstations' => '',
        ];

        $this->corporate_details = [
            'company_type' => '',
            'industry_sector' => '',
            'number_of_employees' => '',
            'annual_turnover' => '',
            'business_activities' => [],
            'certifications' => [],
        ];
    }

    public function updatedCategory()
    {
        // Auto-generate regulatory body based on category
        $this->regulatory_body = $this->getDefaultRegulatoryBody($this->category);
    }

    public function updatedLegalName()
    {
        // Auto-generate organization code from legal name
        if ($this->legal_name && !$this->code) {
            $this->code = $this->generateOrganizationCode($this->legal_name);
        }
    }

    private function getDefaultRegulatoryBody($category)
    {
        return match ($category) {
            'hospital' => 'Ministry of Health',
            'school' => 'Ministry of Education and Sports',
            'sacco' => 'Ministry of Trade, Industry and Cooperatives',
            'parish' => 'Religious Affairs Committee',
            'corporate' => 'Uganda Registration Services Bureau',
            'government' => 'Public Service Commission',
            'ngo' => 'NGO Registration Board',
            default => ''
        };
    }

    private function generateOrganizationCode($name)
    {
        // Generate a code from the organization name
        $words = explode(' ', $name);
        $code = '';

        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $code .= strtoupper(substr($word, 0, 3));
            }
        }

        // Add a random number to ensure uniqueness
        $code .= sprintf('%03d', rand(1, 999));

        return substr($code, 0, 20); // Limit to 20 characters
    }

    public function nextStep()
    {
        $this->validateCurrentStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps && $this->canGoToStep($step)) {
            $this->currentStep = $step;
        }
    }

    public function canGoToStep($step)
    {
        // Allow going back to previous steps
        if ($step <= $this->currentStep) {
            return true;
        }

        // For forward navigation, validate current step first
        if ($step > $this->currentStep) {
            try {
                $this->validateCurrentStep();
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    // Real-time validation methods for admin fields
    public function updatedCustomAdminEmail()
    {
        if ($this->custom_admin_email && $this->admin_assignment_type === 'custom') {
            $this->validateOnly('custom_admin_email');
        }
    }

    public function updatedCustomAdminName()
    {
        if ($this->custom_admin_name && $this->admin_assignment_type === 'custom') {
            $this->validateOnly('custom_admin_name');
        }
    }

    public function updatedCustomAdminPhone()
    {
        if ($this->custom_admin_phone && $this->admin_assignment_type === 'custom') {
            $this->validateOnly('custom_admin_phone');
        }
    }

    // Real-time validation for secondary contact fields
    public function updatedSecondaryContactEmail()
    {
        if ($this->secondary_contact_email && $this->admin_assignment_type === 'secondary') {
            $this->validateOnly('secondary_contact_email');
        }
    }

    public function updatedSecondaryContactName()
    {
        if ($this->secondary_contact_name && $this->admin_assignment_type === 'secondary') {
            $this->validateOnly('secondary_contact_name');
        }
    }

    public function updatedSecondaryContactPhone()
    {
        if ($this->secondary_contact_phone && $this->admin_assignment_type === 'secondary') {
            $this->validateOnly('secondary_contact_phone');
        }
    }

    public function updatedAdminAssignmentType()
    {
        // Clear validation errors when admin assignment type changes
        $this->resetErrorBag([
            'custom_admin_name', 'custom_admin_email', 'custom_admin_phone',
            'secondary_contact_name', 'secondary_contact_email', 'secondary_contact_phone'
        ]);
    }

    public function getAdminPreviewProperty()
    {
        return match($this->admin_assignment_type) {
            'primary' => $this->primary_contact_name
                ? "Primary Contact: {$this->primary_contact_name} ({$this->primary_contact_email})"
                : 'Primary contact will be the admin (enter details above)',
            'secondary' => $this->secondary_contact_name
                ? "Secondary Contact: {$this->secondary_contact_name} ({$this->secondary_contact_email})"
                : 'Secondary contact will be the admin (enter details below)',
            'custom' => $this->custom_admin_name
                ? "Custom Admin: {$this->custom_admin_name} ({$this->custom_admin_email})"
                : 'Custom administrator will be assigned (enter details below)',
            'defer' => 'Administrator will be assigned later',
            default => 'No administrator assigned'
        };
    }

    private function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate(['category' => $this->rules['category']]);
                break;
            case 2:
                $this->validate([
                    'legal_name' => $this->rules['legal_name'],
                    'code' => $this->rules['code'],
                    'organization_type' => $this->rules['organization_type'],
                    'registration_number' => $this->rules['registration_number'],
                    'contact_email' => $this->rules['contact_email'],
                    'contact_phone' => $this->rules['contact_phone'],
                    'date_established' => $this->rules['date_established'],
                ]);
                break;
            case 3:
                $this->validate([
                    'address_line_1' => $this->rules['address_line_1'],
                    'city' => $this->rules['city'],
                    'country' => $this->rules['country'],
                ]);
                break;
            case 4:
                $validationRules = [
                    'primary_contact_name' => $this->rules['primary_contact_name'],
                    'primary_contact_email' => $this->rules['primary_contact_email'],
                    'primary_contact_phone' => $this->rules['primary_contact_phone'],
                ];

                // Add secondary admin validation if that option is selected
                if ($this->admin_assignment_type === 'secondary') {
                    $validationRules['secondary_contact_name'] = $this->rules['secondary_contact_name'];
                    $validationRules['secondary_contact_email'] = $this->rules['secondary_contact_email'];
                    $validationRules['secondary_contact_phone'] = $this->rules['secondary_contact_phone'];
                }

                // Add custom admin validation if that option is selected
                if ($this->admin_assignment_type === 'custom') {
                    $validationRules['custom_admin_name'] = $this->rules['custom_admin_name'];
                    $validationRules['custom_admin_email'] = $this->rules['custom_admin_email'];
                    $validationRules['custom_admin_phone'] = $this->rules['custom_admin_phone'];
                }

                $this->validate($validationRules);
                break;
        }
    }

    public function submit()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            // Prepare organization data
            $organizationData = [
                'legal_name' => $this->legal_name,
                'display_name' => $this->display_name ?: $this->legal_name,
                'code' => $this->code,
                'category' => $this->category,
                'organization_type' => 'branch', // Always assign as branch
                'parent_organization_id' => $this->parent_organization_id,
                'registration_number' => $this->registration_number,
                'tax_identification_number' => $this->tax_identification_number,
                'country_of_registration' => $this->country_of_registration,
                'date_established' => $this->date_established,
                'website_url' => $this->website_url,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'description' => $this->description,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'district' => $this->district,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'latitude' => $this->latitude ?: null,
                'longitude' => $this->longitude ?: null,
                'regulatory_body' => $this->regulatory_body,
                'license_number' => $this->license_number,
                'license_issue_date' => $this->license_issue_date ?: null,
                'license_expiry_date' => $this->license_expiry_date ?: null,
                'accreditation_status' => $this->accreditation_status,
                'primary_contact_name' => $this->primary_contact_name,
                'primary_contact_title' => $this->primary_contact_title,
                'primary_contact_email' => $this->primary_contact_email,
                'primary_contact_phone' => $this->primary_contact_phone,
                'secondary_contact_name' => $this->secondary_contact_name,
                'secondary_contact_email' => $this->secondary_contact_email,
                'secondary_contact_phone' => $this->secondary_contact_phone,
                'bank_name' => $this->bank_name,
                'bank_account_number' => $this->bank_account_number,
                'bank_branch' => $this->bank_branch,
                'default_currency' => $this->default_currency,
                'timezone' => $this->timezone,
                'default_language' => $this->default_language,
                'is_super' => false, // Always assign as branch
            ];

            // Add category-specific details
            switch ($this->category) {
                case 'hospital':
                    $organizationData['hospital_details'] = $this->hospital_details;
                    break;
                case 'school':
                    $organizationData['school_details'] = $this->school_details;
                    break;
                case 'sacco':
                    $organizationData['sacco_details'] = $this->sacco_details;
                    break;
                case 'parish':
                    $organizationData['parish_details'] = $this->parish_details;
                    break;
                case 'corporate':
                    $organizationData['corporate_details'] = $this->corporate_details;
                    break;
            }

            $organization = Organization::create($organizationData);

            $adminUser = null;
            if ($this->admin_assignment_type !== 'defer') {
                $adminUser = $this->createAdminUser($organization);
            }

            DB::commit();

            // Prepare success message
            $message = 'Organization created successfully!';
            if ($adminUser) {
                $tempPassword = session()->pull('admin_temp_password');
                $message .= " System Administrator '{$adminUser->name}' has been assigned.";

                if ($this->send_welcome_email) {
                    $message .= " Welcome email with login instructions has been sent to {$adminUser->email}.";
                } else if ($tempPassword) {
                    $message .= " Temporary password: {$tempPassword} (Please share securely with the admin)";
                }
            } else {
                $message .= " Remember to assign a system administrator.";
            }

            session()->flash('success', $message);

            return redirect()->route('organizations.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating organization: ' . $e->getMessage());
        }
    }

    private function createAdminUser($organization)
    {
        // Determine admin contact based on selection
        $adminData = match($this->admin_assignment_type) {
            'primary' => [
                'name' => $this->primary_contact_name,
                'email' => $this->primary_contact_email,
                'phone' => $this->primary_contact_phone,
                'title' => $this->primary_contact_title ?? 'Primary Contact',
            ],
            'secondary' => [
                'name' => $this->secondary_contact_name,
                'email' => $this->secondary_contact_email,
                'phone' => $this->secondary_contact_phone,
                'title' => 'Secondary Contact',
            ],
            'custom' => [
                'name' => $this->custom_admin_name,
                'email' => $this->custom_admin_email,
                'phone' => $this->custom_admin_phone,
                'title' => $this->custom_admin_title ?? 'System Administrator',
            ],
            default => null
        };

        if (!$adminData || empty($adminData['name']) || empty($adminData['email'])) {
            return;
        }

        // Check if user already exists
        $existingUser = \App\Models\User::where('email', $adminData['email'])->first();
        if ($existingUser) {
            throw new \Exception("A user with email {$adminData['email']} already exists in the system.");
        }

        // Generate secure password
        $password = $this->generateSecurePassword();

        // Create person record
        $nameParts = explode(' ', trim($adminData['name']));
        $givenName = $nameParts[0];
        $familyName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $givenName;

        $person = \App\Models\Person::create([
            'id' => Str::uuid(),
            'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
            'global_identifier' => 'PER-' . strtoupper(Str::random(8)),
            'given_name' => $givenName,
            'family_name' => $familyName,
            'classification' => json_encode(['STAFF']),
        ]);

        // Create email address record
        \App\Models\EmailAddress::create([
            'email_id' => 'EML-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'person_id' => $person->id,
            'email_address' => $adminData['email'],
            'email_type' => 'WORK',
            'is_primary' => true,
        ]);

        // Create phone record
        \App\Models\Phone::create([
            'phone_id' => 'PHN-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'person_id' => $person->id,
            'number' => $adminData['phone'], // Fixed: changed from phone_number to number
            'phone_type' => 'WORK',
            'is_primary' => true,
        ]);

        // Create person affiliation
        $affiliation = \App\Models\PersonAffiliation::create([
            'id' => Str::uuid(),
            'affiliation_id' => 'AFF-' . strtoupper(Str::random(6)),
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role_type' => 'SYSTEM_ADMIN', // Using role_type field as string value
            'role_title' => $adminData['title'],
            'status' => 'ACTIVE',
            'start_date' => now(),
        ]);

        // Create user account
        $user = \App\Models\User::create([
            'id' => Str::uuid(),
            'name' => $adminData['name'],
            'email' => $adminData['email'],
            'password' => Hash::make($password),
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'email_verified_at' => now(),
        ]);

        // Assign system roles
        $user->assignRole('Organization Admin');

        // Store password temporarily for notification (in production, use secure method)
        session()->put('admin_temp_password', $password);

        // Send welcome email if enabled
        if ($this->send_welcome_email) {
            if (!$this->isEmailConfigured()) {
                Log::warning("Email not configured properly. Skipping welcome email for: {$adminData['email']}", [
                    'organization_id' => $organization->id,
                    'admin_user_id' => $user->id,
                ]);
                session()->flash('warning', 'Admin user created successfully, but email is not configured. Please share login credentials manually.');
            } else {
                try {
                    Mail::to($adminData['email'])->send(new AdminWelcomeEmail($user, $organization, $password));
                    Log::info("Welcome email sent to system administrator: {$adminData['email']}", [
                        'organization_id' => $organization->id,
                        'admin_user_id' => $user->id,
                    ]);
                } catch (\Exception $emailError) {
                    Log::error("Failed to send welcome email to system administrator: {$adminData['email']}", [
                        'organization_id' => $organization->id,
                        'admin_user_id' => $user->id,
                        'error' => $emailError->getMessage(),
                    ]);
                    // Don't throw exception as user creation was successful, just email failed
                    session()->flash('warning', 'Admin user created successfully, but welcome email could not be sent. Please share login credentials manually.');
                }
            }
        }

        // Log admin creation
        Log::info("System Administrator created for organization: {$organization->legal_name}", [
            'organization_id' => $organization->id,
            'admin_user_id' => $user->id,
            'admin_email' => $adminData['email'],
        ]);

        return $user;
    }

    private function generateSecurePassword($length = 16)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        // Ensure at least one character from each category
        $password .= chr(rand(97, 122)); // lowercase
        $password .= chr(rand(65, 90));  // uppercase
        $password .= chr(rand(48, 57));  // number
        $password .= '!@#$%^&*'[rand(0, 7)]; // special character

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Check if email configuration is properly set up
     */
    private function isEmailConfigured()
    {
        $mailDriver = config('mail.default');

        if ($mailDriver === 'log' || $mailDriver === 'array') {
            return true; // For testing/development environments
        }

        // Check if basic email configuration exists
        if (empty(config('mail.mailers.' . $mailDriver . '.host')) && $mailDriver !== 'sendmail') {
            return false;
        }

        if (empty(config('mail.from.address'))) {
            return false;
        }

        return true;
    }

    public function getCategoriesProperty()
    {
        // Single source of truth: pull categories from department_sub_categories table
        $defaultIcon = 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5';

        return DepartmentSubCategory::query()
            ->where('is_active', true)
            ->with('department:id,name')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($subCategory) use ($defaultIcon) {
                return [
                    $subCategory->name => [
                        'label' => $subCategory->name,
                        'icon' => $defaultIcon,
                        'description' => 'Organizations under ' . ($subCategory->department?->name ?? 'Unknown') . ' department',
                        'department' => $subCategory->department?->name ?? '',
                    ],
                ];
            })
            ->all();
    }

    public function render()
    {
        return view('livewire.organizations.create', [
            'categories' => $this->categories,
        ]);
    }
}
