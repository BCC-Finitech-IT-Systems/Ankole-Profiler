<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diocese = Organization::factory()->create(['is_super' => false]);

        foreach ([
            'manage-users', 'manage-roles', 'manage-permissions',
            'manage-role-types', 'manage-email-domains', 'manage-dioceses',
            'view-departments', 'create-departments', 'edit-departments', 'delete-departments',
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
    }

    /** @dataProvider adminRoutes */
    public function test_unauthenticated_user_is_redirected(string $route, string $permission): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    /** @dataProvider adminRoutes */
    public function test_user_without_permission_gets_403(string $route, string $permission): void
    {
        Role::findOrCreate('Person', 'web');
        $user = $this->affiliatedUser('Person', $this->diocese);

        $this->actingAs($user)->get(route($route))->assertForbidden();
    }

    /** @dataProvider adminRoutes */
    public function test_user_with_permission_can_access(string $route, string $permission): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $admin = $this->affiliatedUser('Super Admin', $this->diocese, permissions: [$permission]);

        // Super Admin is short-circuited by Gate::before, so just verify no 403/redirect.
        $response = $this->actingAs($admin)->get(route($route));
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
    }

    public static function adminRoutes(): array
    {
        return [
            'users index'        => ['admin.users.index',       'manage-users'],
            'roles index'        => ['admin.roles.index',       'manage-roles'],
            'permissions index'  => ['admin.permissions.index', 'manage-permissions'],
            'role-types index'   => ['admin.role-types.index',  'manage-role-types'],
            'email domains'      => ['admin.allowEmailDomains', 'manage-email-domains'],
            'dioceses index'     => ['admin.dioceses.index',    'manage-dioceses'],
        ];
    }

    public function test_department_index_requires_view_departments(): void
    {
        Role::findOrCreate('Person', 'web');
        $user = $this->affiliatedUser('Person', $this->diocese);

        $this->actingAs($user)->get(route('departments.index'))->assertForbidden();
    }

    public function test_department_store_requires_create_departments(): void
    {
        Role::findOrCreate('Person', 'web');
        $user = $this->affiliatedUser('Person', $this->diocese);

        $this->actingAs($user)->post(route('departments.store'), [])->assertForbidden();
    }

    public function test_livewire_admin_action_without_permission_is_forbidden(): void
    {
        Role::findOrCreate('Person', 'web');
        $user = $this->affiliatedUser('Person', $this->diocese);

        $this->actingAs($user);

        // Livewire bypasses route middleware on /livewire/update; the mount()
        // Gate::authorize must block unprivileged users.
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\UserManager::class)
            ->assertForbidden();
    }

    public function test_user_manager_forbids_self_delete(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $admin = $this->affiliatedUser('Super Admin', $this->diocese, permissions: ['manage-users']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManager::class)
            ->set('userId', $admin->id)
            ->call('deleteUser')
            ->assertForbidden();
    }
}
