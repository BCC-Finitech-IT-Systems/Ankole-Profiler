<?php

namespace Database\Factories;

use App\Models\Workplan;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkplanActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workplan_id' => Workplan::factory(),
            'strategic_objective' => $this->faker->sentence(4),
            'activity' => $this->faker->sentence(6),
            'priority' => 'medium',
            'status' => 'not_started',
            'percent_complete' => 0,
        ];
    }
}
