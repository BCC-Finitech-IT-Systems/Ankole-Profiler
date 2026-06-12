<?php

namespace App\Imports;

use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\Phone;
use App\Models\EmailAddress;
use App\Models\PersonIdentifier;
use App\Models\Organization;
use App\Services\PersonDeduplicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;

class PersonsImport implements ToCollection, WithHeadingRow, WithValidation, WithStartRow
{
    use Importable;

    private Organization $organization;
    private string $defaultRoleType;
    private bool $skipDuplicates;
    private bool $updateExisting;
    private ?int $createdBy;
    private PersonDeduplicationService $deduplicationService;

    public array $results = [
        'summary' => [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ],
        'details' => []
    ];

    public function __construct(
        Organization $organization,
        string $defaultRoleType = 'STAFF',
        bool $skipDuplicates = true,
        bool $updateExisting = false,
        ?int $createdBy = null
    ) {
        $this->organization = $organization;
        $this->defaultRoleType = $defaultRoleType;
        $this->skipDuplicates = $skipDuplicates;
        $this->updateExisting = $updateExisting;
        $this->createdBy = $createdBy;
        $this->deduplicationService = new PersonDeduplicationService();
    }

    public function collection(Collection $collection)
    {
        $this->results['summary']['total'] = $collection->count();

        DB::beginTransaction();

        try {
            foreach ($collection as $index => $row) {
                $rowNumber = $index + 3; // Account for header row (1) + description row (2) + 0-based index

                try {
                    Log::info("PersonImport: Processing row {$rowNumber}", [
                        'row_data' => $row->toArray()
                    ]);

                    $result = $this->processRow($row->toArray(), $rowNumber);

                    $this->results['details'][] = [
                        'row' => $rowNumber,
                        'name' => trim(($row['given_name'] ?? '') . ' ' . ($row['family_name'] ?? '')),
                        'status' => $result['status'],
                        'message' => $result['message']
                    ];

                    $this->results['summary'][$result['status']]++;

                } catch (\Exception $e) {
                    $this->results['details'][] = [
                        'row' => $rowNumber,
                        'name' => trim(($row['given_name'] ?? '') . ' ' . ($row['family_name'] ?? '')),
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];
                    $this->results['summary']['failed']++;

                    Log::error("PersonImport: Row {$rowNumber} failed: " . $e->getMessage(), [
                        'row' => $row->toArray(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PersonImport: Transaction failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Skip the first two rows and start from row 3
     * Row 1: Headers (given_name, family_name, phone, email, etc.)
     * Row 2: Description/Instructions
     * Row 3+: Actual data
     */
    public function startRow(): int
    {
        return 3;
    }

    public function rules(): array
    {
        $baseRules = [
            'given_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone' => 'nullable|max:20', // Changed from 'primary_phone'
            'email' => 'nullable|email|max:255', // Changed from 'primary_email'
            'national_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'role_type' => 'nullable|string|max:50',
            'role_title' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:255',
            'start_date' => 'nullable|date_format:Y-m-d',
        ];

        // Add organization-specific validation rules
        $categoryRules = $this->getCategorySpecificRules($this->organization->category);

        return array_merge($baseRules, $categoryRules);
    }

    private function getCategorySpecificRules(string $category): array
    {
        switch (strtolower($category)) {
            case 'hospital':
                return [
                    // Patient-specific fields
                    'patient_number' => 'nullable|string|max:50',
                    'medical_record_number' => 'nullable|string|max:50',
                    'allergies' => 'nullable|string|max:1000',
                    'chronic_conditions' => 'nullable|string|max:1000',
                    'emergency_contact_name' => 'nullable|string|max:255',
                    'emergency_contact_phone' => 'nullable|max:20',

                    // Staff-specific fields
                    'employee_number' => 'nullable|string|max:50',
                    'department' => 'nullable|string|max:255',
                    'specialization' => 'nullable|string|max:255',
                    'license_number' => 'nullable|string|max:100',
                ];

            case 'school':
                return [
                    // Student-specific fields
                    'student_number' => 'nullable|string|max:50',
                    'enrollment_date' => 'nullable|date_format:Y-m-d',
                    'current_class' => 'nullable|string|max:100',
                    'guardian_name' => 'nullable|string|max:255',
                    'guardian_phone' => 'nullable|max:20',
                    'guardian_email' => 'nullable|email|max:255',

                    // Staff-specific fields
                    'employee_number' => 'nullable|string|max:50',
                    'department' => 'nullable|string|max:255',
                    'teaching_subjects' => 'nullable|string|max:500',
                    'qualifications' => 'nullable|string|max:1000',
                ];

            case 'sacco':
                return [
                    // Member-specific fields
                    'membership_number' => 'nullable|string|max:50',
                    'join_date' => 'nullable|date_format:Y-m-d',
                    'share_capital' => 'nullable|numeric|min:0',
                    'savings_account_ref' => 'nullable|string|max:100',
                    'next_of_kin_name' => 'nullable|string|max:255',
                    'next_of_kin_phone' => 'nullable|max:20',
                    'occupation' => 'nullable|string|max:255',
                    'monthly_income' => 'nullable|numeric|min:0',

                    // Staff-specific fields
                    'employee_number' => 'nullable|string|max:50',
                    'salary' => 'nullable|numeric|min:0',
                ];

            case 'parish':
                return [
                    // Member-specific fields
                    'member_number' => 'nullable|string|max:50',
                    'baptism_date' => 'nullable|date_format:Y-m-d',
                    'confirmation_date' => 'nullable|date_format:Y-m-d',
                    'church_group' => 'nullable|string|max:255',
                    'marital_status' => 'nullable|in:single,married,divorced,widowed',
                    'spouse_name' => 'nullable|string|max:255',
                    'children_count' => 'nullable|integer|min:0',

                    // Clergy-specific fields
                    'ordination_date' => 'nullable|date_format:Y-m-d',
                ];

            case 'corporate':
            case 'government':
            case 'ngo':
            default:
                return [
                    // Employee-specific fields
                    'employee_number' => 'nullable|string|max:50',
                    'department' => 'nullable|string|max:255',
                    'hire_date' => 'nullable|date_format:Y-m-d',
                    'salary' => 'nullable|numeric|min:0',
                    'supervisor_name' => 'nullable|string|max:255',
                    'work_location' => 'nullable|string|max:255',
                ];
        }
    }

    private function processRow(array $row, int $rowNumber): array
    {
        // Clean up the row data - remove apostrophes from phone numbers
        $row = array_map(function($value) {
            if (is_string($value)) {
                // Remove leading apostrophe from phone numbers
                return ltrim(trim($value), "'");
            }
            return $value;
        }, $row);

        Log::info("PersonImport: Cleaned row data", [
            'row' => $rowNumber,
            'phone' => $row['phone'] ?? 'not set',
            'email' => $row['email'] ?? 'not set'
        ]);

        // Check for duplicates
        $duplicateCheck = $this->deduplicationService->findPotentialDuplicates($row);
        $highConfidenceMatch = $duplicateCheck->first(function ($match) {
            return $match['confidence'] === 'high' && $match['similarity'] > 85;
        });

        if ($highConfidenceMatch) {
            $existingPerson = Person::find($highConfidenceMatch['person']['id']);

            if ($this->skipDuplicates && !$this->updateExisting) {
                // Just create affiliation if not exists
                $existingAffiliation = PersonAffiliation::where('person_id', $existingPerson->id)
                    ->where('organization_id', $this->organization->id)
                    ->first();

                if (!$existingAffiliation) {
                    $this->createAffiliation($existingPerson, $row);
                    return [
                        'status' => 'success',
                        'message' => 'Linked existing person to organization'
                    ];
                } else {
                    return [
                        'status' => 'skipped',
                        'message' => 'Person already exists with affiliation'
                    ];
                }
            }

            if ($this->updateExisting) {
                $this->updatePerson($existingPerson, $row);
                $this->createContactInformation($existingPerson, $row);
                $this->createAffiliation($existingPerson, $row);
                return [
                    'status' => 'success',
                    'message' => 'Updated existing person'
                ];
            }
        }

        // Create new person
        $person = $this->createPerson($row);
        Log::info("PersonImport: Person created", ['person_id' => $person->id]);

        $this->createContactInformation($person, $row);
        $this->createAffiliation($person, $row);

        return [
            'status' => 'success',
            'message' => 'Created new person'
        ];
    }

    private function createPerson(array $row): Person
    {
        return Person::create([
            'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
            'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier('GID'),
            'given_name' => $row['given_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'family_name' => $row['family_name'],
            'date_of_birth' => $row['date_of_birth'] ?: null,
            'gender' => $row['gender'] ?: null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'district' => $row['district'] ?? null,
            'country' => $row['country'] ?? 'Uganda',
            'classification' => json_encode([$row['role_type'] ?? $this->defaultRoleType]),
            'created_by' => $this->createdBy,
        ]);
    }

    private function updatePerson(Person $person, array $row): void
    {
        $updateData = array_filter([
            'middle_name' => $row['middle_name'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?: null,
            'gender' => $row['gender'] ?: null,
            'address' => $row['address'] ?: null,
            'city' => $row['city'] ?: null,
            'district' => $row['district'] ?: null,
            'country' => $row['country'] ?: null,
            'updated_by' => $this->createdBy,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        if (!empty($updateData)) {
            $person->update($updateData);
        }
    }

    private function createContactInformation(Person $person, array $row): void
    {
        // Create phone (check for duplicates)
        if (!empty($row['phone'])) { // Changed from 'primary_phone'
            $phoneNumber = $this->cleanPhoneNumber($row['phone']);

            Log::info("PersonImport: Creating phone", [
                'person_id' => $person->id,
                'original_phone' => $row['phone'],
                'cleaned_phone' => $phoneNumber
            ]);

            $existingPhone = Phone::where('person_id', $person->id)
                                  ->where('number', $phoneNumber)
                                  ->first();

            if (!$existingPhone) {
                Phone::create([
                    'person_id' => $person->id,
                    'number' => $phoneNumber,
                    'type' => 'mobile',
                    'is_primary' => true,
                    'created_by' => $this->createdBy,
                ]);
                Log::info("PersonImport: Phone created successfully", ['phone' => $phoneNumber]);
            } else {
                Log::info("PersonImport: Phone already exists", ['phone' => $phoneNumber]);
            }
        } else {
            Log::info("PersonImport: No phone provided for person", ['person_id' => $person->id]);
        }

        // Create email (check for duplicates)
        if (!empty($row['email'])) { // Changed from 'primary_email'
            $emailAddress = strtolower(trim($row['email']));

            Log::info("PersonImport: Creating email", [
                'person_id' => $person->id,
                'email' => $emailAddress
            ]);

            $existingEmail = EmailAddress::where('person_id', $person->id)
                                         ->where('email', $emailAddress)
                                         ->first();

            if (!$existingEmail) {
                EmailAddress::create([
                    'person_id' => $person->id,
                    'email' => $emailAddress,
                    'type' => 'personal',
                    'is_primary' => true,
                    'created_by' => $this->createdBy,
                ]);
                Log::info("PersonImport: Email created successfully", ['email' => $emailAddress]);
            } else {
                Log::info("PersonImport: Email already exists", ['email' => $emailAddress]);
            }
        } else {
            Log::info("PersonImport: No email provided for person", ['person_id' => $person->id]);
        }

        // Create national ID
        if (!empty($row['national_id'])) {
            $existingId = PersonIdentifier::where('person_id', $person->id)
                                          ->where('type', 'national_id')
                                          ->where('identifier', $row['national_id'])
                                          ->first();

            if (!$existingId) {
                PersonIdentifier::create([
                    'person_id' => $person->id,
                    'type' => 'national_id',
                    'identifier' => $row['national_id'],
                    'issuing_authority' => 'NIRA',
                    'created_by' => $this->createdBy,
                ]);
                Log::info("PersonImport: National ID created", ['national_id' => $row['national_id']]);
            }
        }
    }

    /**
     * Clean phone number by removing apostrophes and extra characters
     */
    private function cleanPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove apostrophe prefix
        $phone = ltrim($phone, "'");

        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        return $phone;
    }

    private function createAffiliation(Person $person, array $row): void
    {
        // Check if affiliation already exists
        $roleType = $row['role_type'] ?? $this->defaultRoleType;

        $existingAffiliation = PersonAffiliation::where('person_id', $person->id)
            ->where('organization_id', $this->organization->id)
            ->where('role_type', $roleType)
            ->first();

        if ($existingAffiliation) {
            Log::info("PersonImport: Affiliation already exists", [
                'person_id' => $person->id,
                'organization_id' => $this->organization->id,
                'role_type' => $roleType
            ]);
            return; // Don't create duplicate affiliation
        }

        $affiliation = PersonAffiliation::create([
            'person_id' => $person->id,
            'organization_id' => $this->organization->id,
            'role_type' => $roleType,
            'role_title' => $row['role_title'] ?? null,
            'site' => $row['site'] ?? null,
            'start_date' => $row['start_date'] ?? now()->format('Y-m-d'),
            'status' => 'active',
            'created_by' => $this->createdBy,
        ]);

        Log::info("PersonImport: Affiliation created", [
            'affiliation_id' => $affiliation->id,
            'role_type' => $roleType
        ]);

        // Create role-specific records based on organization category and role type
        $this->createRoleSpecificRecord($affiliation, $row);
    }

    private function createRoleSpecificRecord(PersonAffiliation $affiliation, array $row): void
    {
        $roleType = strtoupper($affiliation->role_type);
        $category = strtolower($this->organization->category);

        Log::info("PersonImport: Creating role-specific record", [
            'category' => $category,
            'role_type' => $roleType
        ]);

        switch ($category) {
            case 'hospital':
                if ($roleType === 'PATIENT') {
                    $this->createPatientRecord($affiliation, $row);
                } elseif (in_array($roleType, ['STAFF', 'CONSULTANT', 'VOLUNTEER'])) {
                    $this->createStaffRecord($affiliation, $row);
                }
                break;

            case 'school':
                if ($roleType === 'STUDENT') {
                    $this->createStudentRecord($affiliation, $row);
                } elseif (in_array($roleType, ['STAFF', 'TEACHER', 'ADMINISTRATOR'])) {
                    $this->createStaffRecord($affiliation, $row);
                }
                break;

            case 'sacco':
                if ($roleType === 'MEMBER') {
                    $this->createSaccoMemberRecord($affiliation, $row);
                } elseif ($roleType === 'STAFF') {
                    $this->createStaffRecord($affiliation, $row);
                }
                break;

            case 'parish':
                if (in_array($roleType, ['MEMBER', 'PARISHIONER', 'PARISH_MEMBER'])) {
                    $this->createParishMemberRecord($affiliation, $row);
                } elseif (in_array($roleType, ['STAFF', 'CLERGY'])) {
                    $this->createStaffRecord($affiliation, $row);
                }
                break;

            default:
                // For corporate, government, NGO, etc. - typically just staff records
                $this->createStaffRecord($affiliation, $row);
                break;
        }
    }

    private function createPatientRecord(PersonAffiliation $affiliation, array $row): void
    {
        if (!class_exists(\App\Models\PatientRecord::class)) {
            return;
        }

        $data = array_filter([
            'affiliation_id' => $affiliation->id,
            'patient_number' => $row['patient_number'] ?? null,
            'medical_record_number' => $row['medical_record_number'] ?? null,
            'allergies' => $row['allergies'] ?? null,
            'chronic_conditions' => $row['chronic_conditions'] ?? null,
        ]);

        if (count($data) > 1) { // More than just affiliation_id
            \App\Models\PatientRecord::create($data);
            Log::info("PersonImport: Patient record created", ['affiliation_id' => $affiliation->id]);
        }
    }

    private function createStudentRecord(PersonAffiliation $affiliation, array $row): void
    {
        if (!class_exists(\App\Models\StudentRecord::class)) {
            return;
        }

        $guardianContact = null;
        if (!empty($row['guardian_name'])) {
            $guardianContact = [
                'name' => $row['guardian_name'],
                'phone' => $this->cleanPhoneNumber($row['guardian_phone'] ?? null),
                'email' => $row['guardian_email'] ?? null,
            ];
        }

        $data = array_filter([
            'affiliation_id' => $affiliation->id,
            'student_number' => $row['student_number'] ?? null,
            'enrollment_date' => $row['enrollment_date'] ?? null,
            'current_class' => $row['current_class'] ?? null,
            'guardian_contact' => $guardianContact,
        ]);

        if (count($data) > 1) { // More than just affiliation_id
            \App\Models\StudentRecord::create($data);
            Log::info("PersonImport: Student record created", ['affiliation_id' => $affiliation->id]);
        }
    }

    private function createSaccoMemberRecord(PersonAffiliation $affiliation, array $row): void
    {
        if (!class_exists(\App\Models\SaccoMemberRecord::class)) {
            return;
        }

        $data = array_filter([
            'affiliation_id' => $affiliation->id,
            'membership_number' => $row['membership_number'] ?? null,
            'join_date' => $row['join_date'] ?? null,
            'share_capital' => $row['share_capital'] ?? null,
            'savings_account_ref' => $row['savings_account_ref'] ?? null,
        ]);

        if (count($data) > 1) { // More than just affiliation_id
            \App\Models\SaccoMemberRecord::create($data);
            Log::info("PersonImport: SACCO member record created", ['affiliation_id' => $affiliation->id]);
        }
    }

    private function createParishMemberRecord(PersonAffiliation $affiliation, array $row): void
    {
        if (!class_exists(\App\Models\ParishMemberRecord::class)) {
            return;
        }

        $data = array_filter([
            'affiliation_id' => $affiliation->id,
            'member_number' => $row['member_number'] ?? null,
            'baptism_date' => $row['baptism_date'] ?? null,
            'confirmation_date' => $row['confirmation_date'] ?? null,
            'church_group' => $row['church_group'] ?? null,
            'marital_status' => $row['marital_status'] ?? null,
            'spouse_name' => $row['spouse_name'] ?? null,
            'children_count' => $row['children_count'] ?? null,
        ]);

        if (count($data) > 1) { // More than just affiliation_id
            \App\Models\ParishMemberRecord::create($data);
            Log::info("PersonImport: Parish member record created", ['affiliation_id' => $affiliation->id]);
        }
    }

    private function createStaffRecord(PersonAffiliation $affiliation, array $row): void
    {
        if (!class_exists(\App\Models\StaffRecord::class)) {
            return;
        }

        $data = array_filter([
            'affiliation_id' => $affiliation->id,
            'staff_number' => $row['employee_number'] ?? null,
            'department' => $row['department'] ?? null,
            'employment_type' => $row['employment_type'] ?? null,
            'grade' => $row['grade'] ?? null,
            'contract_start' => $row['hire_date'] ?? $affiliation->start_date,
            'contract_end' => $row['contract_end'] ?? null,
        ]);

        if (count($data) > 1) { // More than just affiliation_id
            \App\Models\StaffRecord::create($data);
            Log::info("PersonImport: Staff record created", ['affiliation_id' => $affiliation->id]);
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
