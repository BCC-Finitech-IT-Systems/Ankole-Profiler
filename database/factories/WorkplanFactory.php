<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkplanFactory extends Factory
{
    public function definition(): array
    {
        $department = Department::factory()->create();

        return [
            'department_id' => $department->id,
            'organization_id' => $department->organization_id,
            'year' => now()->year,
            'version_number' => 1,
            'title' => 'FY' . now()->year . ' Workplan',
            'status' => 'draft',
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);
    }
}
