<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\OrganizationSite;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Organization
        $superOrg = Organization::updateOrCreate(
            ['legal_name' => 'Ankole Profiler Super Organization'],
            [
                'display_name' => 'Ankole Diocese',
                'code' => 'SUPER001',
                'category' => 'corporate',
                'registration_number' => 'SUPER-2025-001',
                'tax_identification_number' => 'TIN-SUPER001',
                'country_of_registration' => 'UGA',
                'date_established' => '2025-01-01',
                'website_url' => 'https://ankoleprofiler.com',
                'contact_email' => 'hq@ankoleprofiler.com',
                'contact_phone' => '+256700000001',
                'description' => 'The main super Organization for Ankole Profiler.',
                'address_line_1' => 'Main Street',
                'city' => 'Mbarara',
                'district' => 'Mbarara',
                'country' => 'UGA',
                'regulatory_body' => 'Ankole Profiler Board',
                'license_number' => 'APB-SUPER-001',
                'license_issue_date' => '2025-01-01',
                'license_expiry_date' => '2030-12-31',
                'accreditation_status' => 'ACCREDITED',
                'primary_contact_name' => 'Joanita Asasira',
                'primary_contact_title' => 'Head of IT',
                'primary_contact_email' => 'joanita.asasira@ankoleprofiler.com',
                'primary_contact_phone' => '+256781507659',
                'bank_name' => 'Bank of Africa',
                'default_currency' => 'UGX',
                'timezone' => 'Africa/Kampala',
                'corporate_details' => [
                    'corporate_type' => 'super_Organization',
                    'ownership_type' => 'private',
                    'number_of_branches' => 0,
                ],
                'organization_type' => 'super',
                'is_super' => 1,
            ]
        );

        echo "Sample organizations created successfully!\n";
    }
}
