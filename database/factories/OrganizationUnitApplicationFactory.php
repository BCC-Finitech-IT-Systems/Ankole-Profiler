<?php

namespace Database\Factories;

use App\Models\OrganizationUnit;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationUnitApplicationFactory extends Factory
{
    public function definition(): array
    {
        $unit = OrganizationUnit::factory()->create();

        return [
            'organization_id' => $unit->organization_id,
            'unit_id'         => $unit->id,
            'person_id'       => Person::factory(),
            'status'          => 'pending',
        ];
    }
}
