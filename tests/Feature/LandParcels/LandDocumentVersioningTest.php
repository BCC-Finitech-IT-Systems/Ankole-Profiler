<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelDetail;
use App\Models\LandDocument;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandDocumentVersioningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private LandParcel $parcel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->organization = Organization::factory()->create();
        $this->parcel = LandParcel::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function admin()
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, [
            'view-land-parcels', 'edit-land-parcels', 'upload-land-documents',
        ]);
    }

    public function test_uploading_a_second_version_flips_the_prior_version_current_flag()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('document_type', 'survey_report')
            ->set('document', UploadedFile::fake()->create('survey-v1.pdf', 100, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $v1 = LandDocument::where('land_parcel_id', $this->parcel->id)->where('document_type', 'survey_report')->first();
        $this->assertEquals(1, $v1->version_number);
        $this->assertTrue($v1->is_current);

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('document_type', 'survey_report')
            ->set('document', UploadedFile::fake()->create('survey-v2.pdf', 100, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $v1->refresh();
        $v2 = LandDocument::where('land_parcel_id', $this->parcel->id)->where('document_type', 'survey_report')->where('version_number', 2)->first();

        $this->assertFalse($v1->is_current);
        $this->assertNotNull($v2);
        $this->assertTrue($v2->is_current);
        $this->assertTrue(Storage::disk('local')->exists($v1->path), 'Prior version file must not be deleted');
    }

    public function test_distinct_document_types_version_independently()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('document_type', 'survey_report')
            ->set('document', UploadedFile::fake()->create('survey.pdf', 100, 'application/pdf'))
            ->call('uploadDocument');

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('document_type', 'title_copy')
            ->set('document', UploadedFile::fake()->create('title.pdf', 100, 'application/pdf'))
            ->call('uploadDocument');

        $this->assertEquals(1, LandDocument::where('land_parcel_id', $this->parcel->id)->where('document_type', 'survey_report')->max('version_number'));
        $this->assertEquals(1, LandDocument::where('land_parcel_id', $this->parcel->id)->where('document_type', 'title_copy')->max('version_number'));
    }
}
