<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDashboard;
use App\Livewire\Assignments\AssignmentDetail;
use App\Livewire\Assignments\AssignmentsManagement;
use App\Models\Assignment;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $orgA;
    private Organization $orgB;
    private Assignment $assignmentA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();
        $this->assignmentA = Assignment::factory()->create(['organization_id' => $this->orgA->id, 'title' => 'Org A Assignment']);
    }

    public function test_list_excludes_other_organizations()
    {
        Assignment::factory()->create(['organization_id' => $this->orgB->id, 'title' => 'Org B Assignment']);

        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-assignments']);

        Livewire::actingAs($adminB)
            ->test(AssignmentsManagement::class)
            ->assertDontSee('Org A Assignment')
            ->assertSee('Org B Assignment');
    }

    public function test_direct_show_of_a_foreign_assignment_is_forbidden()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-assignments']);

        Livewire::actingAs($adminB)
            ->test(AssignmentDetail::class, ['assignment' => $this->assignmentA])
            ->assertForbidden();
    }

    public function test_dashboard_excludes_other_organizations()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-assignment-dashboard']);

        Livewire::actingAs($adminB)
            ->test(AssignmentDashboard::class)
            ->assertViewHas('total', 0);
    }
}
