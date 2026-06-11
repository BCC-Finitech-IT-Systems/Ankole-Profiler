<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female', 'other']);
        $firstNames = $gender === 'female'
            ? ['Alice', 'Grace', 'Mary', 'Sarah', 'Jane', 'Rose', 'Faith', 'Hope', 'Joy', 'Peace', 'Mercy', 'Charity', 'Agnes', 'Betty', 'Catherine']
            : ['John', 'Paul', 'Peter', 'David', 'James', 'Michael', 'Robert', 'Stephen', 'Francis', 'Emmanuel', 'Joshua', 'Samuel', 'Daniel', 'Mark', 'Luke'];

        $middleNames = ['Grace', 'Mary', 'Paul', 'Peter', 'James', 'Rose', 'Faith', 'Hope', 'Emmanuel', 'Joseph'];

        $lastNames = ['Mukasa', 'Namubiru', 'Ssekandi', 'Katende', 'Mugisha', 'Byarugaba', 'Tumusiime', 'Asiimwe', 'Ainebyoona', 'Mbabazi',
                     'Kirabo', 'Nalwanga', 'Tukamushaba', 'Babirye', 'Nakato', 'Wasswa', 'Kato', 'Musoke', 'Lubega', 'Kasozi'];

        $districts = ['Kampala', 'Wakiso', 'Mukono', 'Jinja', 'Mbarara', 'Gulu', 'Lira', 'Arua', 'Masaka', 'Kasese', 'Kabale', 'Soroti', 'Kitgum', 'Hoima', 'Mbale'];
        $cities = ['Kampala', 'Entebbe', 'Jinja', 'Mbarara', 'Gulu', 'Lira', 'Arua', 'Masaka', 'Kasese', 'Kabale', 'Soroti', 'Kitgum', 'Hoima', 'Mbale', 'Tororo'];

        return [
            'user_id' => \App\Models\User::factory(),
            'person_id' => \App\Models\Person::generatePersonId(),
            'global_identifier' => $this->faker->uuid(),
            'given_name' => $this->faker->randomElement($firstNames),
            'middle_name' => $this->faker->optional(0.7)->randomElement($middleNames),
            'family_name' => $this->faker->randomElement($lastNames),
            'date_of_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'gender' => $gender,
            'classification' => [],
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement($cities),
            'district' => $this->faker->randomElement($districts),
            'country' => 'Uganda',
            'status' => 'active',
            'created_by' => 1, // Assuming user ID 1 exists
            'updated_by' => 1,
        ];
    }

    /**
     * Configure the factory for hospital organization
     */
    public function forHospital(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'classification' => ['medical'],
            ];
        });
    }

    /**
     * Configure the factory for school organization
     */
    public function forSchool(): static
    {
        return $this->state(function (array $attributes) {
            // Mix of students and staff
            $isStudent = $this->faker->boolean(70); // 70% students, 30% staff
            if ($isStudent) {
                return [
                    'date_of_birth' => $this->faker->dateTimeBetween('-25 years', '-5 years')->format('Y-m-d'),
                    'classification' => ['student'],
                ];
            }
            return [
                'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
                'classification' => ['education'],
            ];
        });
    }

    /**
     * Configure the factory for SACCO organization
     */
    public function forSacco(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                'classification' => ['financial'],
            ];
        });
    }

    /**
     * Configure the factory for parish organization
     */
    public function forParish(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'classification' => ['religious'],
            ];
        });
    }

    /**
     * Configure the factory for corporate organization
     */
    public function forCorporate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
                'classification' => ['corporate'],
            ];
        });
    }
}
