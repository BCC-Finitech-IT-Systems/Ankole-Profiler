<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use App\Models\EmailAddress;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo login for a tenant (Organization) admin. Organization Admin
 * authority is scoped through an active PersonAffiliation, not just the
 * role name (see User::managedOrganizationIds()) — so this account needs
 * a Person and an affiliation to the diocese, not just a role assignment.
 */
class DemoTenantAdminSeeder extends Seeder
{
    private const EMAIL = 'demo.tenantadmin@ankole.test';
    private const PASSWORD = 'Demo@Ankole2026';

    public function run(): void
    {
        $diocese = Organization::where('is_super', true)->first();
        if (!$diocese) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Demo Tenant Admin',
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ]
        );
        $user->syncRoles(['Organization Admin']);

        $person = Person::firstOrCreate(
            ['user_id' => $user->id],
            [
                'given_name' => 'Demo',
                'family_name' => 'Tenant Admin',
                'gender' => 'prefer_not_to_say',
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
            ['person_id' => $person->id, 'email' => self::EMAIL],
            [
                'email_id' => IdGenerator::generateEmailId(),
                'type' => 'personal',
                'is_primary' => true,
                'status' => 'active',
                'created_by' => $user->id,
            ]
        );

        PersonAffiliation::updateOrCreate(
            [
                'person_id' => $person->id,
                'organization_id' => $diocese->id,
                'organization_unit_id' => null,
            ],
            [
                'user_id' => $user->id,
                'role_type' => 'STAFF',
                'role_title' => 'Diocese Administrator',
                'status' => 'active',
                'start_date' => now()->subYears(2),
                'created_by' => $user->id,
            ]
        );
    }
}
