<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deduplicate before adding the unique index: keep the newest active
        // affiliation per (person, unit), deactivate the rest.
        DB::transaction(function () {
            $duplicates = DB::table('person_affiliations')
                ->select('person_id', 'organization_unit_id', DB::raw('MAX(id) as keep_id'))
                ->where('status', 'active')
                ->whereNotNull('organization_unit_id')
                ->groupBy('person_id', 'organization_unit_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                DB::table('person_affiliations')
                    ->where('person_id', $duplicate->person_id)
                    ->where('organization_unit_id', $duplicate->organization_unit_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $duplicate->keep_id)
                    ->update(['status' => 'inactive']);
            }
        });

        // A partial unique index over active rows is not expressible here:
        // MariaDB forbids generated columns that reference an FK column with
        // ON DELETE SET NULL (organization_unit_id). Uniqueness of active
        // unit membership is enforced in PersonAffiliation::boot() instead.
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->index(['person_id', 'organization_id', 'status'], 'pa_person_org_status_idx');
            $table->index(['department_id', 'status'], 'pa_department_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->dropIndex('pa_person_org_status_idx');
            $table->dropIndex('pa_department_status_idx');
        });
    }
};
