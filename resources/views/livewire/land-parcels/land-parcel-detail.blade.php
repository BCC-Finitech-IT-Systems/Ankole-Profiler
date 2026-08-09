<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Land Registration</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">{{ $parcel->property_name }} ({{ $parcel->reference_number }})</h1>
        </div>
        <span class="badge {{ $parcel->stage === 'disputed' ? 'badge-error' : '' }}">{{ str_replace('_', ' ', $parcel->stage) }}</span>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">

            {{-- Stage + actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Registration & Titling Stage</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model="stage" class="select select-bordered select-sm">
                        <option value="unregistered">Unregistered</option>
                        <option value="documents_gathering">Documents Gathering</option>
                        <option value="survey_requested">Survey Requested</option>
                        <option value="surveyed">Surveyed</option>
                        <option value="application_prepared">Application Prepared</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="queries_raised">Queries Raised</option>
                        <option value="approved">Approved</option>
                        <option value="title_issued">Title Issued</option>
                        <option value="closed">Closed</option>
                    </select>
                    @can('edit-land-parcels')
                        <button wire:click="updateStage" type="button" class="btn btn-sm btn-outline">Update Stage</button>
                    @endcan
                    @can('manage-land-disputes')
                        @if($parcel->stage !== 'disputed')
                            <button wire:click="markDisputed" type="button" wire:confirm="Mark this parcel as disputed?" class="btn btn-sm btn-outline text-red-500">Mark Disputed</button>
                        @else
                            <button wire:click="resolveDispute" type="button" class="btn btn-sm btn-outline">Resolve Dispute</button>
                        @endif
                    @endcan
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                    <div><span class="text-gray-400">Responsible:</span> {{ $parcel->responsiblePerson?->full_name ?? '—' }}</div>
                    <div><span class="text-gray-400">Expected Completion:</span> {{ $parcel->expected_completion_date?->format('d M Y') ?? '—' }}
                        @if($parcel->isStalled())<span class="badge badge-sm badge-error ml-1">Delayed</span>@endif
                    </div>
                    <div><span class="text-gray-400">Next Action:</span> {{ $parcel->next_action ?? '—' }}</div>
                    <div><span class="text-gray-400">Blockers:</span> {{ $parcel->blockers ?? '—' }}</div>
                </div>
            </div>

            {{-- Updates --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Progress Updates</h3>
                    @can('edit-land-parcels')
                        <button wire:click="openUpdateForm" type="button" class="btn btn-sm btn-outline">Record Update</button>
                    @endcan
                </div>
                @forelse($updates as $update)
                    <div class="border-b border-gray-100 py-2 text-sm">
                        <span class="font-medium">{{ $update->reported_on->format('d M Y') }}</span>
                        @if($update->notes)<p class="text-gray-600">{{ $update->notes }}</p>@endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No updates yet.</p>
                @endforelse
            </div>

            {{-- Payments --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
                    @can('edit-land-parcels')
                        <button wire:click="openPaymentForm" type="button" class="btn btn-sm btn-outline">Record Payment</button>
                    @endcan
                </div>
                @forelse($payments as $payment)
                    <div class="border-b border-gray-100 py-2 text-sm flex justify-between">
                        <span>{{ $payment->purpose ?? 'Payment' }} — {{ $payment->payee ?? '—' }}</span>
                        <span class="font-medium">{{ number_format($payment->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payments recorded.</p>
                @endforelse
            </div>

            {{-- Documents --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Documents</h3>
                @foreach($documentsByType as $type => $docs)
                    <div class="mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ str_replace('_', ' ', $type) }}</p>
                        @foreach($docs as $doc)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span>
                                    v{{ $doc->version_number }} — {{ $doc->original_name }}
                                    @if($doc->is_current)<span class="badge badge-sm">current</span>@endif
                                    @if($doc->restricted)<span class="badge badge-sm badge-warning">restricted</span>@endif
                                </span>
                                <div class="flex gap-1">
                                    <a href="{{ route('land-parcels.documents.download', $doc) }}" class="text-xs text-rose-600">Download</a>
                                    @can('edit-land-parcels')
                                        <button wire:click="openAudienceEditor({{ $doc->id }})" type="button" class="text-xs text-gray-500">Audience</button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @can('upload-land-documents')
                    <form wire:submit="uploadDocument" class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="grid grid-cols-2 gap-2">
                            <select wire:model="document_type" class="select select-bordered select-sm">
                                <option value="purchase_agreement">Purchase Agreement</option>
                                <option value="survey_report">Survey Report</option>
                                <option value="deed_plan">Deed Plan</option>
                                <option value="application">Application</option>
                                <option value="receipt">Receipt</option>
                                <option value="correspondence">Correspondence</option>
                                <option value="court_document">Court Document</option>
                                <option value="title_copy">Title Copy</option>
                                <option value="other">Other</option>
                            </select>
                            <input type="text" wire:model="custody_location" class="input input-bordered input-sm" placeholder="Custody location" />
                        </div>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="checkbox" wire:model="document_restricted" class="checkbox checkbox-sm" /> Restricted document
                        </label>
                        <input type="file" wire:model="document" class="file-input file-input-bordered file-input-sm w-full" />
                        @error('document') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-sm btn-outline">Upload</button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="space-y-4">
            {{-- Title particulars --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Title Particulars</h3>
                <form wire:submit="saveTitleParticulars" class="space-y-2">
                    <input type="text" wire:model="title_number" class="input input-bordered input-sm w-full" placeholder="Title Number" />
                    <input type="text" wire:model="title_volume_folio" class="input input-bordered input-sm w-full" placeholder="Volume/Folio" />
                    <input type="date" wire:model="title_issue_date" class="input input-bordered input-sm w-full" />
                    <input type="text" wire:model="registered_proprietor" class="input input-bordered input-sm w-full" placeholder="Registered Proprietor" />
                    <textarea wire:model="encumbrances" class="textarea textarea-bordered textarea-sm w-full" placeholder="Encumbrances" rows="2"></textarea>
                    <input type="date" wire:model="lease_expiry_date" class="input input-bordered input-sm w-full" />
                    <select wire:model="title_verification_status" class="select select-bordered select-sm w-full">
                        <option value="unverified">Unverified</option>
                        <option value="verified">Verified</option>
                        <option value="disputed">Disputed</option>
                    </select>
                    @can('edit-land-parcels')
                        <button type="submit" class="btn btn-sm btn-outline w-full">Save</button>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>

    {{-- Update modal --}}
    @if($showUpdateForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeUpdateForm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Record Update</h2>
                <form wire:submit="recordUpdate" class="space-y-3">
                    <input type="date" wire:model="update_reported_on" class="input input-bordered w-full" />
                    <textarea wire:model="update_notes" class="textarea textarea-bordered w-full" placeholder="Notes" rows="2"></textarea>
                    <textarea wire:model="update_blockers" class="textarea textarea-bordered w-full" placeholder="Blockers" rows="2"></textarea>
                    <textarea wire:model="update_next_action" class="textarea textarea-bordered w-full" placeholder="Next action" rows="2"></textarea>
                    <input type="date" wire:model="update_revised_due_date" class="input input-bordered w-full" placeholder="Revised due date" />
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeUpdateForm" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Payment modal --}}
    @if($showPaymentForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closePaymentForm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Record Payment</h2>
                <form wire:submit="recordPayment" class="space-y-3">
                    <input type="number" step="0.01" wire:model="payment_amount" class="input input-bordered w-full" placeholder="Amount" />
                    <input type="date" wire:model="payment_paid_on" class="input input-bordered w-full" />
                    <input type="text" wire:model="payee" class="input input-bordered w-full" placeholder="Payee" />
                    <input type="text" wire:model="purpose" class="input input-bordered w-full" placeholder="Purpose" />
                    <input type="text" wire:model="receipt_reference" class="input input-bordered w-full" placeholder="Receipt Reference" />
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closePaymentForm" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Audience editor modal --}}
    @if($activeAudienceDocumentId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeAudienceEditor">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Audience Rules</h2>
                <form wire:submit="addAudience" class="flex items-center gap-2">
                    <select wire:model="audienceType" class="select select-bordered select-sm">
                        <option value="organization">Organization</option>
                        <option value="department">Department</option>
                        <option value="role">Role</option>
                    </select>
                    @if($audienceType === 'role')
                        <input type="text" wire:model="audienceRoleName" class="input input-bordered input-sm" placeholder="Role name" />
                    @endif
                    <button type="submit" class="btn btn-sm btn-outline">Add</button>
                </form>
                <div class="flex justify-end pt-2 border-t border-gray-100">
                    <button type="button" wire:click="closeAudienceEditor" class="btn btn-ghost btn-sm">Close</button>
                </div>
            </div>
        </div>
    @endif

</div>
