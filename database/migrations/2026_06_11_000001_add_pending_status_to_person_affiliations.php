<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Blueprint cannot alter enum columns; modify it directly. 'pending'
        // and 'rejected' support the membership approval workflow.
        DB::statement("ALTER TABLE person_affiliations MODIFY status ENUM('pending', 'active', 'inactive', 'suspended', 'terminated', 'rejected') NOT NULL DEFAULT 'active'");

        // Pending applications have no start date until they are approved.
        DB::statement('ALTER TABLE person_affiliations MODIFY start_date DATE NULL');
    }

    public function down(): void
    {
        // Rows in the new states cannot be represented by the old enum; fold
        // them into 'inactive' before narrowing.
        DB::table('person_affiliations')
            ->whereIn('status', ['pending', 'rejected'])
            ->update(['status' => 'inactive']);

        DB::statement("ALTER TABLE person_affiliations MODIFY status ENUM('active', 'inactive', 'suspended', 'terminated') NOT NULL DEFAULT 'active'");
    }
};
