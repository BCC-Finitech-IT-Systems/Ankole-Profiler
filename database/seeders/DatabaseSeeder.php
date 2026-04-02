<?php

namespace Database\Seeders;

use App\Models\FilterConfiguration;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{

    // public function run(): void
    // {
    //     $this->call([
    //         RolePermissionSeeder::class,
    //         UserSeeder::class,
    //         OrganizationSeeder::class,
    //         PersonTestDataSeeder::class,
    //         PersonSeeder::class,
    //         RolePermissionSeeder::class,
    //         PersonViewPermissionSeeder::class,
    //         RoleTypeSeeder::class,
    //         DomainRecordsSeeder::class,
    //         FilterConfiguration::class,
    //     ]);
    // }

    public function run(): void
    {
        $this->command->info('Running only the UserSeeder...');

        // Disable foreign key checks for seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Run only the UserSeeder
            $this->call([
                RolesAndPermissionsSeeder::class,
                UserSeeder::class,
                AllowedEmailDomainSeeder::class,
                CommunicationPermissionSeeder::class,
                DepartmentSeeder::class,
                DepartmentSubCategorySeeder::class,
            ]);
        } finally {
            // Re-enable foreign key checks after seeding
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->command->info('UserSeeder has been executed successfully.');
    }

    /**
     * Display a summary of seeded data
     */
    private function displaySeedingSummary(): void
    {
        $this->command->info('=== SEEDING SUMMARY ===');

        // Organizations
        $orgCount = DB::table('Organizations')->count();
        $this->command->line("Organizations: {$orgCount}");

        // Persons
        $personCount = DB::table('persons')->count();
        $this->command->line("Persons: {$personCount}");

        // Affiliations
        $affiliationCount = DB::table('person_affiliations')->count();
        $this->command->line("Person Affiliations: {$affiliationCount}");

        // Relationships
        $personalRelCount = DB::table('person_relationships')->count();
        $crossOrgRelCount = DB::table('cross_org_relationships')->count();
        $this->command->line("Personal Relationships: {$personalRelCount}");
        $this->command->line("Cross-Org Relationships: {$crossOrgRelCount}");

        // Verification status
        $verifiedPersonal = DB::table('person_relationships')
            ->where('verification_status', 'verified')->count();
        $pendingPersonal = DB::table('person_relationships')
            ->where('verification_status', 'unverified')->count();
        $verifiedCrossOrg = DB::table('cross_org_relationships')
            ->where('verified', true)->count();
        $pendingCrossOrg = DB::table('cross_org_relationships')
            ->where('verified', false)->count();

        $this->command->line("Verified Personal: {$verifiedPersonal}, Pending: {$pendingPersonal}");
        $this->command->line("Verified Cross-Org: {$verifiedCrossOrg}, Pending: {$pendingCrossOrg}");

        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->line('1. Run: php artisan relationships:discover --type=all');
        $this->command->line('2. Run: php artisan relationships:verify --interactive');
        $this->command->line('3. Access dashboard at: /relationships');
    }
}
