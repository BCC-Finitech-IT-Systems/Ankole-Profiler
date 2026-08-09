<?php

namespace App\Livewire\Policies;

use App\Models\AuditLog;
use App\Models\Policy;
use Livewire\Component;
use Livewire\WithPagination;

class PolicyAuditTrail extends Component
{
    use WithPagination;

    public Policy $policy;

    public function mount(Policy $policy): void
    {
        // view-audit-logs alone isn't diocese-scoped, so also require the
        // caller to be authorized against this specific policy's diocese —
        // otherwise any Organization Admin in any diocese could read
        // another diocese's audit trail by guessing a policy id.
        $this->authorize('viewAny', AuditLog::class);
        $this->authorize('view', $policy);
        $this->policy = $policy;
    }

    public function render()
    {
        $versionIds = $this->policy->versions()->pluck('id');
        $publicationIds = $this->policy->publications()->pluck('policy_publications.id');

        $logs = AuditLog::query()
            ->where(function ($q) use ($versionIds, $publicationIds) {
                $q->where(function ($qq) {
                    $qq->where('auditable_type', Policy::class)->where('auditable_id', $this->policy->id);
                })
                ->orWhere(function ($qq) use ($versionIds) {
                    $qq->where('auditable_type', \App\Models\PolicyVersion::class)->whereIn('auditable_id', $versionIds);
                })
                ->orWhere(function ($qq) use ($publicationIds) {
                    $qq->where('auditable_type', \App\Models\PolicyPublication::class)->whereIn('auditable_id', $publicationIds);
                });
            })
            ->with('actor')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.policies.policy-audit-trail', [
            'logs' => $logs,
        ]);
    }
}
