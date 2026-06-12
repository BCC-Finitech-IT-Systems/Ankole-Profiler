<?php

namespace Tests\Feature;

use App\Livewire\Organizations\ReviewMembershipApplications;
use App\Livewire\Person\PersonSelfRegistrationComponent;
use App\Models\AllowedEmailDomain;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PersonSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('Person', 'web');
        $this->diocese = Organization::factory()->create(['is_super' => false, 'category' => 'diocese']);
        AllowedEmailDomain::create(['domain' => 'example.com', 'is_active' => true]);
    }

    private function register(array $overrides = [])
    {
        return Livewire::test(PersonSelfRegistrationComponent::class)
            ->set('form', array_merge([
                'given_name' => 'Grace',
                'middle_name' => '',
                'family_name' => 'Tumusiime',
                'date_of_birth' => '1995-03-22',
                'gender' => 'Female',
                'phone' => '+256782345678',
                'email' => 'grace@example.com',
                'address' => '45 Kamukuzi Hill',
                'country' => 'Uganda',
                'district' => 'Mbarara',
                'city' => 'Mbarara',
                'organization_id' => $this->diocese->id,
            ], $overrides))
            ->call('submit');
    }

    public function test_registration_creates_pending_application_without_roles()
    {
        $this->register()->assertHasNoErrors();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $affiliation = PersonAffiliation::where('person_id', $user->person->id)->firstOrFail();

        $this->assertSame('pending', $affiliation->status);
        $this->assertSame($this->diocese->id, $affiliation->organization_id);
        $this->assertSame('MEMBER', $affiliation->role_type);
        $this->assertNull($affiliation->department_id);
        $this->assertNull($affiliation->start_date);
        $this->assertCount(0, $user->roles);
    }

    public function test_registration_rejects_super_organization_as_target()
    {
        $super = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);

        $this->register(['organization_id' => $super->id])
            ->assertHasErrors(['form.organization_id']);

        $this->assertNull(User::where('email', 'grace@example.com')->first());
    }

    public function test_approval_activates_membership_and_assigns_person_role()
    {
        $this->register();
        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $affiliation = PersonAffiliation::where('person_id', $user->person->id)->firstOrFail();

        $admin = $this->affiliatedUser('Organization Admin', $this->diocese, permissions: ['approve-organization-membership']);

        Livewire::actingAs($admin)
            ->test(ReviewMembershipApplications::class)
            ->call('approve', $affiliation->id);

        $affiliation->refresh();
        $this->assertSame('active', $affiliation->status);
        $this->assertNotNull($affiliation->start_date);
        $this->assertTrue($user->fresh()->hasRole('Person'));
    }

    public function test_rejection_keeps_user_without_roles()
    {
        $this->register();
        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $affiliation = PersonAffiliation::where('person_id', $user->person->id)->firstOrFail();

        $admin = $this->affiliatedUser('Organization Admin', $this->diocese, permissions: ['approve-organization-membership']);

        Livewire::actingAs($admin)
            ->test(ReviewMembershipApplications::class)
            ->call('reject', $affiliation->id);

        $this->assertSame('rejected', $affiliation->fresh()->status);
        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_admin_of_another_diocese_cannot_approve()
    {
        $this->register();
        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $affiliation = PersonAffiliation::where('person_id', $user->person->id)->firstOrFail();

        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $admin = $this->affiliatedUser('Organization Admin', $otherDiocese, permissions: ['approve-organization-membership']);

        Livewire::actingAs($admin)
            ->test(ReviewMembershipApplications::class)
            ->call('approve', $affiliation->id)
            ->assertForbidden();

        $this->assertSame('pending', $affiliation->fresh()->status);
    }

    public function test_pending_listing_is_scoped_to_managed_organizations()
    {
        $this->register();

        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $admin = $this->affiliatedUser('Organization Admin', $otherDiocese, permissions: ['approve-organization-membership']);

        Livewire::actingAs($admin)
            ->test(ReviewMembershipApplications::class)
            ->assertDontSee('Grace');
    }
}
