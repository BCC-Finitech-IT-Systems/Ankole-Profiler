<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The sidebar offers Import/Export Persons on the org-scoped permissions
 * (import-org-persons / export-org-persons) while the pages checked the
 * all-organizations ones — or, in export's case, nothing at all, leaving
 * person data reachable by anyone who could load the route. These pin both
 * halves so the menu and the page cannot disagree again.
 */
class PersonImportExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    private function userWith(array $permissions): User
    {
        $role = Role::findOrCreate('Import Export Test Role', 'web');

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        // org.access requires an active affiliation with the selected organization
        $person = Person::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        return $user;
    }

    private function visit(User $user, string $path)
    {
        return $this->actingAs($user)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get($path);
    }

    public static function pageProvider(): array
    {
        return [
            'import' => ['/persons/import', 'import-org-persons', 'import-persons'],
            'export' => ['/persons/export', 'export-org-persons', 'export-persons'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_the_org_scoped_permission_grants_access(string $path, string $orgScoped): void
    {
        $this->visit($this->userWith([$orgScoped]), $path)->assertSuccessful();
    }

    #[DataProvider('pageProvider')]
    public function test_the_all_organizations_permission_grants_access(string $path, string $orgScoped, string $global): void
    {
        $this->visit($this->userWith([$global]), $path)->assertSuccessful();
    }

    #[DataProvider('pageProvider')]
    public function test_holding_neither_permission_is_forbidden(string $path): void
    {
        $this->visit($this->userWith(['some-unrelated-permission']), $path)->assertForbidden();
    }
}
