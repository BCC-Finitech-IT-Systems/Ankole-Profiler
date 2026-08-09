<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->diocese(),
            'department_id' => null,
            'reference_code' => strtoupper($this->faker->unique()->lexify('POL-????')),
            'title' => $this->faker->sentence(4),
            'policy_category' => $this->faker->randomElement(['Education', 'Finance', 'Safeguarding', 'HR']),
            'status' => 'draft',
        ];
    }
}
