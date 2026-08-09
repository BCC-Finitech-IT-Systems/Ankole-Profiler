<?php

namespace App\Livewire\Policies;

use App\Models\Person;
use App\Models\PolicyDocument;
use App\Models\PolicyPublication;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PolicyAdoptionTracker extends Component
{
    use WithPagination, WithFileUploads;

    public string $statusFilter = '';
    public ?int $organizationFilter = null;

    public ?int $activePublicationId = null;

    public string $adoption_date = '';
    public ?int $responsible_person_id = null;
    public string $responsiblePersonSearch = '';
    public string $responsiblePersonSelectedName = '';
    public array $personResults = [];
    public string $implementation_notes = '';
    public string $adoptionStatus = 'adopted';
    public $evidence;

    public bool $showExceptionModal = false;
    public string $exception_reason = '';

    protected $queryString = [
        'statusFilter' => ['except' => ''],
    ];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    private function institutionIds()
    {
        $user = Auth::user();

        return $user->activeAffiliations()->pluck('person_affiliations.organization_id')->unique();
    }

    public function acknowledge(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('acknowledge', $publication);

        $publication->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::record($publication, 'adoption.acknowledged', [], $publication->policy->organization_id, $publication->organization_id);
        session()->flash('success', 'Policy acknowledged.');
    }

    public function openAdoptionForm(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('updateAdoption', $publication);

        $this->activePublicationId = $publicationId;
        $this->adoption_date = $publication->adoption_date?->format('Y-m-d') ?? now()->toDateString();
        $this->responsible_person_id = $publication->responsible_person_id;
        $this->responsiblePersonSelectedName = $publication->responsiblePerson?->full_name ?? '';
        $this->responsiblePersonSearch = $this->responsiblePersonSelectedName;
        $this->implementation_notes = $publication->implementation_notes ?? '';
        $this->adoptionStatus = 'adopted';
    }

    public function closeAdoptionForm(): void
    {
        $this->activePublicationId = null;
        $this->responsiblePersonSearch = '';
        $this->responsiblePersonSelectedName = '';
        $this->personResults = [];
        $this->responsible_person_id = null;
        $this->resetValidation();
    }

    public function updatedResponsiblePersonSearch(): void
    {
        if (strlen($this->responsiblePersonSearch) < 2) {
            $this->personResults = [];
            return;
        }

        $this->personResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->responsiblePersonSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->responsiblePersonSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->toArray();
    }

    public function selectResponsiblePerson(int $personId, string $name): void
    {
        $this->responsible_person_id = $personId;
        $this->responsiblePersonSelectedName = $name;
        $this->responsiblePersonSearch = $name;
        $this->personResults = [];
    }

    public function recordAdoption(): void
    {
        $publication = PolicyPublication::findOrFail($this->activePublicationId);
        $this->authorize('updateAdoption', $publication);

        $this->validate([
            'adoption_date' => 'required|date',
            'responsible_person_id' => 'nullable|exists:persons,id',
            'implementation_notes' => 'nullable|string',
            'adoptionStatus' => 'required|in:adopted,partially_adopted',
        ]);

        $publication->update([
            'status' => $this->adoptionStatus,
            'adoption_date' => $this->adoption_date,
            'responsible_person_id' => $this->responsible_person_id,
            'implementation_notes' => $this->implementation_notes ?: null,
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::record($publication, 'adoption.status_changed', [
            'new' => $this->adoptionStatus,
        ], $publication->policy->organization_id, $publication->organization_id);

        session()->flash('success', 'Adoption recorded.');
        $this->closeAdoptionForm();
    }

    public function uploadEvidence(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('updateAdoption', $publication);

        $this->validate([
            'evidence' => 'required|file|max:20480',
        ]);

        $path = $this->evidence->store('policies/adoption-evidence/' . $publication->id, 'local');

        PolicyDocument::create([
            'documentable_type' => PolicyPublication::class,
            'documentable_id' => $publication->id,
            'kind' => 'evidence',
            'title' => $this->evidence->getClientOriginalName(),
            'path' => $path,
            'original_name' => $this->evidence->getClientOriginalName(),
            'mime' => $this->evidence->getMimeType(),
            'size' => $this->evidence->getSize(),
            'hash' => hash_file('sha256', $this->evidence->getRealPath()),
            'uploaded_by' => Auth::id(),
        ]);

        AuditLogger::record($publication, 'adoption.evidence_uploaded', [], $publication->policy->organization_id, $publication->organization_id);

        $this->evidence = null;
        session()->flash('success', 'Evidence uploaded.');
    }

    public function openExceptionModal(int $publicationId): void
    {
        $publication = PolicyPublication::findOrFail($publicationId);
        $this->authorize('requestException', $publication);

        $this->activePublicationId = $publicationId;
        $this->exception_reason = '';
        $this->showExceptionModal = true;
    }

    public function closeExceptionModal(): void
    {
        $this->showExceptionModal = false;
        $this->activePublicationId = null;
    }

    public function requestException(): void
    {
        $publication = PolicyPublication::findOrFail($this->activePublicationId);
        $this->authorize('requestException', $publication);

        $this->validate(['exception_reason' => 'required|string']);

        $publication->update([
            'exception_status' => 'requested',
            'exception_reason' => $this->exception_reason,
            'status' => 'exception_requested',
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::record($publication, 'adoption.exception_requested', [
            'reason' => $this->exception_reason,
        ], $publication->policy->organization_id, $publication->organization_id);

        session()->flash('success', 'Exception requested.');
        $this->closeExceptionModal();
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-policy-adoption'), 403);

        $institutionIds = $this->institutionIds();

        $query = PolicyPublication::with(['policy', 'policyVersion', 'organization'])
            ->forInstitutions($institutionIds)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('sent_at');

        return view('livewire.policies.policy-adoption-tracker', [
            'publications' => $query->paginate(15),
        ]);
    }
}
