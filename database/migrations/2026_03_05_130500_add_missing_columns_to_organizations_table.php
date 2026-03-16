<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'display_name')) {
                $table->string('display_name')->nullable()->after('legal_name');
            }

            if (!Schema::hasColumn('organizations', 'parent_organization_id')) {
                $table->foreignId('parent_organization_id')
                    ->nullable()
                    ->after('organization_type')
                    ->constrained('organizations')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('organizations', 'tax_identification_number')) {
                $table->string('tax_identification_number')->nullable()->after('registration_number');
            }

            if (!Schema::hasColumn('organizations', 'country_of_registration')) {
                $table->string('country_of_registration', 3)->nullable()->after('tax_identification_number');
            }

            if (!Schema::hasColumn('organizations', 'website_url')) {
                $table->string('website_url')->nullable()->after('date_established');
            }

            if (!Schema::hasColumn('organizations', 'description')) {
                $table->text('description')->nullable()->after('contact_phone');
            }

            if (!Schema::hasColumn('organizations', 'address_line_1')) {
                $table->text('address_line_1')->nullable()->after('description');
            }

            if (!Schema::hasColumn('organizations', 'address_line_2')) {
                $table->text('address_line_2')->nullable()->after('address_line_1');
            }

            if (!Schema::hasColumn('organizations', 'regulatory_body')) {
                $table->string('regulatory_body')->nullable()->after('longitude');
            }

            if (!Schema::hasColumn('organizations', 'license_number')) {
                $table->string('license_number')->nullable()->after('regulatory_body');
            }

            if (!Schema::hasColumn('organizations', 'license_issue_date')) {
                $table->date('license_issue_date')->nullable()->after('license_number');
            }

            if (!Schema::hasColumn('organizations', 'license_expiry_date')) {
                $table->date('license_expiry_date')->nullable()->after('license_issue_date');
            }

            if (!Schema::hasColumn('organizations', 'accreditation_status')) {
                $table->string('accreditation_status')->nullable()->after('license_expiry_date');
            }

            if (!Schema::hasColumn('organizations', 'primary_contact_title')) {
                $table->string('primary_contact_title')->nullable()->after('primary_contact_name');
            }

            if (!Schema::hasColumn('organizations', 'secondary_contact_name')) {
                $table->string('secondary_contact_name')->nullable()->after('primary_contact_phone');
            }

            if (!Schema::hasColumn('organizations', 'secondary_contact_email')) {
                $table->string('secondary_contact_email')->nullable()->after('secondary_contact_name');
            }

            if (!Schema::hasColumn('organizations', 'secondary_contact_phone')) {
                $table->string('secondary_contact_phone')->nullable()->after('secondary_contact_email');
            }

            if (!Schema::hasColumn('organizations', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('secondary_contact_phone');
            }

            if (!Schema::hasColumn('organizations', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }

            if (!Schema::hasColumn('organizations', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_account_number');
            }

            if (!Schema::hasColumn('organizations', 'default_currency')) {
                $table->string('default_currency', 10)->nullable()->after('bank_branch');
            }

            if (!Schema::hasColumn('organizations', 'timezone')) {
                $table->string('timezone')->nullable()->after('default_currency');
            }

            if (!Schema::hasColumn('organizations', 'default_language')) {
                $table->string('default_language', 10)->nullable()->after('timezone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'parent_organization_id')) {
                $table->dropConstrainedForeignId('parent_organization_id');
            }

            $columnsToDrop = [
                'display_name',
                'tax_identification_number',
                'country_of_registration',
                'website_url',
                'description',
                'address_line_1',
                'address_line_2',
                'regulatory_body',
                'license_number',
                'license_issue_date',
                'license_expiry_date',
                'accreditation_status',
                'primary_contact_title',
                'secondary_contact_name',
                'secondary_contact_email',
                'secondary_contact_phone',
                'bank_name',
                'bank_account_number',
                'bank_branch',
                'default_currency',
                'timezone',
                'default_language',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
