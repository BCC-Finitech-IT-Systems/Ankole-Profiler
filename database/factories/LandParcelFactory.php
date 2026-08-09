<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandParcelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reference_number' => strtoupper($this->faker->unique()->lexify('LP-????')),
            'property_name' => $this->faker->streetName() . ' Plot',
            'district' => $this->faker->city(),
            'stage' => 'unregistered',
            'title_verification_status' => 'unverified',
        ];
    }
}
