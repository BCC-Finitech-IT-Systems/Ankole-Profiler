<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\PersonRelationship;
use App\Models\Project;
use App\Models\ProjectAffiliation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Demo accounts behind the login page's one-click buttons, plus a small
 * set of projects and people so their dashboards are not empty:
 *
 * - Diocese administrator: the demo tenant admin (DemoTenantAdminSeeder)
 * - Head of Department, Education (Department Manager)
 *
 * Idempotent: safe to run on every deploy. Every record it creates is
 * marked as demo data (".test" emails, "DEMO-" project codes).
 */
class DemoAccountsSeeder extends Seeder
{
    public const PASSWORD = 'Demo@Ankole2026';

    public const DIOCESE_ADMIN_EMAIL = 'demo.tenantadmin@ankole.test';

    public const HEAD_OF_DEPARTMENT_EMAIL = 'demo.hod@ankole.test';

    private Organization $diocese;

    public function run(): void
    {
        $diocese = Organization::where('is_super', true)->first();
        $education = Department::where('name', 'Education')->first();
        $outreach = Department::where('name', 'Mission and Outreach')->first();

        if (!$diocese || !$education || !$outreach) {
            $this->command?->warn('DemoAccountsSeeder skipped: diocese or departments not seeded yet.');

            return;
        }

        $this->diocese = $diocese;

        foreach (['Organization Admin', 'Department Manager'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // The diocese admin is the existing demo tenant admin.
        (new DemoTenantAdminSeeder())->run();
        $dioceseAdmin = User::where('email', self::DIOCESE_ADMIN_EMAIL)->firstOrFail();

        $hod = $this->account(self::HEAD_OF_DEPARTMENT_EMAIL, 'Demo', 'Head of Education', 'Department Manager');
        $this->affiliate($hod->person, $hod, $education, 'STAFF', 'Head of Department, Education');

        // Show the demo head on the dashboard, without displacing a real one.
        if (!$education->admin_user_id) {
            $education->update(['admin_user_id' => $hod->id]);
        }

        $stJames = $this->project($education, 'St James SS', 'DEMO-SJSS', 'Secondary', $hod);
        $mbararaHigh = $this->project($education, 'Mbarara High School', 'DEMO-MHS', 'Secondary', $hod);
        $stPeters = $this->project($outreach, "St Peter's Church", 'DEMO-SPC', 'Parish', $dioceseAdmin);

        $people = [
            // [given, family, gender, born, project, type, role]
            ['Mary', 'Kansiime', 'female', '1984-03-14', $stJames, 'staff', 'Head Teacher'],
            ['John', 'Byaruhanga', 'male', '1990-08-02', $stJames, 'staff', 'Teacher'],
            ['Brian', 'Tumwine', 'male', '2009-05-19', $stJames, 'person', 'Student'],
            ['Faith', 'Ainembabazi', 'female', '2008-11-03', $stJames, 'person', 'Student'],
            ['Annet', 'Tumwine', 'female', '1979-01-27', $stJames, 'associate', 'Parent'],
            ['Isaac', 'Mwesigye', 'male', '1987-06-11', $mbararaHigh, 'staff', 'Teacher'],
            ['Doreen', 'Natukunda', 'female', '2007-09-22', $mbararaHigh, 'person', 'Student'],
            ['Moses', 'Kato', 'male', '1975-12-05', $stPeters, 'staff', 'Parish Priest'],
            ['Juliet', 'Atwine', 'female', '1993-04-30', $stPeters, 'person', 'Parishioner'],
        ];

        $created = [];
        foreach ($people as [$given, $family, $gender, $born, $project, $type, $role]) {
            $person = $this->person($given, $family, $gender, $born, $hod);
            $this->affiliate($person, $hod, $project->department, $type === 'staff' ? 'STAFF' : 'MEMBER', $role);
            ProjectAffiliation::updateOrCreate(
                ['project_id' => $project->id, 'person_id' => $person->id],
                [
                    'affiliation_type' => $type,
                    'role_title' => $role,
                    'occupation' => $type === 'staff' ? $role : null,
                    'start_date' => now()->subYear()->toDateString(),
                    'status' => 'active',
                    'created_by' => $hod->id,
                ]
            );
            $created["$given $family"] = $person;
        }

        // One person, one profile: the parent at St James SS is also a
        // parishioner at St Peter's, and is linked to her son.
        $annet = $created['Annet Tumwine'];
        ProjectAffiliation::updateOrCreate(
            ['project_id' => $stPeters->id, 'person_id' => $annet->id],
            ['affiliation_type' => 'person', 'role_title' => 'Parishioner', 'start_date' => now()->subYears(5)->toDateString(), 'status' => 'active', 'created_by' => $hod->id]
        );

        PersonRelationship::updateOrCreate(
            ['person_a_id' => $annet->id, 'person_b_id' => $created['Brian Tumwine']->id, 'relationship_type' => 'parent_child'],
            [
                'direction' => 'a_to_b',
                'is_primary' => true,
                'confidence_score' => 1.00,
                'discovery_method' => 'manual',
                'status' => 'active',
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]
        );

        // Policies, workplans, assignments, land and audit reports for the
        // demo department, so those pages aren't empty.
        (new DemoModuleDataSeeder())->setCommand($this->command)->run();

        $this->command?->info('Demo accounts seeded (password ' . self::PASSWORD . '): '
            . self::DIOCESE_ADMIN_EMAIL . ', ' . self::HEAD_OF_DEPARTMENT_EMAIL);
    }

    private function account(string $email, string $given, string $family, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => "$given $family",
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ]
        );
        $user->syncRoles([$role]);

        $person = Person::firstOrCreate(
            ['user_id' => $user->id],
            [
                'person_id' => IdGenerator::generatePersonId(),
                'global_identifier' => IdGenerator::generateGlobalIdentifier(),
                'given_name' => $given,
                'family_name' => $family,
                'classification' => ['STAFF'],
                'city' => 'Mbarara',
                'district' => 'Mbarara',
                'country' => 'UGA',
                'created_by' => $user->id,
            ]
        );

        return $user->setRelation('person', $person);
    }

    private function person(string $given, string $family, string $gender, string $born, User $createdBy): Person
    {
        return Person::firstOrCreate(
            ['given_name' => $given, 'family_name' => $family, 'date_of_birth' => $born],
            [
                'person_id' => IdGenerator::generatePersonId(),
                'global_identifier' => IdGenerator::generateGlobalIdentifier(),
                'gender' => $gender,
                'classification' => ['MEMBER'],
                'city' => 'Mbarara',
                'district' => 'Mbarara',
                'country' => 'UGA',
                'created_by' => $createdBy->id,
            ]
        );
    }

    private function project(Department $department, string $name, string $code, string $subCategory, User $admin): Project
    {
        return Project::updateOrCreate(
            ['code' => $code],
            [
                'department_id' => $department->id,
                'name' => $name,
                'sub_category' => $subCategory,
                'description' => 'Demo project for the client presentation.',
                'admin_user_id' => $admin->id,
                'starts_on' => now()->subYear()->toDateString(),
                'is_active' => true,
            ]
        )->setRelation('department', $department);
    }

    private function affiliate(Person $person, User $user, ?Department $department, string $roleType, string $title): void
    {
        PersonAffiliation::updateOrCreate(
            [
                'person_id' => $person->id,
                'organization_id' => $this->diocese->id,
                'role_type' => $roleType,
                'organization_unit_id' => null,
            ],
            [
                'department_id' => $department?->id,
                'user_id' => $person->user_id,
                'role_title' => $title,
                'status' => 'active',
                'start_date' => now()->subYear(),
                'created_by' => $user->id,
            ]
        );
    }
}
