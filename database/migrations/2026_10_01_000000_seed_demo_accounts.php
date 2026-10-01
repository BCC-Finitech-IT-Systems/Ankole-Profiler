<?php

use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Deploys only run migrations, so the login page's demo accounts (and the
 * small demo data set behind their dashboards) are created here. The
 * seeder is idempotent and skips itself on a fresh database whose
 * departments are not seeded yet; DatabaseSeeder runs it again afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new DemoAccountsSeeder())->run();
    }

    public function down(): void
    {
        // Demo data is left in place; remove it by hand if needed.
    }
};
