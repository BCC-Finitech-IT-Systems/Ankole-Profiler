<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => $this->faker->sentence(4),
            'priority' => 'medium',
            'status' => 'not_started',
            'percent_complete' => 0,
        ];
    }
}
