<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original (person_id, organization_id, role_type) unique index
        // made a unit membership collide with the person's org-level
        // affiliation of the same role code (diocese MEMBER + unit MEMBER),
        // crashing unit-application approval. Include organization_unit_id
        // so rows are deduplicated per unit instead. MariaDB treats NULLs as
        // distinct in unique indexes, so uniqueness of the org-level
        // (unit-less) row is enforced in PersonAffiliation::boot().
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->unique(
                ['person_id', 'organization_id', 'role_type', 'organization_unit_id'],
                'pa_person_org_role_unit_unique'
            );
            $table->dropUnique('person_affiliations_person_id_organization_id_role_type_unique');
        });
    }

    public function down(): void
    {
        // Restoring the strict unique fails if unit-level duplicates exist;
        // deactivate the extra rows is not safe to guess here, so this only
        // works on data that still satisfies the old constraint.
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->unique(['person_id', 'organization_id', 'role_type']);
            $table->dropUnique('pa_person_org_role_unit_unique');
        });
    }
};
