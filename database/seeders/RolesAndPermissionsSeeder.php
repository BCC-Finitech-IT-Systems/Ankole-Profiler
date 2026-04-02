<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
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

            // Organization unit permissions
            'assign-organization-unit',
            'review-organization-units',

            // Communication permissions
            'send-communications',
            'view-communications',
            'manage-communications',

            // Organization permissions
            'view-organizations',
            'view-organizations-hierarchy',
            'create-organizations',
            'edit-organizations',
            'delete-organizations',
            'view-own-organization',
            'view-sites',
            'create-sites',
            'view-own-sites',
            'view-units',
            'view-own-units',
            'create-units',
            'view-organization-units',

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
            'support-persons',

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

            // Site management permissions
            'manage-sites',
            'import-organizations',
            'export-organizations',

            // Department and project permissions
            'view-departments',
            'view-departments-dashboard',
            'create-departments',
            'edit-departments',
            'delete-departments',
            'assign-department-admins',
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'assign-project-admins',
            'manage-project-persons',
            'view-project-relationships',
            'view-project-stats',
            'send-project-communications',
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
            'create-units',
            'view-org-persons',
            'create-org-persons',
            'import-org-persons',
            'export-org-persons',
            'delete-persons',
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
            'view-departments',
            'view-departments-dashboard',
            'create-departments',
            'edit-departments',
            'delete-departments',
            'assign-department-admins',
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'assign-project-admins',
            'manage-project-persons',
            'view-project-relationships',
            'view-project-stats',
            'send-project-communications',
            'send-communications',
            'view-communications',
        ]);

        $deptManager = Role::firstOrCreate(['name' => 'Department Manager']);
        $deptManager->syncPermissions([
            'view-dashboard',
            'view-departments-dashboard',
            'view-dept-team',
            'manage-dept-team',
            'view-dept-staff',
            'view-dept-students',
            'view-dept-patients',
            'view-phones',
            'view-emails',
            'view-addresses',
            'view-reports',
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'assign-project-admins',
            'manage-project-persons',
            'view-project-relationships',
            'view-project-stats',
            'send-project-communications',
        ]);

        $projectAdmin = Role::firstOrCreate(['name' => 'Project Admin']);
        $projectAdmin->syncPermissions([
            'view-dashboard',
            'view-projects',
            'manage-project-persons',
            'view-project-relationships',
            'view-project-stats',
            'send-project-communications',
            'view-persons',
            'create-persons',
            'edit-persons',
            'view-affiliations',
            'create-affiliations',
            'edit-affiliations',
            'view-phones',
            'view-emails',
            'view-addresses',
        ]);

        $staff = Role::firstOrCreate(['name' => 'Staff']);
        $staff->syncPermissions([
            'view-dashboard',
            'view-projects',
            'view-persons',
            'view-affiliations',
            'view-project-relationships',
            'view-phones',
            'view-emails',
            'view-addresses',
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
            'support-persons',
            'view-org-persons',
            'edit-persons',
            'support-persons',
            'view-affiliations',
            'view-organization-units',
            // Do NOT include: 'edit-units', 'delete-units', 'move-units'
        ]);
    }
}
