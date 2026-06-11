<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['hospital', 'school', 'sacco', 'parish', 'corporate', 'government', 'ngo'];
        $types = ['branch', 'HOLDING', 'SUBSIDIARY', 'STANDALONE'];

        return [
            'legal_name' => $this->faker->unique()->company() . ' ' . $this->faker->companySuffix(),
            'display_name' => $this->faker->company(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'organization_type' => $this->faker->randomElement($types),
            'category' => $this->faker->randomElement($categories),
            'registration_number' => $this->faker->unique()->numerify('REG-####'),
            'tax_identification_number' => $this->faker->unique()->numerify('TIN-########'),
            'country_of_registration' => 'UGA',
            'date_established' => $this->faker->dateTimeBetween('-20 years', '-1 year'),
            'website_url' => $this->faker->url(),
            'contact_email' => $this->faker->unique()->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'address_line_1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'district' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'UGA', // Use ISO code instead of full name
            'latitude' => $this->faker->latitude(-1.5, 4.0), // Uganda coordinates
            'longitude' => $this->faker->longitude(29.5, 35.0),
            // Required contact fields
            'primary_contact_name' => $this->faker->name(),
            'primary_contact_email' => $this->faker->unique()->safeEmail(),
            'primary_contact_phone' => $this->faker->phoneNumber(),
        ];
    }
}
