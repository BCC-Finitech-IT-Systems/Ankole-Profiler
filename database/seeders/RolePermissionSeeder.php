<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
    $permissions = [
            // Unit management permissions
            'edit-units',
            'delete-units',
            'move-units',
            'approve-unit-membership',
            'bulk-approve-unit-membership',
            // Dashboard permissions
            'view-dashboard',
            'view-analytics',
            'view-org-analytics',
            'view-risk-overview',

            'assign-organization-unit',
            'review-organization-units',

            // communication
            'send-communications',
            'view-communications',
            'manage-communications',

            // Organization permissions
            'view-Organizations',
            'view-Organizations-hierarchy',
            'create-Organizations',
            'edit-Organizations',
            'delete-Organizations',
            'view-own-Organization',
            'view-sites',
            'create-sites',
            'view-own-sites',
            'view-units',
            'view-own-units',
            'create-units',
            'view-Organization-units',


            // Person permissions
            'view-persons',
            'create-persons',
            'edit-persons',
            'delete-persons',
            'import-persons',
            'export-persons',
            'view-org-persons',
            'create-org-persons',
            'import-org-persons',
            'export-org-persons',
            'merge-persons',
            'manage-duplicates',
            'verify-persons',
            'view-persons-document',
            'edit-persons-document',
            'delete-persons-document',
            'Support-persons',

            // Affiliation permissions
            'view-affiliations',
            'create-affiliations',
            'edit-affiliations',
            'delete-affiliations',
            'view-org-affiliations',
            'create-org-affiliations',

            // Domain-specific permissions
            'view-staff',
            'view-students',
            'view-patients',
            'view-sacco-members',
            'view-parish-members',
            'view-dept-team',
            'manage-dept-team',
            'view-dept-staff',
            'view-dept-students',
            'view-dept-patients',

            // Contact permissions
            'view-phones',
            'view-emails',
            'view-addresses',
            'send-sms',
            'send-email',

            // Financial permissions
            'view-financial-profiles',
            'view-bank-accounts',
            'view-mobile-money',
            'view-assets',
            'view-liabilities',
            'view-insurance',

            // Compliance permissions
            'manage-consents',
            'view-audit-logs',
            'manage-kyc',
            'manage-blacklist',
            'manage-data-rights',

            // Integration permissions
            'manage-integrations',
            'monitor-sync',
            'manage-api',
            'manage-webhooks',

            // Report permissions
            'view-reports',
            'create-reports',
            'view-org-reports',
            'view-compliance-reports',
            'export-reports',

            // User management permissions
            'manage-users',
            'create-users',
            'manage-roles',
            'manage-access',
            'manage-org-roles',
            'manage-role-types',
            'manage-permissions',

            // Settings permissions
            'manage-settings',
            'manage-data',
            'view-system-health',

            // Task permissions
            'view-tasks',
            'view-own-entries',
            'view-quality-issues',

            'manage-sites',
            'import-Organizations',
            'export-Organizations',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $orgAdmin = Role::firstOrCreate(['name' => 'Organization Admin']);
        $orgAdmin->syncPermissions([
            'view-dashboard',
            'view-org-analytics',
            'view-own-Organization',
            'view-Organizations-hierarchy',
            'view-own-sites',
            'view-own-units',
            'view-org-persons',
            'create-org-persons',
            'import-org-persons',
            'export-org-persons',
            'view-org-affiliations',
            'create-org-affiliations',
            'manage-org-roles',
            'manage-role-types',
            'view-phones',
            'view-emails',
            'view-addresses',
            'manage-consents',
            'view-audit-logs',
            'manage-data-rights',
            'view-org-reports',
            'manage-users',
            'create-users',
            'manage-users',
        ]);

        $deptManager = Role::firstOrCreate(['name' => 'Department Manager']);
        $deptManager->syncPermissions([
            'view-dashboard',
            'view-dept-team',
            'manage-dept-team',
            'view-dept-staff',
            'view-dept-students',
            'view-dept-patients',
            'view-phones',
            'view-emails',
            'view-addresses',
            'view-reports',
        ]);

        $dataEntryClerk = Role::firstOrCreate(['name' => 'Data Entry Clerk']);
        $dataEntryClerk->syncPermissions([
            'view-dashboard',
            'view-persons',
            'create-persons',
            'edit-persons',
            'create-affiliations',
            'view-phones',
            'view-emails',
            'view-addresses',
            'view-tasks',
            'view-own-entries',
            'view-quality-issues',
        ]);

        $complianceOfficer = Role::firstOrCreate(['name' => 'Compliance Officer']);
        $complianceOfficer->syncPermissions([
            'view-dashboard',
            'view-risk-overview',
            'manage-consents',
            'view-audit-logs',
            'manage-kyc',
            'manage-data-rights',
            'view-compliance-reports',
        ]);

        $readOnly = Role::firstOrCreate(['name' => 'Read Only']);
        $readOnly->syncPermissions([
            'view-dashboard',
            'view-persons',
            'view-affiliations',
            'view-phones',
            'view-emails',
            'view-addresses',
            'view-reports',
            'export-reports',
        ]);

        // Person role and permissions
        $personRole = Role::firstOrCreate(['name' => 'Person']);
        $personRole->syncPermissions([
            'view-persons',
            'view-persons-document',
            'edit-persons-document',
            'delete-persons-document',
            'Support-persons',
            'view-org-persons',
            'edit-persons',
            'Support-persons',
            'view-affiliations',
            'view-Organization-units',
            // Do NOT include: 'edit-units', 'delete-units', 'move-units'
        ]);
    }
}
