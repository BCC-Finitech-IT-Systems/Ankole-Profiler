<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyAuditTrail;
use App\Livewire\Policies\PolicyDetail;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyAuditTrailTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Policy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
        $version = PolicyVersion::factory()->create(['policy_id' => $this->policy->id, 'version_number' => 1]);
        $this->policy->update(['current_version_id' => $version->id]);
    }

    public function test_approve_action_writes_exactly_one_audit_row()
    {
        $admin = $this->affiliatedUser('Organization Admin', $this->diocese, null, ['view-policies', 'approve-policies']);
        $version = $this->policy->currentVersion;
        $version->update(['synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->call('approve');

        $this->assertEquals(1, AuditLog::where('event', 'policy_version.approved')
            ->where('auditable_type', PolicyVersion::class)
            ->where('auditable_id', $version->id)
            ->count());
    }

    public function test_audit_log_rows_are_immutable()
    {
        $log = AuditLog::create([
            'auditable_type' => Policy::class,
            'auditable_id' => $this->policy->id,
            'event' => 'policy.created',
            'created_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $log->update(['event' => 'tampered']);
    }

    public function test_audit_log_rows_cannot_be_deleted()
    {
        $log = AuditLog::create([
            'auditable_type' => Policy::class,
            'auditable_id' => $this->policy->id,
            'event' => 'policy.created',
            'created_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $log->delete();
    }

    public function test_user_without_view_audit_logs_permission_is_forbidden()
    {
        $viewer = $this->affiliatedUser('Organization Admin', $this->diocese, null, ['view-policies']);

        Livewire::actingAs($viewer)
            ->test(PolicyAuditTrail::class, ['policy' => $this->policy])
            ->assertForbidden();
    }

    public function test_audit_trail_is_isolated_between_dioceses()
    {
        $otherDiocese = Organization::factory()->diocese()->create();
        $otherAdmin = $this->affiliatedUser('Organization Admin', $otherDiocese, null, ['view-policies', 'view-audit-logs']);

        Livewire::actingAs($otherAdmin)
            ->test(PolicyAuditTrail::class, ['policy' => $this->policy])
            ->assertForbidden();
    }
}
