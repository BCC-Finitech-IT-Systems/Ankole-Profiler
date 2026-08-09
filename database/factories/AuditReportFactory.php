<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => $this->faker->sentence(4),
            'audit_type' => $this->faker->randomElement(['internal', 'external', 'financial', 'compliance', 'operational', 'institutional']),
            'issuing_body' => $this->faker->company(),
            'issue_date' => $this->faker->date(),
            'status' => 'draft',
            'restricted' => false,
        ];
    }
}
