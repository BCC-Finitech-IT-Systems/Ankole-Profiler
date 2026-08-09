<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PoliciesManagement;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyCrudTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->diocese->id]);
    }

    private function dioceseAdmin(array $permissions = ['view-policies', 'create-policies', 'edit-policies', 'archive-policies'])
    {
        return $this->affiliatedUser('Organization Admin', $this->diocese, null, $permissions);
    }

    public function test_diocese_admin_can_create_a_policy_with_a_v1_draft_version()
    {
        $admin = $this->dioceseAdmin();

        Livewire::actingAs($admin)
            ->test(PoliciesManagement::class)
            ->call('create')
            ->set('title', 'Safeguarding Policy')
            ->set('reference_code', 'POL-001')
            ->set('policy_category', 'Safeguarding')
            ->set('organization_id', $this->diocese->id)
            ->call('save')
            ->assertHasNoErrors();

        $policy = Policy::where('reference_code', 'POL-001')->first();
        $this->assertNotNull($policy);
        $this->assertEquals('draft', $policy->status);
        $this->assertNotNull($policy->currentVersion);
        $this->assertEquals(1, $policy->currentVersion->version_number);
        $this->assertEquals('draft', $policy->currentVersion->status);
    }

    public function test_add_policy_button_hidden_without_create_permission()
    {
        $viewer = $this->dioceseAdmin(['view-policies']);

        $response = $this->actingAs($viewer)->get(route('policies.index'));

        $response->assertOk();
        $response->assertDontSee('Add Policy');
    }

    public function test_add_policy_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->dioceseAdmin();

        $response = $this->actingAs($admin)->get(route('policies.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['wire:id', 'Add Policy'], false);
    }

    public function test_editing_a_policy_requires_diocese_or_department_scope()
    {
        $policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);

        $outsider = $this->affiliatedUser('Organization Admin', Organization::factory()->diocese()->create(), null, ['view-policies', 'edit-policies']);

        Livewire::actingAs($outsider)
            ->test(PoliciesManagement::class)
            ->call('edit', $policy->id)
            ->assertForbidden();
    }

    public function test_search_and_filter_scope_to_managed_dioceses_and_departments()
    {
        $admin = $this->dioceseAdmin();

        $mine = Policy::factory()->create([
            'organization_id' => $this->diocese->id,
            'title' => 'Finance Policy',
            'policy_category' => 'Finance',
        ]);

        $otherDiocese = Organization::factory()->diocese()->create();
        Policy::factory()->create(['organization_id' => $otherDiocese->id, 'title' => 'Other Diocese Policy']);

        $component = Livewire::actingAs($admin)->test(PoliciesManagement::class);

        $component->assertSee('Finance Policy')->assertDontSee('Other Diocese Policy');

        $component->set('search', 'Finance')->assertSee('Finance Policy');
        $component->set('search', 'Nonexistent')->assertDontSee('Finance Policy');
    }

    public function test_archive_requires_permission_and_scope()
    {
        $admin = $this->dioceseAdmin();
        $policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);

        Livewire::actingAs($admin)
            ->test(PoliciesManagement::class)
            ->call('confirmArchive', $policy->id)
            ->call('archive')
            ->assertHasNoErrors();

        $this->assertEquals('archived', $policy->fresh()->status);
    }
}
