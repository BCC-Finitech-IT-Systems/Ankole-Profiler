<?php

namespace App\Livewire\Policies;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Notifications\PolicyExceptionDecided;
use App\Services\AuditLogger;
use App\Services\PolicyNotificationTargets;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PolicyAdoptionDashboard extends Component
{
    use WithPagination;

    public string $policyFilter = '';
    public string $institutionFilter = '';
    public string $departmentFilter = '';
    public string $statusFilter = '';

    protected $queryString = [
        'policyFilter' => ['except' => ''],
        'institutionFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    private function dioceseIds()
    {
        $user = Auth::user();

        return $user->managedOrganizationIds()
            ->intersect(Organization::where('category', 'diocese')->pluck('id'));
    }

    private function basePublicationsQuery()
    {
        return PolicyPublication::whereHas('policy', function ($q) {
            $q->whereIn('organization_id', $this->dioceseIds());
        });
    }

    public function approveException(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('decideException', $publication);

        $publication->update([
            'exception_status' => 'approved',
            'exception_decided_by' => Auth::id(),
            'exception_decided_at' => now(),
        ]);

        AuditLogger::record($publication, 'adoption.exception_decided', ['decision' => 'approved'], $publication->policy->organization_id, $publication->organization_id);

        foreach (PolicyNotificationTargets::forInstitution($publication->organization) as $recipient) {
            $recipient->notify(new PolicyExceptionDecided($publication));
        }

        session()->flash('success', 'Exception approved.');
    }

    public function rejectException(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('decideException', $publication);

        $publication->update([
            'exception_status' => 'rejected',
            'exception_decided_by' => Auth::id(),
            'exception_decided_at' => now(),
            'status' => 'sent',
        ]);

        AuditLogger::record($publication, 'adoption.exception_decided', ['decision' => 'rejected'], $publication->policy->organization_id, $publication->organization_id);

        foreach (PolicyNotificationTargets::forInstitution($publication->organization) as $recipient) {
            $recipient->notify(new PolicyExceptionDecided($publication));
        }

        session()->flash('success', 'Exception rejected.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-policy-dashboard'), 403);

        $dioceseIds = $this->dioceseIds();

        $publications = $this->basePublicationsQuery()
            ->with(['policy', 'organization'])
            ->when($this->policyFilter, fn ($q) => $q->where('policy_id', $this->policyFilter))
            ->when($this->institutionFilter, fn ($q) => $q->where('organization_id', $this->institutionFilter))
            ->when($this->departmentFilter, fn ($q) => $q->whereHas('policy', fn ($pq) => $pq->where('department_id', $this->departmentFilter)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));

        $total = (clone $publications)->count();
        $adopted = (clone $publications)->whereIn('status', ['adopted', 'partially_adopted'])->count();
        $overdue = (clone $publications)->overdue()->count();
        $pendingExceptions = (clone $this->basePublicationsQuery())->where('exception_status', 'requested')->count();

        return view('livewire.policies.policy-adoption-dashboard', [
            'publications' => $publications->orderByDesc('sent_at')->paginate(20),
            'exceptions' => (clone $this->basePublicationsQuery())->with(['policy', 'organization'])->where('exception_status', 'requested')->get(),
            'policies' => Policy::whereIn('organization_id', $dioceseIds)->orderBy('title')->get(['id', 'title']),
            'institutions' => Organization::whereIn('id', $dioceseIds)->get()
                ->flatMap(fn ($diocese) => Organization::whereIn('id', $diocese->subtreeIds())->where('id', '!=', $diocese->id)->get(['id', 'display_name', 'legal_name'])),
            'departments' => Department::whereIn('organization_id', $dioceseIds)->orderBy('name')->get(['id', 'name']),
            'coveragePercent' => $total > 0 ? round(($adopted / $total) * 100) : 0,
            'overdueCount' => $overdue,
            'pendingExceptionsCount' => $pendingExceptions,
        ]);
    }
}
