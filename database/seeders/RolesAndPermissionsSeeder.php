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
            'approve-organization-membership',
            'manage-email-domains',
            'manage-dioceses',
            'manage-settings',

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

            // Policy repository permissions
            'view-policies',
            'create-policies',
            'edit-policies',
            'archive-policies',
            'approve-policies',
            'publish-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'update-policy-adoption',
            'decide-policy-exceptions',
            'view-policy-dashboard',

            // Annual workplan permissions
            'view-workplans',
            'create-workplans',
            'edit-workplans',
            'submit-workplans',
            'approve-workplans',
            'archive-workplans',
            'record-workplan-progress',
            'carry-forward-workplans',
            'upload-workplan-documents',
            'download-workplan-documents',
            'view-workplan-dashboard',

            // Assignment tracking permissions
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'close-assignments',
            'report-assignment-progress',
            'review-assignments',
            'upload-assignment-documents',
            'download-assignment-documents',
            'view-assignment-dashboard',

            // Land registration permissions
            'view-land-parcels',
            'create-land-parcels',
            'edit-land-parcels',
            'archive-land-parcels',
            'upload-land-documents',
            'download-land-documents',
            'manage-land-disputes',
            'view-land-dashboard',

            // Audit report register permissions
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'archive-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
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
            'view-own-organization',
            'view-organizations-hierarchy',
            'view-own-sites',
            'view-own-units',
            'create-organizations', // add institutions under the diocese they administer
            'create-units',
            'edit-units',
            'approve-unit-membership',
            'bulk-approve-unit-membership',
            'approve-organization-membership',
            'manage-dioceses', // edit own diocese only; create is Super Admin-only in the component
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
            // Policy repository: diocese-side admins draft/approve/publish;
            // institution-side admins are scoped to their own org by the
            // Policy classes, not by permission (no separate role for them).
            'view-policies',
            'create-policies',
            'edit-policies',
            'archive-policies',
            'approve-policies',
            'publish-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'update-policy-adoption',
            'decide-policy-exceptions',
            'view-policy-dashboard',
            // Annual workplans: diocese/org-level admins approve; when
            // scoped to their own department they also draft, matching the
            // Policy Repository's institution-side dual-role convention.
            'view-workplans',
            'create-workplans',
            'edit-workplans',
            'submit-workplans',
            'approve-workplans',
            'archive-workplans',
            'record-workplan-progress',
            'carry-forward-workplans',
            'upload-workplan-documents',
            'download-workplan-documents',
            'view-workplan-dashboard',
            // Assignment tracking: full access including review (diocese/org
            // admins act as reviewer since they're outside the drafting
            // department).
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'close-assignments',
            'report-assignment-progress',
            'review-assignments',
            'upload-assignment-documents',
            'download-assignment-documents',
            'view-assignment-dashboard',
            // Land registration: full access including dispute management
            // (org-level admins are the higher authority for legally
            // sensitive dispute decisions).
            'view-land-parcels',
            'create-land-parcels',
            'edit-land-parcels',
            'archive-land-parcels',
            'upload-land-documents',
            'download-land-documents',
            'manage-land-disputes',
            'view-land-dashboard',
            // Audit report register: full access, including archiving
            // (org-level admins are the higher authority for governance
            // records, matching the land-dispute convention above).
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'archive-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
        ]);

        $deptManager = Role::firstOrCreate(['name' => 'Department Manager']);
        $deptManager->syncPermissions([
            'view-dashboard',
            'view-departments-dashboard',
            'view-dept-team',
            'manage-dept-team',
            'edit-units',
            'approve-unit-membership',
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
            // Policy repository: can draft/edit and view adoption, but not
            // approve/publish/decide exceptions (drafting is not approval).
            'view-policies',
            'create-policies',
            'edit-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'view-policy-dashboard',
            // Annual workplans: drafts, submits, records progress, and
            // carries forward — no approve (separation of duties).
            'view-workplans',
            'create-workplans',
            'edit-workplans',
            'submit-workplans',
            'record-workplan-progress',
            'carry-forward-workplans',
            'upload-workplan-documents',
            'download-workplan-documents',
            'view-workplan-dashboard',
            // Assignment tracking: draft/manage/close/report progress, but
            // no review (separation of duties — a manager's own department
            // cannot review its own submitted work).
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'close-assignments',
            'report-assignment-progress',
            'upload-assignment-documents',
            'download-assignment-documents',
            'view-assignment-dashboard',
            // Land registration: manage records, but not dispute decisions
            // (manage-land-disputes is org-scope only, per the policy).
            'view-land-parcels',
            'create-land-parcels',
            'edit-land-parcels',
            'upload-land-documents',
            'download-land-documents',
            'view-land-dashboard',
            // Audit report register: drafts/edits reports, but no archive
            // (separation of duties, same shape as land registration above).
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
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
            'view-policies',
            'download-policy-documents',
            'view-workplans',
            'download-workplan-documents',
            'view-assignments',
            'download-assignment-documents',
            'view-land-parcels',
            'download-land-documents',
            'view-audit-reports',
            'download-audit-documents',
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
            'view-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'view-policy-dashboard',
            'view-workplans',
            'download-workplan-documents',
            'view-workplan-dashboard',
            'view-assignments',
            'download-assignment-documents',
            'view-assignment-dashboard',
            'view-land-parcels',
            'download-land-documents',
            'view-land-dashboard',
            // Audit report register: broader than the land-registration
            // subset above — audits are squarely Compliance Officer's
            // domain, so this role also drafts/edits reports, not just
            // views/downloads them.
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
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
            'view-policies',
            'view-policy-adoption',
            'view-workplans',
            'view-assignments',
            'view-assignment-dashboard',
            'view-land-parcels',
            'view-land-dashboard',
            'view-audit-reports',
            'view-audit-dashboard',
        ]);

        // Person role and permissions
        $personRole = Role::firstOrCreate(['name' => 'Person']);
        $personRole->syncPermissions([
            'view-persons',
            'view-persons-document',
            'edit-persons-document',
            'delete-persons-document',
            'view-org-persons',
            'edit-persons',
            'view-affiliations',
            'view-organization-units',
            'view-policies',
            // A plain Person needs these three to act as an assignee on
            // their own assignments — the reportProgress policy still
            // requires them to actually be the lead/support on the
            // specific record, so this permission alone grants nothing.
            'view-assignments',
            'report-assignment-progress',
            'download-assignment-documents',
            // Do NOT include: 'edit-units', 'delete-units', 'move-units'
        ]);
    }
}
