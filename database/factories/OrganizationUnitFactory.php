<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'department_id'   => Department::factory(),
            'name'            => $this->faker->words(2, true),
            'code'            => strtoupper($this->faker->unique()->lexify('UNIT-???')),
            'is_active'       => true,
        ];
    }
}
