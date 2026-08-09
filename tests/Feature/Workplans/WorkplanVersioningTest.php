<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanVersioningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    public function test_editing_an_approved_workplan_throws_and_leaves_the_row_unchanged()
    {
        $workplan = Workplan::factory()->approved()->create([
            'department_id' => $this->department->id,
            'organization_id' => $this->organization->id,
            'title' => 'Original Title',
        ]);

        $this->expectException(\DomainException::class);

        $workplan->title = 'Changed after approval';
        $workplan->save();

        $this->assertEquals('Original Title', $workplan->fresh()->title);
    }

    public function test_create_revision_produces_a_new_draft_pointing_back_via_supersedes()
    {
        $workplan = Workplan::factory()->approved()->create([
            'department_id' => $this->department->id,
            'organization_id' => $this->organization->id,
            'year' => 2026,
        ]);
        WorkplanActivity::factory()->create(['workplan_id' => $workplan->id]);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'create-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $workplan])
            ->call('createRevision');

        $v2 = Workplan::where('department_id', $this->department->id)->where('year', 2026)->where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $this->assertEquals('draft', $v2->status);
        $this->assertEquals($workplan->id, $v2->supersedes_workplan_id);
        $this->assertEquals(1, $v2->activities()->count(), 'Activities should be copied into the revision');
    }

    public function test_version_number_is_unique_per_department_and_year()
    {
        Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id, 'year' => 2026, 'version_number' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id, 'year' => 2026, 'version_number' => 1]);
    }
}
