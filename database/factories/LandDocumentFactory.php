<?php

namespace Database\Factories;

use App\Models\LandParcel;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'land_parcel_id' => LandParcel::factory(),
            'document_type' => 'survey_report',
            'version_number' => 1,
            'is_current' => true,
            'restricted' => false,
            'path' => 'land-documents/fake/' . $this->faker->uuid() . '.pdf',
            'original_name' => 'document.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }

    public function restricted(): static
    {
        return $this->state(fn () => ['restricted' => true]);
    }
}
