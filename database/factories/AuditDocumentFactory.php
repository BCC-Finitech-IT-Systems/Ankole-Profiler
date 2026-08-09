<?php

namespace Database\Factories;

use App\Models\AuditReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'audit_report_id' => AuditReport::factory(),
            'document_type' => 'report',
            'version_number' => 1,
            'is_current' => true,
            'path' => 'audit-reports/test/' . $this->faker->uuid() . '.pdf',
            'original_name' => $this->faker->word() . '.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
