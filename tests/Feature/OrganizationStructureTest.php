<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    private Organization $diocese;
    private Organization $org;
    private Department $department;
    private OrganizationUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->create([
            'category' => 'diocese',
            'organization_type' => 'branch',
        ]);
        $this->org = Organization::factory()->create([
            'parent_organization_id' => $this->diocese->id,
            'organization_type' => 'branch',
        ]);
        $this->department = Department::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Education Wing',
        ]);
        $this->unit = OrganizationUnit::factory()->create([
            'organization_id' => $this->org->id,
            'department_id' => $this->department->id,
            'name' => 'Chapel Choir Unit',
        ]);

        PersonAffiliation::factory()->create([
            'person_id' => Person::factory()->create()->id,
            'organization_id' => $this->org->id,
            'organization_unit_id' => $this->unit->id,
            'status' => 'active',
        ]);
        PersonAffiliation::factory()->create([
            'person_id' => Person::factory()->create()->id,
            'organization_id' => $this->org->id,
            'status' => 'pending',
        ]);
    }

    public function test_show_page_lists_structure_components()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.show', $this->org->id))
            ->assertOk()
            ->assertSee('Structure & Components')
            ->assertSee('Education Wing')
            ->assertSee('Chapel Choir Unit')
            ->assertSee($this->diocese->display_name)
            ->assertSee('Pending applications');
    }

    public function test_show_page_lists_child_organizations_on_diocese()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.show', $this->diocese->id))
            ->assertOk()
            ->assertSee($this->org->display_name);
    }

    public function test_index_shows_diocese_and_component_counts()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertSee($this->diocese->display_name)
            ->assertSee('depts')
            ->assertSee('units')
            ->assertSee('members');
    }
}
