<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait BuildsAffiliatedUsers
{
    /**
     * Create a User with a linked Person and an active affiliation to the
     * given organization (and optional department), holding the given
     * Spatie role. Permissions are created on demand.
     */
    protected function affiliatedUser(
        string $role,
        Organization $organization,
        ?Department $department = null,
        array $permissions = [],
        string $status = 'active',
    ): User {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'department_id' => $department?->id,
            'user_id' => $user->id,
            'status' => $status,
        ]);

        $roleModel = Role::findOrCreate($role, 'web');
        foreach ($permissions as $permission) {
            $roleModel->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user->assignRole($roleModel);

        return $user->fresh();
    }
}
