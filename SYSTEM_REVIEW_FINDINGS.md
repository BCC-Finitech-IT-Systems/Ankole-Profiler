# System Review Findings

Date: 2026-06-12

This document consolidates the recent review passes around project management, organization/department modeling, sidebar visibility, and public registration.

## Expected Structure

The system model should remain consistent around this hierarchy:

- `Organization` owns `Departments` and `OrganizationUnits`.
- `Department` owns `Projects`, `RoleTypes`, and department sub-categories.
- `Project` belongs to one `Department`, can optionally belong to one `OrganizationUnit`, and has people through `ProjectAffiliation`.
- `Person` should not directly own an `organization_id`; membership belongs in `PersonAffiliation`.
- Organization scoping should flow through active affiliations, managed organizations, managed departments, and organization tree relationships.

## Review 1: Project And Organization Data Structure

### Confirmed

- Projects are first-class records in the `projects` table.
- Projects do not have their own `organization_id`; they resolve organization through:

  `projects.department_id -> departments.organization_id -> organizations.id`

- The `Project` model also exposes this indirectly through `getOrganizationAttribute()`.
- Organization names appearing beside projects are expected when the page is showing the project owner/context.

### Findings

1. **Projects page was showing organizations because the page/query intentionally loads them**
   - `ProjectController@index` eager-loads `department.organization`.
   - Department dashboard displays project organization and organization category columns.

2. **Departments Dashboard was expanding project results using organization category**
   - Earlier behavior included projects where the selected department had sub-categories whose names matched organization categories.
   - This made the page look organization-driven instead of department/project-driven.
   - This was tightened in commit `5dd8524` so matching stays inside the selected organization tree.

3. **"Projects Profile" was actually an organization profile**
   - The sidebar label pointed to `organizations.current-project`.
   - The component loaded `user_current_organization()`, not a `Project`.
   - The label was corrected to "Organization Profile" in commit `5dd8524`.

4. **Project management exists mostly inside Departments Dashboard**
   - There is no standalone project management UI module.
   - Project listing, creation, editing, and chart access are embedded in `DepartmentsDashboard`.

## Review 2: Broader Inconsistencies And Model Relationships

### High Priority Findings

1. **Org Admin person creation previously allowed arbitrary organization selection**
   - `CreatePersonsComponent` accepted an existing organization ID without verifying it was in the user's department/organization tree scope.
   - This was fixed in commit `5dd8524`.

2. **Project persons route previously bypassed project scope**
   - `/projects/{project}/persons` used a route closure and only checked `can:view-projects`.
   - It now uses `ProjectController@persons`, which calls scoped project authorization.
   - Fixed in commit `5dd8524`.

3. **Organization/project naming was mixed throughout the UI**
   - Super Admin menu used project labels for organization routes.
   - Organization Admin menu used project labels for organization profile.
   - Corrected labels in commit `5dd8524`.

4. **`Person::create()` had stale `organization_id` usage**
   - The person creation flow in `CreatePersonsComponent` was cleaned up to keep organization membership in `PersonAffiliation`.
   - Other paths still need review, especially imports.

### Remaining Relationship Issues

1. **`User::Organization()` relationship is structurally wrong**
   - Current relationship attempts to resolve organization as if `users.id` were `person_affiliations.person_id`.
   - Correct path should be:

     `users.id -> persons.user_id -> person_affiliations.person_id -> organizations.id`

2. **Several helpers still assume `users.organization_id` exists**
   - Examples include organization context middleware/helper code.
   - Current organization should be resolved from session or active affiliations.

3. **`PersonStandardImport` still writes removed `persons.organization_id`**
   - Standard import creates a `Person` with `organization_id`.
   - It should create `Person`, then create a `PersonAffiliation`.

4. **Cross-org relationship scopes use ungrouped `orWhereHas`**
   - `CrossOrgRelationship::scopeForOrganization()` and connection stats can leak prior query constraints.
   - Wrap primary/secondary organization filters in a grouped `where(function (...) { ... })`.

5. **Project person sync validates unit existence but not unit ownership**
   - `SyncProjectPersonsRequest` only checks `organization_unit_id` exists.
   - The controller stores it directly.
   - It should verify the unit belongs to the same department/organization as the project.

6. **Duplicate/legacy organization unit components exist**
   - `app/Livewire/OrganisationUnitsComponent.php`
   - `app/Livewire/Organizations/OrganisationUnitsComponent.php`
   - Both use direct `auth()->user()->organization_id` assumptions and should be consolidated or removed.

## Review 3: Sidebar, Project UI, And Public Registration

### Project Module In Sidebar

Confirmed:

- The project routes still exist:
  - `projects.index`
  - `projects.store`
  - `projects.show`
  - `projects.update`
  - `projects.destroy`
  - `projects.persons`
  - `projects.persons.sync`

- There is no sidebar entry pointing directly to `projects.index` or any `projects.*` management route.

Important context:

- Before commit `5dd8524`, the sidebar showed organization routes using project labels:
  - "Projects Mgt" -> `organizations.*`
  - "All Projects" -> `organizations.index`
  - "Add New Project" -> `organizations.create`
  - "Import Projects" -> `organizations.import`

- These labels were corrected to organization wording.
- This made it clear that a real project module menu item is missing rather than removed.

### Project Management UI

Confirmed:

- There is no dedicated project management Livewire page or Blade index page.
- Project CRUD endpoints mostly return JSON.
- Real project management UI is embedded in the Departments Dashboard:
  - Create Project button
  - Project list table
  - Edit Project modal
  - Project chart controls
  - Link to persons in a project

Additional project UI:

- `resources/views/projects/persons.blade.php` shows persons for one project.

Conclusion:

- Project management exists, but it is buried under Departments Dashboard.
- A standalone Projects module would need a dedicated menu entry and browser UI.

### Public Registration

Confirmed:

- Public registration route exists outside auth middleware:

  `/person/self-register`

- It uses `PersonSelfRegistrationComponent`.
- It renders `resources/views/livewire/person/person-self-registration.blade.php`.

Behavior:

- Applicant selects a Diocese.
- System creates a `User`.
- System creates a `Person`.
- System creates a pending `PersonAffiliation` with `role_type = MEMBER`.
- No role is assigned immediately.
- Email verification is sent.
- Membership becomes active only after diocese admin approval.
- Tests exist in `PersonSelfRegistrationTest`.

Issue:

- The custom login page does not visibly link to `/person/self-register`.
- The feature exists, but it is not discoverable from the public login UI.

## Completed Fixes

Committed and pushed:

`5dd8524 Tighten organization project scoping`

Included:

- Scoped `/projects/{project}/persons` through `ProjectController`.
- Tightened department dashboard organization tree scoping.
- Restricted project creation/editing in dashboard to managed departments.
- Validated project unit selection against selected department.
- Cleaned some organization/project wording.
- Added regression tests:
  - `CrossOrgScopeRegressionTest`
  - `DepartmentsDashboardScopingTest`
  - `OrganizationStructureTest`

Focused tests pass when run sequentially. Existing deprecation warnings remain from package code.

## Recommended Next Work

1. Fix organization context resolution.
   - Remove direct `users.organization_id` assumptions.
   - Make `User::Organization()` correct or replace it with explicit helpers.
   - Fix `OrganizationSwitcher` method call from `canAccessibleOrganizations()` to the real API.

2. Add a proper Projects module UI.
   - Add sidebar item to `projects.index`.
   - Build a browser UI for project list/create/edit/delete.
   - Keep Departments Dashboard as an analytics/dashboard view, not the only management surface.

3. Clean remaining registration discoverability.
   - Add "Create account" or "Register as member" link to the login page.
   - Consider renaming route/display text from "person self-register" to public member registration.

4. Fix import paths.
   - Remove `organization_id` from `PersonStandardImport` person creation.
   - Ensure all imports create `PersonAffiliation` records for membership.

5. Tighten relationship query scopes.
   - Group `orWhereHas` conditions in cross-organization relationship scopes.
   - Audit other `orWhere` access filters for leakage.

6. Consolidate organization unit components.
   - Remove or merge duplicated organization unit Livewire components.
   - Ensure all unit creation uses current organization or managed organization scope.

7. Validate project-affiliation unit scope.
   - `ProjectAffiliation.organization_unit_id` must belong to the same department/organization as the project.

