<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name'            => $this->faker->words(2, true),
            'code'            => strtoupper($this->faker->unique()->lexify('DEPT-???')),
            'is_active'       => true,
        ];
    }
}
