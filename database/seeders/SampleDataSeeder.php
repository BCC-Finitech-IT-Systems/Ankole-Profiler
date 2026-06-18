<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use App\Models\Department;
use App\Models\EmailAddress;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\Phone;
use App\Models\RoleType;
use App\Models\UnitPersonRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data demonstrating the post-remediation membership workflows:
 *
 * - Scoped admins: an Organization Admin and a Department Manager whose
 *   authority flows from active affiliations (managedOrganizationIds /
 *   managedDepartmentIds), not from global roles alone.
 * - Self-registration outcomes: pending, rejected, and approved diocese
 *   membership applications (ReviewMembershipApplications).
 * - Unit membership: an active unit member with a unit_person_roles row
 *   resolved to a RoleType (role_type_id), plus a pending unit application
 *   awaiting approval (ReviewUnitApplications / ManageUnitMembers).
 *
 * All sample accounts share the password below.
 */
class SampleDataSeeder extends Seeder
{
    private const PASSWORD = 'Ankole@2026';

    private Organization $superOrg;

    public function run(): void
    {
        $this->superOrg = Organization::where('is_super', true)->firstOrFail();

        $diocese = $this->createDiocese();
        $units = $this->createUnits();

        $admin = $this->createOrganizationAdmin($diocese);
        $this->createDepartmentManager();
        $this->createActiveUnitMember($units['youth'], $admin);
        $this->createPendingApplicants($diocese);
        $this->createRejectedApplicant($admin, $diocese);
        $this->createPendingUnitApplication($units['chaplaincy']);

        $this->command->info('Sample data seeded:');
        $this->command->line('  - 1 diocese (Ankole Diocese)');
        $this->command->line('  - 1 Organization Admin (joanita.asasira.demo@gmail.com)');
        $this->command->line('  - 1 Department Manager, Education (peter.nahabwe.demo@gmail.com)');
        $this->command->line('  - 1 active Youth Ministry member (grace.atuhaire.demo@gmail.com)');
        $this->command->line('  - 2 pending + 1 rejected membership applications (Ankole Diocese)');
        $this->command->line('  - 1 pending unit application (School Chaplaincy Committee)');
        $this->command->line('  All sample accounts use password: ' . self::PASSWORD);
    }

    /**
     * The diocese sits under the super organization; it is what self-
     * registration and the org create form offer as the membership target.
     * Keyed on a non-super "Ankole Diocese" so it never collides with the
     * super organization (which DepartmentSeeder also names "Ankole Diocese").
     */
    private function createDiocese(): Organization
    {
        return Organization::updateOrCreate(
            ['legal_name' => 'Ankole Diocese', 'is_super' => false],
            [
                'category' => 'diocese',
                'organization_type' => 'branch',
                'parent_organization_id' => $this->superOrg->id,
                'country' => 'UGA',
                'country_of_registration' => 'UGA',
                'is_active' => true,
                'display_name' => 'Ankole Diocese',
                'code' => 'AD-001',
                'city' => 'Mbarara',
                'district' => 'Mbarara',
                'contact_email' => 'office@ankolediocese.org',
            ]
        );
    }

    /**
     * Units under departments, so RoleType resolution and the manage gates
     * exercise the department scoping added in the remediation.
     *
     * @return array{youth: OrganizationUnit, chaplaincy: OrganizationUnit, choir: OrganizationUnit}
     */
    private function createUnits(): array
    {
        $outreach = Department::where('name', 'Mission and Outreach')->first();
        $education = Department::where('name', 'Education')->first();

        $defaults = [
            'is_active' => true,
            'faith_based' => true,
            'socio_economic' => false,
            'support_services' => false,
            'operational_status' => 'active',
            'join_requests_enabled' => true,
        ];

        $youth = OrganizationUnit::updateOrCreate(
            ['organization_id' => $this->superOrg->id, 'code' => 'YOUTH-MIN'],
            $defaults + [
                'name' => 'Youth Ministry',
                'department_id' => $outreach?->id,
                'unit_type' => 'ministry',
                'description' => 'Diocese-wide youth ministry and fellowship.',
            ]
        );

        $chaplaincy = OrganizationUnit::updateOrCreate(
            ['organization_id' => $this->superOrg->id, 'code' => 'SCH-CHAP'],
            $defaults + [
                'name' => 'School Chaplaincy Committee',
                'department_id' => $education?->id,
                'unit_type' => 'committee',
                'description' => 'Coordinates chaplaincy across diocese schools.',
            ]
        );

        // Deliberately department-less: exercises the global MEMBER fallback
        // in UnitRoleAssigner.
        $choir = OrganizationUnit::updateOrCreate(
            ['organization_id' => $this->superOrg->id, 'code' => 'CATH-CHOIR'],
            $defaults + [
                'name' => 'Cathedral Choir',
                'unit_type' => 'community',
                'description' => 'St. James Cathedral choir.',
            ]
        );

        return ['youth' => $youth, 'chaplaincy' => $chaplaincy, 'choir' => $choir];
    }

    /**
     * Org Admin scope comes from the role AND active affiliations
     * (managedOrganizationIds): one to the super org, one to the diocese
     * she administers, so she can review its applications and edit it in
     * the Dioceses admin section.
     */
    private function createOrganizationAdmin(Organization $diocese): User
    {
        [$user, $person] = $this->createAccount(
            'joanita.asasira.demo@gmail.com',
            ['given_name' => 'Joanita', 'family_name' => 'Asasira', 'gender' => 'female', 'date_of_birth' => '1988-04-12'],
            'Organization Admin'
        );

        $this->affiliate($person, $user, [
            'role_type' => 'STAFF',
            'role_title' => 'Diocese Administrator',
            'status' => 'active',
            'start_date' => now()->subYears(2),
        ]);

        $this->affiliate($person, $user, [
            'role_type' => 'STAFF',
            'role_title' => 'Diocese Administrator',
            'status' => 'active',
            'start_date' => now()->subYears(2),
        ], $diocese);

        return $user;
    }

    /**
     * Department Manager scope flows from the affiliation's department_id
     * (managedDepartmentIds).
     */
    private function createDepartmentManager(): void
    {
        $education = Department::where('name', 'Education')->first();

        [$user, $person] = $this->createAccount(
            'peter.nahabwe.demo@gmail.com',
            ['given_name' => 'Peter', 'family_name' => 'Nahabwe', 'gender' => 'male', 'date_of_birth' => '1985-09-30'],
            'Department Manager'
        );

        $this->affiliate($person, $user, [
            'role_type' => 'STAFF',
            'role_title' => 'Education Department Manager',
            'department_id' => $education?->id,
            'status' => 'active',
            'start_date' => now()->subYear(),
        ]);
    }

    /**
     * Fully approved member of a unit: active unit affiliation plus a
     * unit_person_roles row carrying role_type_id (Phase 4).
     */
    private function createActiveUnitMember(OrganizationUnit $unit, User $grantedBy): void
    {
        [$user, $person] = $this->createAccount(
            'grace.atuhaire.demo@gmail.com',
            ['given_name' => 'Grace', 'family_name' => 'Atuhaire', 'gender' => 'female', 'date_of_birth' => '1999-01-25'],
            'Person'
        );

        // Diocese membership (the approved outcome of self-registration).
        $this->affiliate($person, $user, [
            'role_type' => 'MEMBER',
            'status' => 'active',
            'start_date' => now()->subMonths(6),
        ]);

        // Unit membership mirroring ReviewUnitApplications::createMembership().
        $this->affiliate($person, $user, [
            'organization_unit_id' => $unit->id,
            'department_id' => $unit->department_id,
            'role_type' => 'MEMBER',
            'status' => 'active',
            'start_date' => now()->subMonths(3),
            'permissions' => ['view'],
        ]);

        $memberRoleType = RoleType::where('code', 'MEMBER')->first();

        UnitPersonRole::updateOrCreate(
            ['unit_id' => $unit->id, 'person_id' => $person->id],
            [
                'role' => 'MEMBER',
                'role_type_id' => $memberRoleType?->id,
                'can_view' => true,
                'can_edit' => false,
                'can_approve' => false,
                'granted_by' => $grantedBy->id,
                'granted_at' => now()->subMonths(3),
            ]
        );
    }

    /**
     * Pending diocese membership applications, exactly as self-registration
     * leaves them: no role assigned, no start_date (Phase 3).
     */
    private function createPendingApplicants(Organization $diocese): void
    {
        $applicants = [
            ['email' => 'samuel.tumusiime.demo@gmail.com', 'given_name' => 'Samuel', 'family_name' => 'Tumusiime', 'gender' => 'male', 'date_of_birth' => '1995-07-04'],
            ['email' => 'esther.kyomugisha.demo@gmail.com', 'given_name' => 'Esther', 'family_name' => 'Kyomugisha', 'gender' => 'female', 'date_of_birth' => '2001-11-17'],
        ];

        foreach ($applicants as $applicant) {
            $email = $applicant['email'];
            unset($applicant['email']);

            [$user, $person] = $this->createAccount($email, $applicant);

            $this->affiliate($person, $user, [
                'role_type' => 'MEMBER',
                'status' => 'pending',
            ], $diocese);
        }
    }

    private function createRejectedApplicant(User $rejectedBy, Organization $diocese): void
    {
        [$user, $person] = $this->createAccount(
            'david.mugisha.demo@gmail.com',
            ['given_name' => 'David', 'family_name' => 'Mugisha', 'gender' => 'male', 'date_of_birth' => '1992-02-08']
        );

        $this->affiliate($person, $user, [
            'role_type' => 'MEMBER',
            'status' => 'rejected',
            'updated_by' => $rejectedBy->id,
        ], $diocese);
    }

    /**
     * An approved diocese member who has applied to join a unit; the
     * application sits pending for ReviewUnitApplications.
     */
    private function createPendingUnitApplication(OrganizationUnit $unit): void
    {
        [$user, $person] = $this->createAccount(
            'ruth.namara.demo@gmail.com',
            ['given_name' => 'Ruth', 'family_name' => 'Namara', 'gender' => 'female', 'date_of_birth' => '1997-05-21'],
            'Person'
        );

        $this->affiliate($person, $user, [
            'role_type' => 'MEMBER',
            'status' => 'active',
            'start_date' => now()->subMonth(),
        ]);

        OrganizationUnitApplication::updateOrCreate(
            ['organization_id' => $this->superOrg->id, 'unit_id' => $unit->id, 'person_id' => $person->id],
            ['status' => 'pending', 'notes' => 'Interested in serving on the chaplaincy committee.']
        );
    }

    /**
     * User + linked Person (persons.user_id) + primary contacts, mirroring
     * PersonSelfRegistrationComponent.
     *
     * @return array{0: User, 1: Person}
     */
    private function createAccount(string $email, array $personAttrs, ?string $role = null): array
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $personAttrs['given_name'] . ' ' . $personAttrs['family_name'],
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ]
        );

        if ($role) {
            $user->syncRoles([$role]);
        }

        $person = Person::firstOrCreate(
            ['user_id' => $user->id],
            $personAttrs + [
                'person_id' => IdGenerator::generatePersonId(),
                'global_identifier' => IdGenerator::generateGlobalIdentifier(),
                'classification' => ['MEMBER'],
                'address' => 'Plot 12, High Street',
                'city' => 'Mbarara',
                'district' => 'Mbarara',
                'country' => 'UGA',
                'created_by' => $user->id,
            ]
        );

        EmailAddress::firstOrCreate(
            ['person_id' => $person->id, 'email' => $email],
            [
                'email_id' => IdGenerator::generateEmailId(),
                'type' => 'personal',
                'is_primary' => true,
                'status' => 'active',
                'created_by' => $user->id,
            ]
        );

        Phone::firstOrCreate(
            ['person_id' => $person->id],
            [
                'phone_id' => IdGenerator::generatePhoneId(),
                'number' => '+2567' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'type' => 'mobile',
                'is_primary' => true,
                'status' => 'active',
                'created_by' => $user->id,
            ]
        );

        return [$user, $person];
    }

    private function affiliate(Person $person, User $user, array $attrs, ?Organization $organization = null): PersonAffiliation
    {
        return PersonAffiliation::updateOrCreate(
            [
                'person_id' => $person->id,
                'organization_id' => ($organization ?? $this->superOrg)->id,
                'organization_unit_id' => $attrs['organization_unit_id'] ?? null,
            ],
            $attrs + [
                'user_id' => $user->id,
                'created_by' => $user->id,
            ]
        );
    }
}
