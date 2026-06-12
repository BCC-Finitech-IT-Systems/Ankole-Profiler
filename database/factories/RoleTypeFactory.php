<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'department_id' => Department::factory(),
            'code'          => strtoupper($name),
            'name'          => ucfirst($name),
            'active'        => true,
        ];
    }

    public function global(): static
    {
        return $this->state(['department_id' => null]);
    }
}
