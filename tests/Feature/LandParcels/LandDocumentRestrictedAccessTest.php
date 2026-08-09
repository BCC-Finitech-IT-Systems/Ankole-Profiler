<?php

namespace Tests\Feature\LandParcels;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LandDocument;
use App\Models\LandDocumentAudience;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandDocumentRestrictedAccessTest extends TestCase
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

    private function uploadedDocument(bool $restricted): LandDocument
    {
        $file = UploadedFile::fake()->create('court-order.pdf', 100, 'application/pdf');
        $path = $file->store('land-parcels/test', 'local');

        return LandDocument::create([
            'land_parcel_id' => $this->parcel->id,
            'document_type' => 'court_document',
            'version_number' => 1,
            'is_current' => true,
            'restricted' => $restricted,
            'path' => $path,
            'original_name' => 'court-order.pdf',
        ]);
    }

    private function viewerWithNoScope(array $permissions = ['view-land-parcels', 'download-land-documents'])
    {
        return $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, $permissions);
    }

    public function test_manager_scope_can_download_regardless_of_restriction()
    {
        $document = $this->uploadedDocument(true);
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-land-parcels', 'download-land-documents']);

        $response = $this->actingAs($admin)->get(route('land-parcels.documents.download', $document));

        $response->assertOk();
    }

    public function test_restricted_document_denies_a_user_outside_the_audience()
    {
        $document = $this->uploadedDocument(true);
        LandDocumentAudience::create(['land_document_id' => $document->id, 'role_name' => 'Some Other Role']);

        $outsider = $this->viewerWithNoScope();

        $response = $this->actingAs($outsider)->get(route('land-parcels.documents.download', $document));

        $response->assertForbidden();
        $this->assertEquals(0, AuditLog::where('event', 'document.downloaded')->count());
    }

    public function test_restricted_document_allows_a_matching_role_audience()
    {
        $document = $this->uploadedDocument(true);
        LandDocumentAudience::create(['land_document_id' => $document->id, 'role_name' => 'Organization Admin']);

        $matchingUser = $this->viewerWithNoScope();

        $response = $this->actingAs($matchingUser)->get(route('land-parcels.documents.download', $document));

        $response->assertOk();
        $this->assertEquals(1, AuditLog::where('event', 'document.downloaded')->count());
    }

    public function test_non_restricted_document_still_requires_manager_scope()
    {
        $document = $this->uploadedDocument(false);
        $outsider = $this->viewerWithNoScope();

        $response = $this->actingAs($outsider)->get(route('land-parcels.documents.download', $document));

        $response->assertForbidden();
    }
}
