<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('reference_number');
            $table->string('property_name');
            $table->string('location')->nullable();
            $table->string('district')->nullable();
            $table->string('sub_county')->nullable();
            $table->string('parish')->nullable();
            $table->string('village')->nullable();
            $table->decimal('acreage', 10, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('tenure_type')->nullable();
            $table->string('current_use')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->text('acquisition_details')->nullable();

            $table->enum('stage', [
                'unregistered', 'documents_gathering', 'survey_requested', 'surveyed',
                'application_prepared', 'submitted', 'under_review', 'queries_raised',
                'approved', 'title_issued', 'disputed', 'closed',
            ])->default('unregistered');

            $table->string('application_reference')->nullable();
            $table->string('land_office')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->string('external_advocate')->nullable();
            $table->string('external_surveyor')->nullable();
            $table->text('next_action')->nullable();
            $table->text('blockers')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();

            // Title particulars
            $table->string('title_number')->nullable();
            $table->string('title_volume_folio')->nullable();
            $table->date('title_issue_date')->nullable();
            $table->string('registered_proprietor')->nullable();
            $table->text('encumbrances')->nullable();
            $table->date('lease_expiry_date')->nullable();
            $table->enum('title_verification_status', ['unverified', 'verified', 'disputed'])->default('unverified');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference_number']);
            $table->index(['organization_id', 'stage']);
            $table->index('department_id');
            $table->index('expected_completion_date');
            $table->index('lease_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcels');
    }
};
