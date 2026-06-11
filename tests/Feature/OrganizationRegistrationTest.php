<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Organizations\Create;

class OrganizationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The create form loads its category list from the
        // department_sub_categories table, so the categories used in these
        // tests must exist there.
        $organization = Organization::factory()->create();
        $department = \App\Models\Department::create([
            'organization_id' => $organization->id,
            'name' => 'General',
        ]);

        foreach (['hospital', 'school'] as $category) {
            \App\Models\DepartmentSubCategory::create([
                'department_id' => $department->id,
                'name' => $category,
                'is_active' => true,
            ]);
        }
    }

    public function test_organization_creation_form_renders()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/organizations/create')
            ->assertStatus(200)
            ->assertSeeLivewire('organizations.create');
    }

    public function test_can_create_hospital_organization()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('category', 'hospital')
            ->set('legal_name', 'Mulago National Referral Hospital')
            ->set('display_name', 'Mulago Hospital')
            ->set('code', 'MNH001')
            ->set('registration_number', 'HOSP/2023/001')
            ->set('date_established', '1962-01-15')
            ->set('contact_email', 'info@mulagohospital.go.ug')
            ->set('contact_phone', '+256414530692')
            ->set('address_line_1', 'Mulago Hill')
            ->set('city', 'Kampala')
            ->set('country', 'UGA')
            ->set('primary_contact_name', 'Dr. Byarugaba Baterana')
            ->set('primary_contact_email', 'director@mulagohospital.go.ug')
            ->set('primary_contact_phone', '+256782123456')
            ->set('hospital_details.hospital_type', 'REFERRAL')
            ->set('hospital_details.bed_capacity', 1500)
            ->call('submit')
            ->assertRedirect('/organizations');

        $this->assertDatabaseHas('organizations', [
            'legal_name' => 'Mulago National Referral Hospital',
            'category' => 'hospital',
            'code' => 'MNH001'
        ]);
    }

    public function test_can_create_school_organization()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('category', 'school')
            ->set('legal_name', 'Makerere University')
            ->set('display_name', 'Makerere University')
            ->set('code', 'MAK001')
            ->set('registration_number', 'UNIV/1922/001')
            ->set('date_established', '1922-06-01')
            ->set('contact_email', 'info@mak.ac.ug')
            ->set('contact_phone', '+256414532694')
            ->set('address_line_1', 'Makerere Hill')
            ->set('city', 'Kampala')
            ->set('country', 'UGA')
            ->set('primary_contact_name', 'Prof. Barnabas Nawangwe')
            ->set('primary_contact_email', 'vc@mak.ac.ug')
            ->set('primary_contact_phone', '+256782654321')
            ->set('school_details.school_type', 'UNIVERSITY')
            ->set('school_details.student_capacity', 50000)
            ->set('school_details.current_enrollment', 40000)
            ->set('school_details.number_of_teachers', 1500)
            ->call('submit')
            ->assertRedirect('/organizations');

        $this->assertDatabaseHas('organizations', [
            'legal_name' => 'Makerere University',
            'category' => 'school',
            'code' => 'MAK001'
        ]);
    }

    public function test_validation_rules_work()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('category', 'hospital')
            ->set('legal_name', '') // Required field
            ->set('contact_email', 'invalid-email') // Invalid email
            ->call('submit')
            ->assertHasErrors(['legal_name', 'contact_email']);
    }

    public function test_multi_step_navigation_works()
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Create::class);

        // Start at step 1
        $component->assertSet('currentStep', 1);

        // Can't proceed without selecting category
        $component->call('nextStep')
            ->assertHasErrors(['category']);

        // Select category and proceed
        $component->set('category', 'hospital')
            ->call('nextStep')
            ->assertSet('currentStep', 2);

        // Can go back to previous step
        $component->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    public function test_category_must_exist_in_sub_categories_table()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('category', 'nonexistent-category')
            ->call('nextStep')
            ->assertHasErrors(['category']);
    }
}
