<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Services\PersonFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $Organization;
    protected $filterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Organization = Organization::factory()->create([
            'legal_name' => 'Test Organization',
            'category' => 'hospital'
        ]);

        $this->filterService = new PersonFilterService($this->Organization);
    }

    /**
     * The filter service only returns persons with an active affiliation
     * to the organization, so every test person needs one.
     */
    protected function createPerson(array $attributes = []): Person
    {
        $person = Person::factory()->create($attributes);

        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $this->Organization->id,
            'status' => 'active',
        ]);

        return $person;
    }

    public function test_can_filter_by_search_term()
    {
        // Create test persons
        $person1 = $this->createPerson(['given_name' => 'John', 'family_name' => 'Doe']);
        $person2 = $this->createPerson(['given_name' => 'Jane', 'family_name' => 'Smith']);

        // Filter by search term
        $results = $this->filterService->applyFilters(['search' => 'John'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->given_name);
    }

    public function test_can_filter_by_classification()
    {
        // Create test persons with different classifications
        $person1 = $this->createPerson(['classification' => ['STAFF']]);
        $person2 = $this->createPerson(['classification' => ['MEMBER']]);

        // Filter by classification
        $results = $this->filterService->applyFilters(['classification' => 'STAFF'])->get();

        $this->assertCount(1, $results);
        $this->assertContains('STAFF', $results->first()->classification);
    }

    public function test_can_filter_by_gender()
    {
        // Create test persons with different genders
        $person1 = $this->createPerson(['gender' => 'male']);
        $person2 = $this->createPerson(['gender' => 'female']);

        // Filter by gender
        $results = $this->filterService->applyFilters(['gender' => 'male'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('male', $results->first()->gender);
    }

    public function test_can_filter_by_age_range()
    {
        // Create test persons with different birth dates
        $person1 = $this->createPerson(['date_of_birth' => now()->subYears(25)->format('Y-m-d')]);
        $person2 = $this->createPerson(['date_of_birth' => now()->subYears(45)->format('Y-m-d')]);

        // Filter by age range
        $results = $this->filterService->applyFilters(['age_range' => '18-30'])->get();

        $this->assertCount(1, $results);
    }

    public function test_can_combine_multiple_filters()
    {
        // Create test persons
        $person1 = $this->createPerson([
            'given_name' => 'John',
            'gender' => 'male',
            'classification' => ['STAFF']
        ]);

        $person2 = $this->createPerson([
            'given_name' => 'Jane',
            'gender' => 'female',
            'classification' => ['MEMBER']
        ]);

        // Apply multiple filters
        $results = $this->filterService->applyFilters([
            'search' => 'John',
            'gender' => 'male',
            'classification' => 'STAFF'
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->given_name);
    }

    public function test_returns_empty_when_no_matches()
    {
        // Create test person
        $this->createPerson(['given_name' => 'John', 'family_name' => 'Doe']);

        // Filter with non-matching criteria
        $results = $this->filterService->applyFilters(['search' => 'NonExistent'])->get();

        $this->assertCount(0, $results);
    }
}
