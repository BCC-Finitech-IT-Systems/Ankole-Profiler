<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDashboard;
use App\Models\Assignment;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentOverdueTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    public function test_is_overdue_reflects_past_due_date_and_open_status()
    {
        $overdue = Assignment::factory()->create([
            'organization_id' => $this->organization->id, 'status' => 'in_progress', 'due_date' => now()->subDays(3),
        ]);
        $notYetDue = Assignment::factory()->create([
            'organization_id' => $this->organization->id, 'status' => 'in_progress', 'due_date' => now()->addDays(3),
        ]);
        $completedPastDue = Assignment::factory()->create([
            'organization_id' => $this->organization->id, 'status' => 'completed', 'due_date' => now()->subDays(3),
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($notYetDue->isOverdue());
        $this->assertFalse($completedPastDue->isOverdue());
    }

    public function test_revised_due_date_takes_precedence_over_original_due_date()
    {
        $assignment = Assignment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'in_progress',
            'due_date' => now()->subDays(10),
            'revised_due_date' => now()->addDays(5),
        ]);

        $this->assertFalse($assignment->isOverdue());
    }

    public function test_dashboard_overdue_bucket_matches_model_calculation()
    {
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'in_progress', 'due_date' => now()->subDay()]);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'in_progress', 'due_date' => now()->addDay()]);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-assignment-dashboard']);

        Livewire::actingAs($admin)
            ->test(AssignmentDashboard::class)
            ->assertViewHas('overdueCount', 1);
    }
}
