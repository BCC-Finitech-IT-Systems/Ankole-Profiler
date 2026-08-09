<?php

namespace App\Livewire\Policies;

use App\Models\Organization;
use App\Models\Person;
use App\Models\Policy;
use App\Models\PolicyDocument;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use App\Models\PolicyVersionAudience;
use App\Notifications\PolicyIssued;
use App\Notifications\PolicyRevised;
use App\Services\AuditLogger;
use App\Services\PolicyNotificationTargets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PolicyDetail extends Component
{
    use WithFileUploads;

    public Policy $policy;

    // Draft metadata form
    public string $summary = '';
    public string $effective_date = '';
    public string $review_date = '';
    public string $adoption_due_date = '';
    public string $issuing_authority = '';
    public string $visibility = 'diocese_wide';

    // Synod approval form
    public string $synod_approval_date = '';
    public string $synod_approval_reference = '';

    // Uploads
    public $document;
    public $attachment;

    // Audience form
    public string $audienceType = 'organization';
    public ?int $audienceOrganizationId = null;
    public ?int $audienceDepartmentId = null;
    public string $audienceRoleName = '';

    // Publish modal
    public bool $showPublishModal = false;
    public array $selectedInstitutionIds = [];
    public string $publishDueDate = '';

    public function mount(Policy $policy): void
    {
        $this->authorize('view', $policy);
        $this->policy = $policy;
        $this->syncFormFromDraft();
    }

    private function draftVersion(): ?PolicyVersion
    {
        // Policy::versions() bakes in ->orderBy('version_number') ascending;
        // chaining orderByDesc() on top stacks rather than overrides (the
        // first ORDER BY clause wins), so reorder() first to get DESC.
        return $this->policy->versions()->where('status', 'draft')->reorder('version_number', 'desc')->first();
    }

    private function latestVersion(): ?PolicyVersion
    {
        return $this->policy->versions()->reorder('version_number', 'desc')->first();
    }

    private function syncFormFromDraft(): void
    {
        $draft = $this->draftVersion();

        if (!$draft) {
            return;
        }

        $this->summary = $draft->summary ?? '';
        $this->effective_date = $draft->effective_date?->format('Y-m-d') ?? '';
        $this->review_date = $draft->review_date?->format('Y-m-d') ?? '';
        $this->adoption_due_date = $draft->adoption_due_date?->format('Y-m-d') ?? '';
        $this->issuing_authority = $draft->issuing_authority ?? '';
        $this->visibility = $draft->visibility;
        $this->synod_approval_date = $draft->synod_approval_date?->format('Y-m-d') ?? '';
        $this->synod_approval_reference = $draft->synod_approval_reference ?? '';
    }

    // ─── Draft metadata ───────────────────────────────────────────────────────

    public function saveMetadata(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('update', $draft);

        $this->validate([
            'summary' => 'nullable|string',
            'effective_date' => 'nullable|date',
            'review_date' => 'nullable|date',
            'adoption_due_date' => 'nullable|date',
            'issuing_authority' => 'nullable|string|max:255',
            'visibility' => 'required|in:diocese_wide,restricted',
        ]);

        $draft->update([
            'summary' => $this->summary ?: null,
            'effective_date' => $this->effective_date ?: null,
            'review_date' => $this->review_date ?: null,
            'adoption_due_date' => $this->adoption_due_date ?: null,
            'issuing_authority' => $this->issuing_authority ?: null,
            'visibility' => $this->visibility,
        ]);

        AuditLogger::record($draft, 'policy_version.updated', [], $this->policy->organization_id);
        session()->flash('success', 'Draft updated.');
    }

    public function recordSynodApproval(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('recordApproval', $draft);

        $this->validate([
            'synod_approval_date' => 'required|date',
            'synod_approval_reference' => 'required|string|max:255',
        ]);

        $draft->update([
            'synod_approval_date' => $this->synod_approval_date,
            'synod_approval_reference' => $this->synod_approval_reference,
        ]);

        AuditLogger::record($draft, 'policy_version.approval_recorded', [
            'synod_approval_date' => $this->synod_approval_date,
            'synod_approval_reference' => $this->synod_approval_reference,
        ], $this->policy->organization_id);

        session()->flash('success', 'Synod approval recorded.');
    }

    public function approve(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('approve', $draft);

        $draft->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLogger::record($draft, 'policy_version.approved', [], $this->policy->organization_id);
        session()->flash('success', 'Version approved.');
    }

    public function createRevision(): void
    {
        $latest = $this->latestVersion();
        abort_unless($latest, 404);
        $this->authorize('createRevision', $latest);

        abort_if(
            $this->policy->versions()->whereIn('status', ['draft', 'pending_approval'])->exists(),
            422,
            'A revision is already in progress for this policy.'
        );

        $new = DB::transaction(function () use ($latest) {
            return PolicyVersion::create([
                'policy_id' => $this->policy->id,
                'version_number' => $latest->version_number + 1,
                'version_label' => ($latest->version_number + 1) . '.0',
                'summary' => $latest->summary,
                'effective_date' => $latest->effective_date,
                'review_date' => $latest->review_date,
                'adoption_due_date' => $latest->adoption_due_date,
                'issuing_authority' => $latest->issuing_authority,
                'visibility' => $latest->visibility,
                'status' => 'draft',
                'supersedes_version_id' => $latest->id,
                'created_by' => Auth::id(),
            ]);
        });

        AuditLogger::record($new, 'policy_version.created', ['supersedes_version_id' => $latest->id], $this->policy->organization_id);
        session()->flash('success', 'New draft revision created.');
        $this->syncFormFromDraft();
    }

    public function archiveVersion(int $versionId): void
    {
        $version = PolicyVersion::findOrFail($versionId);
        $this->authorize('archive', $version);

        $version->update(['status' => 'archived', 'archived_at' => now()]);
        AuditLogger::record($version, 'policy_version.archived', [], $this->policy->organization_id);
        session()->flash('success', 'Version archived.');
    }

    // ─── Documents ────────────────────────────────────────────────────────────

    public function uploadDocument(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('update', $draft);

        $this->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $path = $this->document->store('policies/' . $this->policy->id . '/versions/' . $draft->id, 'local');

        $draft->update([
            'document_path' => $path,
            'document_original_name' => $this->document->getClientOriginalName(),
            'document_mime' => $this->document->getMimeType(),
            'document_size' => $this->document->getSize(),
            'document_hash' => hash_file('sha256', $this->document->getRealPath()),
        ]);

        AuditLogger::record($draft, 'policy_version.document_replaced', [], $this->policy->organization_id);

        $this->document = null;
        session()->flash('success', 'Approved document uploaded.');
    }

    public function uploadAttachment(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('update', $draft);

        $this->validate([
            'attachment' => 'required|file|max:20480',
        ]);

        $path = $this->attachment->store('policies/' . $this->policy->id . '/versions/' . $draft->id . '/attachments', 'local');

        PolicyDocument::create([
            'documentable_type' => PolicyVersion::class,
            'documentable_id' => $draft->id,
            'kind' => 'supporting',
            'title' => $this->attachment->getClientOriginalName(),
            'path' => $path,
            'original_name' => $this->attachment->getClientOriginalName(),
            'mime' => $this->attachment->getMimeType(),
            'size' => $this->attachment->getSize(),
            'hash' => hash_file('sha256', $this->attachment->getRealPath()),
            'uploaded_by' => Auth::id(),
        ]);

        AuditLogger::record($draft, 'policy_version.attachment_added', [], $this->policy->organization_id);

        $this->attachment = null;
        session()->flash('success', 'Supporting attachment uploaded.');
    }

    // ─── Restricted audience ────────────────────────────────────────────────

    public function addAudience(): void
    {
        $draft = $this->draftVersion();
        abort_unless($draft, 404);
        $this->authorize('update', $draft);

        $this->validate([
            'audienceType' => 'required|in:organization,department,role',
            'audienceOrganizationId' => 'required_if:audienceType,organization|nullable|exists:organizations,id',
            'audienceDepartmentId' => 'required_if:audienceType,department|nullable|exists:departments,id',
            'audienceRoleName' => 'required_if:audienceType,role|nullable|string|max:255',
        ]);

        PolicyVersionAudience::create([
            'policy_version_id' => $draft->id,
            'organization_id' => $this->audienceType === 'organization' ? $this->audienceOrganizationId : null,
            'department_id' => $this->audienceType === 'department' ? $this->audienceDepartmentId : null,
            'role_name' => $this->audienceType === 'role' ? $this->audienceRoleName : null,
        ]);

        AuditLogger::record($draft, 'policy_version.visibility_changed', ['audience_added' => true], $this->policy->organization_id);

        $this->audienceOrganizationId = null;
        $this->audienceDepartmentId = null;
        $this->audienceRoleName = '';
        session()->flash('success', 'Audience rule added.');
    }

    public function removeAudience(int $audienceId): void
    {
        $audience = PolicyVersionAudience::findOrFail($audienceId);
        $this->authorize('update', $audience->policyVersion);

        $audience->delete();
        AuditLogger::record($audience->policyVersion, 'policy_version.visibility_changed', ['audience_removed' => true], $this->policy->organization_id);
        session()->flash('success', 'Audience rule removed.');
    }

    // ─── Publish ──────────────────────────────────────────────────────────────

    public function openPublishModal(): void
    {
        $draft = $this->draftVersion() ?? $this->latestVersion();
        abort_unless($draft, 404);
        $this->authorize('publish', $draft);

        $this->selectedInstitutionIds = [];
        $this->publishDueDate = $draft->adoption_due_date?->format('Y-m-d') ?? now()->addDays(30)->toDateString();
        $this->showPublishModal = true;
    }

    public function closePublishModal(): void
    {
        $this->showPublishModal = false;
    }

    public function selectAllInstitutions(): void
    {
        $this->selectedInstitutionIds = $this->availableInstitutions()->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function publish(): void
    {
        $version = $this->draftVersion() ?? $this->latestVersion();
        abort_unless($version, 404);
        $this->authorize('publish', $version);

        $this->validate([
            'selectedInstitutionIds' => 'required|array|min:1',
            'publishDueDate' => 'nullable|date',
        ]);

        $allowedInstitutionIds = $this->availableInstitutions()->pluck('id');
        $targetIds = collect($this->selectedInstitutionIds)->map(fn ($id) => (int) $id)
            ->intersect($allowedInstitutionIds)->values();

        abort_if($targetIds->isEmpty(), 422, 'No valid institutions selected.');

        $previousPublished = $this->policy->versions()->where('status', 'published')->first();

        DB::transaction(function () use ($version, $targetIds, $previousPublished) {
            $version->update([
                'status' => 'published',
                'published_by' => Auth::id(),
                'published_at' => now(),
            ]);

            if ($previousPublished) {
                $previousPublished->update(['status' => 'superseded']);
            }

            $this->policy->update(['current_version_id' => $version->id, 'status' => 'active']);

            foreach ($targetIds as $institutionId) {
                PolicyPublication::updateOrCreate(
                    ['policy_version_id' => $version->id, 'organization_id' => $institutionId],
                    [
                        'policy_id' => $this->policy->id,
                        'status' => 'sent',
                        'due_date' => $this->publishDueDate ?: null,
                        'sent_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );
            }
        });

        AuditLogger::record($version, 'policy_version.published', [
            'institution_ids' => $targetIds->all(),
            'due_date' => $this->publishDueDate,
        ], $this->policy->organization_id);

        // Fire after the transaction commits, matching the app's convention
        // of not notifying on a rollback (see ReviewMembershipApplications).
        foreach ($targetIds as $institutionId) {
            $organization = Organization::find($institutionId);
            $recipients = PolicyNotificationTargets::forInstitution($organization);
            $notification = $previousPublished ? new PolicyRevised($version) : new PolicyIssued($version);

            foreach ($recipients as $recipient) {
                $recipient->notify($notification);
            }
        }

        $this->showPublishModal = false;
        $this->policy->refresh();
        session()->flash('success', 'Policy published to ' . $targetIds->count() . ' institution(s).');
    }

    public function availableInstitutions()
    {
        return Organization::whereIn('id', $this->policy->organization->subtreeIds())
            ->where('id', '!=', $this->policy->organization_id)
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'legal_name', 'category']);
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $this->policy->refresh();

        return view('livewire.policies.policy-detail', [
            'versions' => $this->policy->versions()->with('audiences')->reorder('version_number', 'desc')->get(),
            'draft' => $this->draftVersion(),
            'attachments' => $this->draftVersion()?->attachments ?? collect(),
            'institutions' => $this->showPublishModal ? $this->availableInstitutions() : collect(),
        ]);
    }
}
