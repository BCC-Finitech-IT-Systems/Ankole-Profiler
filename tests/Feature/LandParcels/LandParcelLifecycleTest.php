<?php

namespace Tests\Feature\LandParcels;

use App\Livewire\LandParcels\LandParcelDetail;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LandParcel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class LandParcelLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;
    private LandParcel $parcel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
        $this->parcel = LandParcel::factory()->create([
            'organization_id' => $this->organization->id, 'department_id' => $this->department->id,
        ]);
    }

    public function test_stage_transition_writes_a_distinct_audit_row()
    {
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-land-parcels', 'edit-land-parcels']);

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->set('stage', 'documents_gathering')
            ->call('updateStage')
            ->assertHasNoErrors();

        $this->assertEquals('documents_gathering', $this->parcel->fresh()->stage);

        $log = AuditLog::where('event', 'land_parcel.field_changed')
            ->where('auditable_id', $this->parcel->id)
            ->get()
            ->firstWhere(fn ($l) => $l->properties['field'] === 'stage');

        $this->assertNotNull($log);
        $this->assertEquals('unregistered', $log->properties['old']);
        $this->assertEquals('documents_gathering', $log->properties['new']);
    }

    public function test_department_manager_cannot_mark_or_resolve_a_dispute()
    {
        $manager = $this->affiliatedUser('Department Manager', $this->organization, $this->department, [
            'view-land-parcels', 'edit-land-parcels', 'manage-land-disputes',
        ]);

        // Even with the permission granted, org-scope is required —
        // Department Manager's managedOrganizationIds() is empty.
        Livewire::actingAs($manager)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->call('markDisputed')
            ->assertForbidden();
    }

    public function test_org_admin_can_mark_and_resolve_a_dispute()
    {
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-land-parcels', 'manage-land-disputes']);

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel])
            ->call('markDisputed')
            ->assertHasNoErrors();

        $this->assertEquals('disputed', $this->parcel->fresh()->stage);

        Livewire::actingAs($admin)
            ->test(LandParcelDetail::class, ['parcel' => $this->parcel->fresh()])
            ->call('resolveDispute', 'under_review')
            ->assertHasNoErrors();

        $this->assertEquals('under_review', $this->parcel->fresh()->stage);
    }
}
