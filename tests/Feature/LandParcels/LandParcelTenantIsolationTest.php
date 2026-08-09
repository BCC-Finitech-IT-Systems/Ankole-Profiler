<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelDashboard;
use App\Livewire\LandParcels\LandParcelDetail;
use App\Livewire\LandParcels\LandParcelsManagement;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandParcelTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $orgA;
    private Organization $orgB;
    private LandParcel $parcelA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();
        $this->parcelA = LandParcel::factory()->create(['organization_id' => $this->orgA->id, 'property_name' => 'Org A Parcel']);
    }

    public function test_list_excludes_other_organizations()
    {
        LandParcel::factory()->create(['organization_id' => $this->orgB->id, 'property_name' => 'Org B Parcel']);

        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-land-parcels']);

        Livewire::actingAs($adminB)
            ->test(LandParcelsManagement::class)
            ->assertDontSee('Org A Parcel')
            ->assertSee('Org B Parcel');
    }

    public function test_direct_show_of_a_foreign_parcel_is_forbidden()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-land-parcels']);

        Livewire::actingAs($adminB)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcelA])
            ->assertForbidden();
    }

    public function test_dashboard_excludes_other_organizations()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-land-dashboard']);

        Livewire::actingAs($adminB)
            ->test(LandParcelDashboard::class)
            ->assertViewHas('total', 0);
    }
}
