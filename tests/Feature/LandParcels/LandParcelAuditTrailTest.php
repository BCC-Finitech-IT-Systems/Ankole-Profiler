<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelDetail;
use App\Models\AuditLog;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandParcelAuditTrailTest extends TestCase
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

    private function admin(array $extra = [])
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, array_merge([
            'view-land-parcels', 'edit-land-parcels', 'upload-land-documents',
        ], $extra));
    }

    public function test_payment_recorded_is_audited()
    {
        Livewire::actingAs($this->admin())
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->call('openPaymentForm')
            ->set('payment_amount', 500000)
            ->set('payment_paid_on', now()->toDateString())
            ->call('recordPayment');

        $this->assertEquals(1, AuditLog::where('event', 'land_parcel.payment_recorded')->where('auditable_id', $this->parcel->id)->count());
    }

    public function test_document_upload_and_custody_location_change_are_audited()
    {
        Livewire::actingAs($this->admin())
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('document_type', 'survey_report')
            ->set('custody_location', 'Diocese Registry Safe')
            ->set('document', UploadedFile::fake()->create('survey.pdf', 100, 'application/pdf'))
            ->call('uploadDocument');

        $this->assertEquals(1, AuditLog::where('event', 'land_document.uploaded')->where('auditable_id', $this->parcel->id)->count());
        $this->assertEquals(1, AuditLog::where('event', 'document.custody_moved')->where('auditable_id', $this->parcel->id)->count());
    }

    public function test_dispute_decision_is_audited()
    {
        $admin = $this->admin(['manage-land-disputes']);

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->call('markDisputed');

        $this->assertEquals(1, AuditLog::where('event', 'land_parcel.disputed')->where('auditable_id', $this->parcel->id)->count());
    }
}
