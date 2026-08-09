<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Policies</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Our Policy Adoption</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <select wire:model.live="statusFilter" class="select select-bordered select-sm">
            <option value="">All Statuses</option>
            <option value="sent">Sent</option>
            <option value="acknowledged">Acknowledged</option>
            <option value="adopted">Adopted</option>
            <option value="partially_adopted">Partially Adopted</option>
            <option value="exception_requested">Exception Requested</option>
            <option value="overdue">Overdue</option>
        </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="table table-sm w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Policy</th>
                    <th class="px-4 py-3 text-left">Version</th>
                    <th class="px-4 py-3 text-left">Due Date</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($publications as $publication)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $publication->policy->title }}</td>
                        <td class="px-4 py-3 text-xs">{{ $publication->policyVersion->version_label }}</td>
                        <td class="px-4 py-3 text-xs">{{ $publication->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="badge badge-sm">{{ str_replace('_', ' ', $publication->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                @if($publication->status === 'sent')
                                    <button wire:click="acknowledge({{ $publication->id }})" type="button" class="btn btn-ghost btn-xs">Acknowledge</button>
                                @endif
                                <button wire:click="openAdoptionForm({{ $publication->id }})" type="button" class="btn btn-ghost btn-xs">Record Adoption</button>
                                <button wire:click="openExceptionModal({{ $publication->id }})" type="button" class="btn btn-ghost btn-xs">Request Exception</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No policies published to your institution yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($publications->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $publications->links() }}</div>
        @endif
    </div>
</div>

    {{-- Adoption form modal --}}
    @if($activePublicationId && !$showExceptionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeAdoptionForm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Record Adoption</h2>
                <form wire:submit="recordAdoption" class="space-y-3">
                    <div>
                        <label class="label label-text text-xs font-medium">Status</label>
                        <select wire:model="adoptionStatus" class="select select-bordered w-full">
                            <option value="adopted">Adopted</option>
                            <option value="partially_adopted">Partially Adopted</option>
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Adoption Date</label>
                        <input type="date" wire:model="adoption_date" class="input input-bordered w-full" />
                    </div>
                    <div class="relative">
                        <label class="label label-text text-xs font-medium">Responsible Person</label>
                        <input type="text" wire:model.live.debounce.300ms="responsiblePersonSearch" class="input input-bordered w-full" placeholder="Search by name…" />
                        @if(count($personResults) > 0)
                            <ul class="mt-1 border border-gray-200 rounded-lg shadow bg-white text-sm max-h-36 overflow-y-auto">
                                @foreach($personResults as $person)
                                    <li>
                                        <button type="button" wire:click="selectResponsiblePerson({{ $person['id'] }}, '{{ addslashes(($person['given_name'] ?? '').' '.($person['family_name'] ?? '')) }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50">
                                            {{ $person['given_name'] }} {{ $person['family_name'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Implementation Notes</label>
                        <textarea wire:model="implementation_notes" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeAdoptionForm" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>

                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500 mb-2">Evidence</p>
                    <form wire:submit="uploadEvidence({{ $activePublicationId }})" class="flex items-center gap-2">
                        <input type="file" wire:model="evidence" class="file-input file-input-bordered file-input-sm w-full" />
                        <button type="submit" class="btn btn-sm btn-outline">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Exception request modal --}}
    @if($showExceptionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeExceptionModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Request Exception</h2>
                <form wire:submit="requestException" class="space-y-3">
                    <textarea wire:model="exception_reason" class="textarea textarea-bordered w-full" rows="3" placeholder="Reason"></textarea>
                    @error('exception_reason') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeExceptionModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
