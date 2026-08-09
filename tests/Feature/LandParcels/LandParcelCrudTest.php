<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelsManagement;
use App\Models\Department;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandParcelCrudTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function admin(array $permissions = ['view-land-parcels', 'create-land-parcels', 'edit-land-parcels'])
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, $permissions);
    }

    public function test_admin_can_register_a_parcel()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(LandParcelsManagement::class)
            ->call('create')
            ->set('organization_id', $this->organization->id)
            ->set('department_id', $this->department->id)
            ->set('reference_number', 'LP-0001')
            ->set('property_name', 'Cathedral Plot')
            ->call('save')
            ->assertHasNoErrors();

        $parcel = LandParcel::where('reference_number', 'LP-0001')->first();
        $this->assertNotNull($parcel);
        $this->assertEquals('unregistered', $parcel->stage);
    }

    public function test_reference_number_is_unique_per_organization()
    {
        LandParcel::factory()->create(['organization_id' => $this->organization->id, 'reference_number' => 'LP-0001']);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(LandParcelsManagement::class)
            ->call('create')
            ->set('organization_id', $this->organization->id)
            ->set('reference_number', 'LP-0001')
            ->set('property_name', 'Another Plot')
            ->call('save')
            ->assertHasErrors(['reference_number']);
    }

    public function test_add_parcel_button_hidden_without_create_permission()
    {
        $viewer = $this->admin(['view-land-parcels']);

        $response = $this->actingAs($viewer)->get(route('land-parcels.index'));

        $response->assertOk();
        $response->assertDontSee('Add Parcel');
    }

    public function test_add_parcel_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('land-parcels.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['wire:id', 'Add Parcel'], false);
    }

    public function test_outsider_department_cannot_edit()
    {
        $parcel = LandParcel::factory()->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);

        $outsider = $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, ['view-land-parcels', 'edit-land-parcels']);

        Livewire::actingAs($outsider)
            ->test(\App\Livewire\LandParcels\LandParcelDetail::class, ['parcel' => $parcel])
            ->assertForbidden();
    }
}
