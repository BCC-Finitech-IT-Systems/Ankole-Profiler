<?php

namespace Database\Factories;

use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'version_number' => 1,
            'version_label' => '1.0',
            'summary' => $this->faker->sentence(),
            'status' => 'draft',
            'visibility' => 'diocese_wide',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'synod_approval_date' => now()->subDays(5)->toDateString(),
            'synod_approval_reference' => 'SYNOD-' . $this->faker->unique()->numerify('####'),
            'document_path' => 'policies/fake/document.pdf',
            'document_original_name' => 'document.pdf',
            'document_mime' => 'application/pdf',
            'document_size' => 1024,
            'approved_at' => now(),
        ]);
    }

    public function published(): static
    {
        return $this->approved()->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
