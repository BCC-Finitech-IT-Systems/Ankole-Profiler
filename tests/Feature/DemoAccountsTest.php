<?php

namespace Tests\Feature;

use App\Livewire\Departments\DepartmentsDashboard;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectAffiliation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The login page's demo accounts, and the role scoping the client
 * presentation describes: the diocese admin sees every department, a
 * Head of Department only their own, and a plain member only their own
 * profile.
 */
class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function inOrganization(User $user)
    {
        $organizationId = $user->person->affiliations()->value('organization_id');

        return $this->actingAs($user)->withSession(['current_organization_id' => $organizationId]);
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $counts = fn () => [
            Project::count(), ProjectAffiliation::count(), Person::count(), User::count(),
            \App\Models\Policy::count(), \App\Models\Workplan::count(), \App\Models\WorkplanActivity::count(),
            \App\Models\Assignment::count(), \App\Models\LandParcel::count(), \App\Models\AuditReport::count(),
        ];
        $before = $counts();

        $this->seed(DemoAccountsSeeder::class);

        $this->assertSame($before, $counts());
    }

    public function test_login_page_offers_all_three_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Diocese admin')
            ->assertSee('Head of Department')
            ->assertSee(DemoAccountsSeeder::DIOCESE_ADMIN_EMAIL)
            ->assertSee(DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL);
    }

    public function test_demo_buttons_can_be_turned_off(): void
    {
        config(['app.demo_logins' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Head of Department')
            ->assertDontSee(DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL);
    }

    public function test_demo_accounts_can_sign_in(): void
    {
        foreach ([DemoAccountsSeeder::DIOCESE_ADMIN_EMAIL, DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL] as $email) {
            $this->post('/login', ['email' => $email, 'password' => DemoAccountsSeeder::PASSWORD]);
            $this->assertAuthenticatedAs($this->user($email));
            auth()->logout();
        }
    }

    public function test_head_of_department_sees_only_their_department(): void
    {
        $hod = $this->user(DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL);

        $this->inOrganization($hod)->get('/departments/dashboard')->assertOk();

        Livewire::actingAs($hod)
            ->test(DepartmentsDashboard::class)
            ->assertViewHas('departments', fn ($departments) => $departments->pluck('name')->all() === ['Education'])
            ->assertViewHas('selectedDepartmentProjects', fn ($projects) => $projects->pluck('code')->sort()->values()->all() === ['DEMO-MHS', 'DEMO-SJSS']);
    }

    public function test_diocese_admin_sees_every_department(): void
    {
        $admin = $this->user(DemoAccountsSeeder::DIOCESE_ADMIN_EMAIL);

        Livewire::actingAs($admin)
            ->test(DepartmentsDashboard::class)
            ->assertViewHas('departments', fn ($departments) => $departments->count() === 7);
    }

    public function test_diocese_admin_persons_list_loads(): void
    {
        $admin = $this->user(DemoAccountsSeeder::DIOCESE_ADMIN_EMAIL);

        $this->inOrganization($admin)->get('/persons/all')->assertOk()->assertDontSee('No persons found');
    }

    public function test_member_profile_page_renders(): void
    {
        $member = $this->user('grace.atuhaire.demo@gmail.com');

        $this->inOrganization($member)->get('/persons/profile-current')->assertOk()->assertSee('MEMBER');
    }

    public function test_plain_member_cannot_browse_the_directory(): void
    {
        $member = $this->user('grace.atuhaire.demo@gmail.com');

        foreach (['/persons/all', '/persons/search', '/persons/export', '/communication/send', '/relationships'] as $path) {
            $this->inOrganization($member)->get($path)->assertForbidden();
        }
    }

    public function test_head_of_department_dashboard_is_the_department_dashboard(): void
    {
        $this->inOrganization($this->user(DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL))
            ->get('/dashboard')
            ->assertRedirect(route('departments.dashboard'));
    }

    public function test_demo_modules_have_records_for_the_diocese_admin(): void
    {
        $this->assertModulesShowDemoRecords(DemoAccountsSeeder::DIOCESE_ADMIN_EMAIL);
    }

    public function test_demo_modules_have_records_for_the_head_of_department(): void
    {
        $this->assertModulesShowDemoRecords(DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL);
    }

    private function assertModulesShowDemoRecords(string $email): void
    {
        $user = $this->user($email);

        foreach (['/policies', '/workplans', '/assignments', '/land-parcels', '/audit-reports'] as $path) {
            $this->inOrganization($user)->get($path)->assertOk()->assertSee('(Demo)');
        }
    }
}
