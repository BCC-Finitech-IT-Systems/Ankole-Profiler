<?php

namespace Tests\Feature;

use App\Livewire\Organizations\Index;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * confirmingDeleteId is a public Livewire property, so a client can set it to
 * any id and call the action directly without ever touching the UI. These
 * tests pin the server-side guards that make that harmless.
 */
class OrganizationDeletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_access_cannot_delete_an_organization(): void
    {
        $victim = Organization::factory()->create();
        $attacker = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($attacker)
            ->test(Index::class)
            ->set('confirmingDeleteId', $victim->id)
            ->call('deleteOrganization');
    }

    public function test_the_organization_survives_an_unauthorized_delete_attempt(): void
    {
        $victim = Organization::factory()->create();
        $attacker = User::factory()->create();

        try {
            Livewire::actingAs($attacker)
                ->test(Index::class)
                ->set('confirmingDeleteId', $victim->id)
                ->call('deleteOrganization');
        } catch (ModelNotFoundException|AuthorizationException $e) {
            // expected
        }

        $this->assertNull($victim->fresh()->deleted_at, 'organization was deleted by an unauthorized user');
    }

    public function test_super_admin_can_delete_an_organization(): void
    {
        $org = Organization::factory()->create();
        Role::findOrCreate('Super Admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('confirmingDeleteId', $org->id)
            ->call('deleteOrganization');

        $this->assertNotNull($org->fresh()->deleted_at, 'Super Admin should still be able to delete');
    }
}
