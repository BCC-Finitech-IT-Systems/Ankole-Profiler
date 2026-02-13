<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Collection;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;

class PersonImportService
{
    private PersonDeduplicationService $deduplicationService;

    public function __construct()
    {
        $this->deduplicationService = new PersonDeduplicationService();
    }

    public function getRoleSpecificTemplateHeaders(string $organizationCategory): array
    {
        // Base headers using database field names
        $baseHeaders = [
            // Personal Information (Person table)
            'given_name' => 'First Name (Required)',
            'middle_name' => 'Middle Name (Optional)',
            'family_name' => 'Last Name (Required)',
            'date_of_birth' => 'Date of Birth (YYYY-MM-DD)',
            'gender' => 'Gender (male/female/other/prefer_not_to_say)',

            // Address Information (Person table)
            'address' => 'Full Address',
            'city' => 'City',
            'district' => 'District/State',
            'country' => 'Country (Default: Uganda)',

            // Contact Information (Related tables)
            'phone' => 'Primary Phone Number',
            'email' => 'Primary Email Address',

            // Identity Information (PersonIdentifier table)
            'national_id' => 'National ID Number',
            'passport_number' => 'Passport Number (Optional)',
            'drivers_license' => 'Driver\'s License (Optional)',
            'professional_license' => 'Professional License (Optional)',

            // Affiliation Information (PersonAffiliation table)
            'role_type' => $this->getRoleTypeDescription($organizationCategory),
            'role_title' => 'Job Title/Position (Optional)',
            'site' => 'Site/Location (Optional)',
            'start_date' => 'Start Date (YYYY-MM-DD)',
        ];

        // Role-specific headers based on organization category
        $roleSpecificHeaders = [];

        switch (strtolower($organizationCategory)) {
            case 'hospital':
                $roleSpecificHeaders = [
                    // Patient-specific fields
                    'patient_number' => 'Patient Number (for patients)',
                    'medical_record_number' => 'Medical Record Number (for patients)',
                    'allergies' => 'Known Allergies (for patients)',
                    'chronic_conditions' => 'Chronic Conditions (for patients)',
                    'emergency_contact_name' => 'Emergency Contact Name (for patients)',
                    'emergency_contact_phone' => 'Emergency Contact Phone (for patients)',

                    // Staff-specific fields
                    'employee_number' => 'Employee Number (for staff)',
                    'department' => 'Department (for staff)',
                    'specialization' => 'Medical Specialization (for doctors)',
                    'license_number' => 'Medical License Number (for medical staff)',
                ];
                break;

            case 'school':
                $roleSpecificHeaders = [
                    // Student-specific fields
                    'student_number' => 'Student Number (for students)',
                    'enrollment_date' => 'Enrollment Date (YYYY-MM-DD) (for students)',
                    'current_class' => 'Current Class/Grade (for students)',
                    'guardian_name' => 'Guardian Name (for students)',
                    'guardian_phone' => 'Guardian Phone (for students)',
                    'guardian_email' => 'Guardian Email (for students)',

                    // Staff-specific fields
                    'employee_number' => 'Employee Number (for staff)',
                    'department' => 'Department (for staff)',
                    'teaching_subjects' => 'Teaching Subjects (for teachers)',
                    'qualifications' => 'Educational Qualifications (for staff)',
                ];
                break;

            case 'sacco':
                $roleSpecificHeaders = [
                    // Member-specific fields
                    'membership_number' => 'Membership Number',
                    'join_date' => 'Join Date (YYYY-MM-DD)',
                    'share_capital' => 'Initial Share Capital (Amount)',
                    'savings_account_ref' => 'Savings Account Reference',
                    'next_of_kin_name' => 'Next of Kin Name',
                    'next_of_kin_phone' => 'Next of Kin Phone',
                    'occupation' => 'Occupation',
                    'monthly_income' => 'Monthly Income (Amount)',

                    // Staff-specific fields
                    'employee_number' => 'Employee Number (for staff)',
                    'salary' => 'Salary (for staff)',
                ];
                break;

            case 'parish':
                $roleSpecificHeaders = [
                    // Member-specific fields
                    'member_number' => 'Member Number',
                    'baptism_date' => 'Baptism Date (YYYY-MM-DD)',
                    'confirmation_date' => 'Confirmation Date (YYYY-MM-DD)',
                    'church_group' => 'Church Group/Ministry',
                    'marital_status' => 'Marital Status',
                    'spouse_name' => 'Spouse Name (if married)',
                    'children_count' => 'Number of Children',

                    // Clergy-specific fields
                    'ordination_date' => 'Ordination Date (YYYY-MM-DD) (for clergy)',
                ];
                break;

            case 'corporate':
            case 'government':
            case 'ngo':
            default:
                $roleSpecificHeaders = [
                    // Employee-specific fields
                    'employee_number' => 'Employee Number',
                    'department' => 'Department',
                    'hire_date' => 'Hire Date (YYYY-MM-DD)',
                    'salary' => 'Salary (Amount)',
                    'supervisor_name' => 'Supervisor Name',
                    'work_location' => 'Work Location',
                ];
                break;
        }

        return array_merge($baseHeaders, $roleSpecificHeaders);
    }
    /**
     * Get role type description with valid options for organization category
     */
    private function getRoleTypeDescription(string $organizationCategory): string
    {
        $roleOptions = match (strtolower($organizationCategory)) {
            'hospital' => 'Role Type (PATIENT, DOCTOR, NURSE, STAFF, ADMIN)',
            'school' => 'Role Type (STUDENT, TEACHER, STAFF, ADMIN)',
            'sacco' => 'Role Type (MEMBER, STAFF, ADMIN, BOARD_MEMBER)',
            'parish' => 'Role Type (MEMBER, CLERGY, STAFF, ADMIN)',
            'corporate', 'government', 'ngo' => 'Role Type (EMPLOYEE, MANAGER, ADMIN)',
            default => 'Role Type (STAFF, ADMIN)'
        };

        return $roleOptions . ' (Required)';
    }

    /**
     * Generate CSV template file for organization category
     */
    public function generateTemplateFile(string $organizationCategory, string $organizationName = null): string
    {
        $headers = $this->getRoleSpecificTemplateHeaders($organizationCategory);

        // Initialize filename variable
        $filename = 'person_import_template_' . strtolower($organizationCategory);
        if ($organizationName) {
            $filename .= '_' . str_replace([' ', '/'], '_', strtolower($organizationName));
        }
        $filename .= '_' . date('Y-m-d') . '.csv';

        $filePath = storage_path('app/templates/' . $filename);

        // Ensure directory exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        // Create CSV with headers and sample data
        $file = fopen($filePath, 'w');

        // Write BOM for better Excel compatibility
        fwrite($file, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($file, array_keys($headers));

        // Write description row
        fputcsv($file, array_values($headers));

        // Write sample data based on organization category
        $sampleData = $this->getSampleDataForCategory($organizationCategory);
        foreach ($sampleData as $row) {
            $csvRow = [];
            foreach (array_keys($headers) as $header) {
                $value = $row[$header] ?? '';

                // Format values for better Excel compatibility
                if (in_array($header, ['phone', 'guardian_phone', 'emergency_contact_phone', 'next_of_kin_phone'])) {
                    // Prefix phone numbers with apostrophe to prevent scientific notation
                    $value = $value ? "'" . $value : '';
                } elseif (in_array($header, ['date_of_birth', 'join_date', 'enrollment_date', 'hire_date', 'baptism_date', 'confirmation_date', 'ordination_date'])) {
                    // Ensure dates are properly formatted
                    $value = $value ? date('Y-m-d', strtotime($value)) : '';
                } elseif (in_array($header, ['share_capital', 'salary', 'monthly_income'])) {
                    // Format monetary values
                    $value = $value ? number_format((float)$value, 0, '.', '') : '';
                }

                $csvRow[] = $value;
            }
            fputcsv($file, $csvRow);
        }

        fclose($file);

        return $filePath;
    }

    /**
     * Generate Excel template file for organization category
     */
    public function generateExcelTemplateFile(string $organizationCategory, string $organizationName = null): string
    {
        // Define headers based on validated fields from CreatePersonsComponent
        $headers = [
            'given_name' => 'First Name (Required)',
            'family_name' => 'Last Name (Required)',
            'date_of_birth' => 'Date of Birth (YYYY-MM-DD)',
            'gender' => 'Gender (Male/Female)',
            'phone' => 'Primary Phone Number (Unique)',
            'email' => 'Primary Email Address (Unique)',
            'address' => 'Full Address',
            'country' => 'Country',
            'district' => 'District',
            'city' => 'City',
            'role_title' => 'Job Title/Position',
        ];

        // Sanitize organization category and name to remove invalid characters
        $organizationCategory = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $organizationCategory);
        if ($organizationName) {
            $organizationName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $organizationName);
        }

        // Further enhance sanitization to remove any remaining invalid characters
        $organizationCategory = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $organizationCategory);
        $organizationCategory = preg_replace('/_+/', '_', trim($organizationCategory, '_'));

        if ($organizationName) {
            $organizationName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $organizationName);
            $organizationName = preg_replace('/_+/', '_', trim($organizationName, '_'));
        }

        $filename = 'person-import-template-' . strtolower($organizationCategory);
        if ($organizationName) {
            $filename .= '-' . $organizationName;
        }
        $filename .= '-' . date('Y-m-d') . '.xlsx';

        $storageRelativePath = 'templates/' . $filename;
        $filePath = storage_path('app/' . $storageRelativePath);

        // Validate only filename
        $baseName = basename($filePath);
        if (preg_match('/[\\:*?"<>|]/', $baseName)) {
            throw new \Exception("Invalid characters detected in filename: {$baseName}");
        }

        // Ensure directory exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        // Prepare data for Excel export
        $excelData = [];

        // Add headers row
        $excelData[] = array_keys($headers);

        // Add description row
        $excelData[] = array_values($headers);

        // Save file properly
        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\PersonTemplateExport($excelData, $headers),
            $storageRelativePath,
            'local'
        );

        if (!file_exists($filePath)) {
            throw new \Exception("Failed to create Excel template file at: {$filePath}");
        }

        return $filePath;
    }

    /**
     * Get sample data for organization category
     */
    private function getSampleDataForCategory(string $organizationCategory): array
    {
        switch (strtolower($organizationCategory)) {
            case 'hospital':
                return [
                    [
                        // Personal Information - using database field names
                        'given_name' => 'John',
                        'middle_name' => 'Michael',
                        'family_name' => 'Doe',
                        'date_of_birth' => '1985-01-15',
                        'gender' => 'male',

                        // Address Information
                        'address' => '123 Main Street, Kololo',
                        'city' => 'Kampala',
                        'district' => 'Central',
                        'country' => 'Uganda',

                        // Contact Information - using database field names
                        'phone' => '256701234567',
                        'email' => 'john.doe@email.com',

                        // Identity Information
                        'national_id' => 'CM12345678',
                        'passport_number' => '',
                        'drivers_license' => '',
                        'professional_license' => '',

                        // Affiliation Information
                        'role_type' => 'PATIENT',
                        'role_title' => '',
                        'site' => 'Main Hospital',
                        'start_date' => '2025-01-15',

                        // Domain-specific fields
                        'patient_number' => 'P001234',
                        'medical_record_number' => 'MR001234',
                        'allergies' => 'Penicillin',
                        'chronic_conditions' => '',
                        'emergency_contact_name' => 'Jane Doe',
                        'emergency_contact_phone' => '256701234568',
                    ]
                ];

            case 'school':
                return [
                    [
                        // Personal Information - using database field names
                        'given_name' => 'Mary',
                        'middle_name' => 'Grace',
                        'family_name' => 'Johnson',
                        'date_of_birth' => '2010-03-12',
                        'gender' => 'female',

                        // Address Information
                        'address' => '789 School Road, Entebbe',
                        'city' => 'Entebbe',
                        'district' => 'Wakiso',
                        'country' => 'Uganda',

                        // Contact Information
                        'phone' => '256701234570',
                        'email' => '',

                        // Identity Information
                        'national_id' => '',
                        'passport_number' => '',
                        'drivers_license' => '',
                        'professional_license' => '',

                        // Affiliation Information
                        'role_type' => 'STUDENT',
                        'role_title' => '',
                        'site' => 'Main Campus',
                        'start_date' => '2024-01-15',

                        // Domain-specific fields
                        'student_number' => 'STU2024001',
                        'enrollment_date' => '2024-01-15',
                        'current_class' => 'Primary 6',
                        'guardian_name' => 'Robert Johnson',
                        'guardian_phone' => '256701234571',
                        'guardian_email' => 'robert.johnson@email.com',
                    ]
                ];

            case 'sacco':
                return [
                    [
                        // Personal Information
                        'given_name' => 'Peter',
                        'middle_name' => 'Paul',
                        'family_name' => 'Mukasa',
                        'date_of_birth' => '1975-12-05',
                        'gender' => 'male',

                        // Address Information
                        'address' => 'Plot 15, Katete Road',
                        'city' => 'Mbarara',
                        'district' => 'Mbarara',
                        'country' => 'Uganda',

                        // Contact Information
                        'phone' => '256701234573',
                        'email' => 'peter.mukasa@email.com',

                        // Identity Information
                        'national_id' => 'CM95123456789123',
                        'passport_number' => 'B1234567',
                        'drivers_license' => 'DL789012',

                        // Affiliation Information
                        'role_type' => 'MEMBER',
                        'role_title' => 'SACCO Member',
                        'site' => 'Main Branch',
                        'start_date' => '2024-01-01',

                        // Domain-specific fields
                        'membership_number' => 'MEM001',
                        'join_date' => '2024-01-01',
                        'share_capital' => '500000',
                        'savings_account_ref' => 'SAV001',
                        'next_of_kin_name' => 'Grace Mukasa',
                        'next_of_kin_phone' => '256701234574',
                        'occupation' => 'Farmer',
                        'monthly_income' => '800000',
                    ]
                ];

            case 'parish':
                return [
                    [
                        // Personal Information
                        'given_name' => 'Grace',
                        'middle_name' => 'Mary',
                        'family_name' => 'Namubiru',
                        'date_of_birth' => '1990-07-18',
                        'gender' => 'female',

                        // Address Information
                        'address' => 'Plot 22, Church Street',
                        'city' => 'Jinja',
                        'district' => 'Jinja',
                        'country' => 'Uganda',

                        // Contact Information
                        'phone' => '256701234575',
                        'email' => 'grace.namubiru@email.com',

                        // Identity Information
                        'national_id' => 'CM90071812345123',
                        'passport_number' => 'B2345678',
                        'drivers_license' => 'DL890123',

                        // Affiliation Information
                        'role_type' => 'PARISHIONER',
                        'role_title' => 'Parish Member',
                        'site' => 'St. Mary Parish',
                        'start_date' => '2000-04-15',

                        // Domain-specific fields
                        'member_number' => 'PAR001',
                        'baptism_date' => '2000-04-15',
                        'confirmation_date' => '2005-05-20',
                        'church_group' => 'Youth Ministry',
                        'marital_status' => 'married',
                        'spouse_name' => 'Paul Namubiru',
                        'children_count' => '2',
                    ]
                ];

            default:
                return [
                    [
                        // Personal Information
                        'given_name' => 'David',
                        'middle_name' => 'James',
                        'family_name' => 'Wilson',
                        'date_of_birth' => '1988-11-25',
                        'gender' => 'male',

                        // Address Information
                        'address' => 'Plot 45, Business District',
                        'city' => 'Kampala',
                        'district' => 'Kampala',
                        'country' => 'Uganda',

                        // Contact Information
                        'phone' => '256701234576',
                        'email' => 'david.wilson@company.com',

                        // Identity Information
                        'national_id' => 'CM88112512345123',
                        'passport_number' => 'B3456789',
                        'drivers_license' => 'DL901234',

                        // Affiliation Information
                        'role_type' => 'EMPLOYEE',
                        'role_title' => 'Senior Accountant',
                        'site' => 'Head Office',
                        'start_date' => '2024-01-01',

                        // Domain-specific fields
                        'employee_number' => 'EMP001',
                        'department' => 'Finance',
                        'hire_date' => '2024-01-01',
                        'salary' => '2000000',
                        'supervisor_name' => 'Jane Manager',
                        'work_location' => 'Head Office',
                    ]
                ];
        }
    }

    /**
     * Preview import file and return sample data with validation
     */
    public function previewImport(string $filePath): array
    {
        $data = $this->parseFile($filePath);
        $errors = [];

        // Validate headers
        $requiredHeaders = ['given_name', 'family_name'];
        $headers = array_keys($data->first() ?? []);

        foreach ($requiredHeaders as $required) {
            if (!in_array($required, $headers)) {
                $errors[] = "Missing required column: {$required}";
            }
        }

        // Validate first 50 rows for preview
        $previewData = $data->take(50)->map(function ($row, $index) {
            $validationErrors = $this->validateRow($row, $index + 3); // Account for header + description row
            if (!empty($validationErrors)) {
                $row['_validation_errors'] = $validationErrors;
            }
            return $row;
        })->toArray();

        return [
            'data' => $previewData,
            'errors' => $errors,
            'total_rows' => $data->count()
        ];
    }

    /**
     * Import persons from file using maatwebsite/excel
     */
    public function import(string $filePath, array $options = []): array
    {
        // Initialize variables from options
        $organizationId = $options['organization_id'] ?? null;
        $defaultRoleType = $options['default_role_type'] ?? 'STAFF';
        $skipDuplicates = $options['skip_duplicates'] ?? true;
        $updateExisting = $options['update_existing'] ?? false;
        $createdBy = $options['created_by'] ?? null;

        // Use current user's organization if not provided
        if (!$organizationId) {
            $currentOrganization = user_current_organization();
            if (!$currentOrganization) {
                throw new \Exception('You are not associated with any organization.');
            }
            $organizationId = $currentOrganization->id;
        }

        $organization = Organization::findOrFail($organizationId);

        // Use maatwebsite/excel with our custom import class
        $import = new \App\Imports\PersonsImport(
            $organization,
            $defaultRoleType,
            $skipDuplicates,
            $updateExisting,
            $createdBy
        );

        Excel::import($import, $filePath);

        return $import->getResults();
    }

    /**
     * Parse CSV or Excel file into collection (for preview)
     */
    private function parseFile(string $filePath): Collection
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->parseCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($filePath);
        }

        throw new \Exception('Unsupported file format. Please use CSV or Excel files.');
    }

    /**
     * Parse CSV file
     */
    private function parseCsv(string $filePath): Collection
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = collect();
        $rowIndex = 0;
        foreach ($csv as $record) {
            $rowIndex++;

            // Skip description row (row 2) that contains instruction text
            if ($rowIndex === 1 && $this->isDescriptionRow(array_values($record))) {
                continue;
            }

            $records->push($record);
        }

        return $records;
    }

    /**
     * Check if row contains description/instruction text
     */
    private function isDescriptionRow(array $row): bool
    {
        $firstValue = $row[0] ?? '';

        // Check if first column contains typical description patterns
        return str_contains($firstValue, '(Required)') ||
            str_contains($firstValue, '(Optional)') ||
            str_contains($firstValue, 'YYYY-MM-DD') ||
            str_contains($firstValue, 'First Name');
    }

    /**
     * Parse Excel file (for preview)
     */
    private function parseExcel(string $filePath): Collection
    {
        // Use maatwebsite/excel to read the Excel file
        $collection = Excel::toCollection(null, $filePath)->first();

        if ($collection->isEmpty()) {
            throw new \Exception('Excel file is empty or could not be read.');
        }

        // Get headers from first row
        $headers = $collection->first()->toArray();

        // Convert remaining rows to associative arrays, skipping description row
        $data = collect();
        foreach ($collection->skip(2) as $row) { // Skip header (row 1) and description (row 2)
            $rowArray = $row->toArray();

            // Skip empty rows
            if (empty(array_filter($rowArray, function ($value) {
                return !is_null($value) && $value !== '';
            }))) {
                continue;
            }

            $associativeRow = [];

            foreach ($headers as $index => $header) {
                $associativeRow[$header] = $rowArray[$index] ?? null;
            }

            $data->push($associativeRow);
        }

        return $data;
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // Trim all values in the row
        $row = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $row);

        // Required fields
        if (empty($row['given_name'])) {
            $errors[] = "Given name is required";
        }

        if (empty($row['family_name'])) {
            $errors[] = "Family name is required";
        }

        // Email validation
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Date validation
        if (!empty($row['date_of_birth'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $row['date_of_birth']);
            if (!$date || $date->format('Y-m-d') !== $row['date_of_birth']) {
                $errors[] = "Invalid date format for date_of_birth (use YYYY-MM-DD)";
            }
        }

        // Gender validation
        if (!empty($row['gender']) && !in_array(strtolower($row['gender']), ['male', 'female', 'other', 'prefer_not_to_say'])) {
            $errors[] = "Invalid gender value";
        }

        return $errors;
    }
}
