<?php

namespace App\Livewire\LandParcels;

use App\Models\LandDocument;
use App\Models\LandDocumentAudience;
use App\Models\LandParcel;
use App\Models\LandParcelPayment;
use App\Models\LandParcelUpdate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class LandParcelDetail extends Component
{
    use WithFileUploads;

    public LandParcel $parcel;

    public string $stage = '';

    // Update form
    public bool $showUpdateForm = false;
    public string $update_reported_on = '';
    public string $update_notes = '';
    public string $update_blockers = '';
    public string $update_next_action = '';
    public string $update_revised_due_date = '';

    // Payment form
    public bool $showPaymentForm = false;
    public string $payment_amount = '';
    public string $payment_paid_on = '';
    public string $payee = '';
    public string $purpose = '';
    public string $receipt_reference = '';

    // Title particulars
    public string $title_number = '';
    public string $title_volume_folio = '';
    public string $title_issue_date = '';
    public string $registered_proprietor = '';
    public string $encumbrances = '';
    public string $lease_expiry_date = '';
    public string $title_verification_status = 'unverified';

    // Document upload
    public string $document_type = 'survey_report';
    public bool $document_restricted = false;
    public string $custody_location = '';
    public $document;

    // Audience management
    public ?int $activeAudienceDocumentId = null;
    public string $audienceType = 'organization';
    public ?int $audienceOrganizationId = null;
    public ?int $audienceDepartmentId = null;
    public string $audienceRoleName = '';

    public function mount(LandParcel $parcel): void
    {
        $this->authorize('view', $parcel);
        $this->parcel = $parcel;
        $this->stage = $parcel->stage;
        $this->title_number = $parcel->title_number ?? '';
        $this->title_volume_folio = $parcel->title_volume_folio ?? '';
        $this->title_issue_date = $parcel->title_issue_date?->format('Y-m-d') ?? '';
        $this->registered_proprietor = $parcel->registered_proprietor ?? '';
        $this->encumbrances = $parcel->encumbrances ?? '';
        $this->lease_expiry_date = $parcel->lease_expiry_date?->format('Y-m-d') ?? '';
        $this->title_verification_status = $parcel->title_verification_status;
    }

    // ─── Stage transition ────────────────────────────────────────────────────

    public function updateStage(): void
    {
        $this->authorize('update', $this->parcel);

        $this->validate([
            'stage' => 'required|in:unregistered,documents_gathering,survey_requested,surveyed,application_prepared,submitted,under_review,queries_raised,approved,title_issued,closed',
        ]);

        $before = LandParcel::trackedFieldSnapshot($this->parcel);
        $this->parcel->update(['stage' => $this->stage, 'updated_by' => Auth::id()]);
        $this->parcel->logFieldChanges($before);

        AuditLogger::record($this->parcel, 'land_parcel.stage_changed', ['stage' => $this->stage], $this->parcel->organization_id);
        session()->flash('success', 'Stage updated.');
    }

    // ─── Dispute ──────────────────────────────────────────────────────────────

    public function markDisputed(): void
    {
        $this->authorize('markDisputed', $this->parcel);

        $before = LandParcel::trackedFieldSnapshot($this->parcel);
        $this->parcel->update(['stage' => 'disputed', 'updated_by' => Auth::id()]);
        $this->parcel->logFieldChanges($before);
        $this->stage = 'disputed';

        AuditLogger::record($this->parcel, 'land_parcel.disputed', [], $this->parcel->organization_id);
        session()->flash('success', 'Parcel marked as disputed.');
    }

    public function resolveDispute(string $resumeStage = 'under_review'): void
    {
        $this->authorize('resolveDispute', $this->parcel);

        $before = LandParcel::trackedFieldSnapshot($this->parcel);
        $this->parcel->update(['stage' => $resumeStage, 'updated_by' => Auth::id()]);
        $this->parcel->logFieldChanges($before);
        $this->stage = $resumeStage;

        AuditLogger::record($this->parcel, 'land_parcel.dispute_resolved', ['resumed_stage' => $resumeStage], $this->parcel->organization_id);
        session()->flash('success', 'Dispute resolved.');
    }

    // ─── Progress updates ─────────────────────────────────────────────────────

    public function openUpdateForm(): void
    {
        $this->authorize('recordUpdate', $this->parcel);
        $this->update_reported_on = now()->toDateString();
        $this->update_notes = '';
        $this->update_blockers = '';
        $this->update_next_action = '';
        $this->update_revised_due_date = '';
        $this->showUpdateForm = true;
    }

    public function closeUpdateForm(): void
    {
        $this->showUpdateForm = false;
        $this->resetValidation();
    }

    public function recordUpdate(): void
    {
        $this->authorize('recordUpdate', $this->parcel);

        $this->validate([
            'update_reported_on' => 'required|date',
            'update_notes' => 'nullable|string',
            'update_blockers' => 'nullable|string',
            'update_next_action' => 'nullable|string',
            'update_revised_due_date' => 'nullable|date',
        ]);

        DB::transaction(function () {
            LandParcelUpdate::create([
                'land_parcel_id' => $this->parcel->id,
                'reported_on' => $this->update_reported_on,
                'notes' => $this->update_notes ?: null,
                'blockers' => $this->update_blockers ?: null,
                'next_action' => $this->update_next_action ?: null,
                'revised_expected_completion_date' => $this->update_revised_due_date ?: null,
                'reported_by' => Auth::id(),
            ]);

            $this->parcel->update([
                'blockers' => $this->update_blockers ?: $this->parcel->blockers,
                'next_action' => $this->update_next_action ?: $this->parcel->next_action,
                'expected_completion_date' => $this->update_revised_due_date ?: $this->parcel->expected_completion_date,
                'updated_by' => Auth::id(),
            ]);
        });

        AuditLogger::record($this->parcel, 'land_parcel.update_recorded', [], $this->parcel->organization_id);
        session()->flash('success', 'Update recorded.');
        $this->closeUpdateForm();
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function openPaymentForm(): void
    {
        $this->authorize('recordPayment', $this->parcel);
        $this->payment_amount = '';
        $this->payment_paid_on = now()->toDateString();
        $this->payee = '';
        $this->purpose = '';
        $this->receipt_reference = '';
        $this->showPaymentForm = true;
    }

    public function closePaymentForm(): void
    {
        $this->showPaymentForm = false;
        $this->resetValidation();
    }

    public function recordPayment(): void
    {
        $this->authorize('recordPayment', $this->parcel);

        $this->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_paid_on' => 'required|date',
            'payee' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'receipt_reference' => 'nullable|string|max:255',
        ]);

        $payment = LandParcelPayment::create([
            'land_parcel_id' => $this->parcel->id,
            'amount' => $this->payment_amount,
            'paid_on' => $this->payment_paid_on,
            'payee' => $this->payee ?: null,
            'purpose' => $this->purpose ?: null,
            'receipt_reference' => $this->receipt_reference ?: null,
            'recorded_by' => Auth::id(),
        ]);

        AuditLogger::record($this->parcel, 'land_parcel.payment_recorded', [
            'amount' => $this->payment_amount, 'payment_id' => $payment->id,
        ], $this->parcel->organization_id);

        session()->flash('success', 'Payment recorded.');
        $this->closePaymentForm();
    }

    // ─── Title particulars ────────────────────────────────────────────────────

    public function saveTitleParticulars(): void
    {
        $this->authorize('update', $this->parcel);

        $this->validate([
            'title_number' => 'nullable|string|max:255',
            'title_volume_folio' => 'nullable|string|max:255',
            'title_issue_date' => 'nullable|date',
            'registered_proprietor' => 'nullable|string|max:255',
            'encumbrances' => 'nullable|string',
            'lease_expiry_date' => 'nullable|date',
            'title_verification_status' => 'required|in:unverified,verified,disputed',
        ]);

        $before = LandParcel::trackedFieldSnapshot($this->parcel);

        $this->parcel->update([
            'title_number' => $this->title_number ?: null,
            'title_volume_folio' => $this->title_volume_folio ?: null,
            'title_issue_date' => $this->title_issue_date ?: null,
            'registered_proprietor' => $this->registered_proprietor ?: null,
            'encumbrances' => $this->encumbrances ?: null,
            'lease_expiry_date' => $this->lease_expiry_date ?: null,
            'title_verification_status' => $this->title_verification_status,
            'updated_by' => Auth::id(),
        ]);

        $this->parcel->logFieldChanges($before);
        AuditLogger::record($this->parcel, 'land_parcel.title_particulars_updated', [], $this->parcel->organization_id);
        session()->flash('success', 'Title particulars saved.');
    }

    // ─── Documents ────────────────────────────────────────────────────────────

    public function uploadDocument(): void
    {
        $this->authorize('update', $this->parcel);

        $this->validate([
            'document_type' => 'required|in:purchase_agreement,survey_report,deed_plan,application,receipt,correspondence,court_document,title_copy,other',
            'document' => 'required|file|max:20480',
            'custody_location' => 'nullable|string|max:255',
        ]);

        $nextVersion = LandDocument::where('land_parcel_id', $this->parcel->id)
            ->where('document_type', $this->document_type)
            ->max('version_number') + 1;

        $path = $this->document->store('land-parcels/' . $this->parcel->id . '/' . $this->document_type, 'local');

        DB::transaction(function () use ($nextVersion, $path) {
            LandDocument::where('land_parcel_id', $this->parcel->id)
                ->where('document_type', $this->document_type)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            LandDocument::create([
                'land_parcel_id' => $this->parcel->id,
                'document_type' => $this->document_type,
                'version_number' => $nextVersion,
                'is_current' => true,
                'restricted' => $this->document_restricted,
                'custody_location' => $this->custody_location ?: null,
                'path' => $path,
                'original_name' => $this->document->getClientOriginalName(),
                'mime' => $this->document->getMimeType(),
                'size' => $this->document->getSize(),
                'hash' => hash_file('sha256', $this->document->getRealPath()),
                'uploaded_by' => Auth::id(),
            ]);
        });

        AuditLogger::record($this->parcel, 'land_document.uploaded', [
            'document_type' => $this->document_type, 'version_number' => $nextVersion,
        ], $this->parcel->organization_id);

        if ($this->custody_location) {
            AuditLogger::record($this->parcel, 'document.custody_moved', [
                'document_type' => $this->document_type, 'custody_location' => $this->custody_location,
            ], $this->parcel->organization_id);
        }

        $this->document = null;
        $this->custody_location = '';
        session()->flash('success', 'Document uploaded.');
    }

    // ─── Restricted audience ────────────────────────────────────────────────

    public function openAudienceEditor(int $documentId): void
    {
        $this->authorize('update', $this->parcel);
        $this->activeAudienceDocumentId = $documentId;
    }

    public function closeAudienceEditor(): void
    {
        $this->activeAudienceDocumentId = null;
    }

    public function addAudience(): void
    {
        $this->authorize('update', $this->parcel);

        $this->validate([
            'audienceType' => 'required|in:organization,department,role',
            'audienceOrganizationId' => 'required_if:audienceType,organization|nullable|exists:organizations,id',
            'audienceDepartmentId' => 'required_if:audienceType,department|nullable|exists:departments,id',
            'audienceRoleName' => 'required_if:audienceType,role|nullable|string|max:255',
        ]);

        LandDocumentAudience::create([
            'land_document_id' => $this->activeAudienceDocumentId,
            'organization_id' => $this->audienceType === 'organization' ? $this->audienceOrganizationId : null,
            'department_id' => $this->audienceType === 'department' ? $this->audienceDepartmentId : null,
            'role_name' => $this->audienceType === 'role' ? $this->audienceRoleName : null,
        ]);

        $this->audienceOrganizationId = null;
        $this->audienceDepartmentId = null;
        $this->audienceRoleName = '';
        session()->flash('success', 'Audience rule added.');
    }

    public function removeAudience(int $audienceId): void
    {
        $this->authorize('update', $this->parcel);
        LandDocumentAudience::findOrFail($audienceId)->delete();
        session()->flash('success', 'Audience rule removed.');
    }

    public function render()
    {
        $this->parcel->refresh();

        return view('livewire.land-parcels.land-parcel-detail', [
            'updates' => $this->parcel->updates()->with('reporter')->get(),
            'payments' => $this->parcel->payments,
            'documentsByType' => $this->parcel->documents()->with('audiences')->orderByDesc('version_number')->get()->groupBy('document_type'),
        ]);
    }
}
