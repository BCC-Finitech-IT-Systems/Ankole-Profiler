<?php

namespace Tests\Feature;

use App\Livewire\Admin\DioceseManager;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class DioceseManagerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $superOrg;
    private Organization $diocese;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('manage-dioceses', 'web');
        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('Organization Admin', 'web');

        $this->superOrg = Organization::factory()->create([
            'is_super' => true,
            'organization_type' => 'super',
        ]);
        $this->diocese = Organization::factory()->create([
            'category' => 'diocese',
            'organization_type' => 'branch',
            'parent_organization_id' => $this->superOrg->id,
        ]);
    }

    public function test_super_admin_can_create_diocese()
    {
        $admin = $this->affiliatedUser('Super Admin', $this->superOrg);

        Livewire::actingAs($admin)
            ->test(DioceseManager::class)
            ->set('legal_name', 'North Ankole Diocese')
            ->set('code', 'NAD-001')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('organizations', [
            'legal_name' => 'North Ankole Diocese',
            'category' => 'diocese',
            'parent_organization_id' => $this->superOrg->id,
        ]);
    }

    public function test_org_admin_cannot_create_diocese()
    {
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['manage-dioceses']
        );

        Livewire::actingAs($admin)
            ->test(DioceseManager::class)
            ->set('legal_name', 'Rogue Diocese')
            ->set('code', 'RGE-001')
            ->call('create')
            ->assertForbidden();

        $this->assertDatabaseMissing('organizations', ['legal_name' => 'Rogue Diocese']);
    }

    public function test_org_admin_can_update_own_diocese()
    {
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['manage-dioceses']
        );

        Livewire::actingAs($admin)
            ->test(DioceseManager::class)
            ->call('edit', $this->diocese->id)
            ->set('legal_name', 'Renamed Diocese')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Renamed Diocese', $this->diocese->fresh()->legal_name);
    }

    public function test_org_admin_cannot_update_foreign_diocese()
    {
        $foreign = Organization::factory()->create([
            'category' => 'diocese',
            'organization_type' => 'branch',
        ]);

        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['manage-dioceses']
        );

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)
            ->test(DioceseManager::class)
            ->call('edit', $foreign->id);

        $this->assertNotSame('Renamed Diocese', $foreign->fresh()->legal_name);
    }

    public function test_user_without_permission_cannot_mount_component()
    {
        Role::findOrCreate('Person', 'web');
        $user = $this->affiliatedUser('Person', $this->diocese);

        Livewire::actingAs($user)
            ->test(DioceseManager::class)
            ->assertForbidden();
    }
}
