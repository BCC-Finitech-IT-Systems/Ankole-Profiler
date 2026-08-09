<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelDashboard;
use App\Models\LandDocument;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandParcelDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    private function admin()
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-land-dashboard']);
    }

    public function test_bucket_counts_are_correct()
    {
        LandParcel::factory()->create(['organization_id' => $this->organization->id, 'stage' => 'title_issued']);
        LandParcel::factory()->create(['organization_id' => $this->organization->id, 'stage' => 'disputed']);
        LandParcel::factory()->create([
            'organization_id' => $this->organization->id, 'stage' => 'submitted',
            'expected_completion_date' => now()->subDays(5),
        ]);
        LandParcel::factory()->create([
            'organization_id' => $this->organization->id, 'stage' => 'title_issued',
            'lease_expiry_date' => now()->addDays(30),
        ]);

        $component = Livewire::actingAs($this->admin())->test(LandParcelDashboard::class);

        $this->assertEquals(4, $component->viewData('total'));
        $this->assertEquals(2, $component->viewData('completedTitlesCount'));
        $this->assertEquals(1, $component->viewData('disputedCount'));
        $this->assertEquals(1, $component->viewData('delayedCount'));
        $this->assertEquals(1, $component->viewData('expiringLeaseCount'));
    }

    public function test_missing_documents_bucket_flags_parcels_lacking_required_types()
    {
        $complete = LandParcel::factory()->create(['organization_id' => $this->organization->id]);
        LandDocument::factory()->create(['land_parcel_id' => $complete->id, 'document_type' => 'purchase_agreement']);
        LandDocument::factory()->create(['land_parcel_id' => $complete->id, 'document_type' => 'survey_report']);

        $incomplete = LandParcel::factory()->create(['organization_id' => $this->organization->id]);
        LandDocument::factory()->create(['land_parcel_id' => $incomplete->id, 'document_type' => 'survey_report']);

        $component = Livewire::actingAs($this->admin())->test(LandParcelDashboard::class);

        $this->assertEquals(1, $component->viewData('missingDocumentsCount'));
    }
}
