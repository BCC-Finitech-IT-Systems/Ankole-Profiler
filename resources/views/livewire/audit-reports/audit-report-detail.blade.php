<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Audit Report Register</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">{{ $report->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @if($report->restricted)
                <span class="badge badge-warning">restricted</span>
            @endif
            <span class="badge">{{ str_replace('_', ' ', $report->status) }}</span>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">

            {{-- Overview + status --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Overview</h3>
                <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                    <div><span class="text-gray-400">Audit Type:</span> {{ ucfirst($report->audit_type) }}</div>
                    <div><span class="text-gray-400">Issuing Body:</span> {{ $report->issuing_body }}</div>
                    <div><span class="text-gray-400">Issue Date:</span> {{ $report->issue_date?->format('d M Y') }}</div>
                    <div><span class="text-gray-400">Period:</span> {{ $report->period_start?->format('d M Y') ?? '—' }} – {{ $report->period_end?->format('d M Y') ?? '—' }}</div>
                    <div><span class="text-gray-400">Audited:</span> {{ $report->department?->name ?? $report->audited_institution_name ?? '—' }}</div>
                    <div><span class="text-gray-400">Overall Rating:</span> {{ $report->overall_rating ?? '—' }}</div>
                </div>
                @if($report->summary)
                    <p class="text-sm text-gray-600 border-t border-gray-100 pt-3">{{ $report->summary }}</p>
                @endif

                @can('edit-audit-reports', $report)
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                        <select wire:model="status" class="select select-bordered select-sm">
                            <option value="draft">Draft</option>
                            <option value="issued">Issued</option>
                            <option value="under_review">Under Review</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button wire:click="updateStatus" type="button" class="btn btn-sm btn-outline">Update Status</button>
                    </div>
                @endcan
            </div>

            {{-- Documents --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Documents</h3>
                @forelse($documentsByType as $type => $docs)
                    <div class="mb-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ str_replace('_', ' ', $type) }}</p>
                        @foreach($docs as $doc)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span>
                                    v{{ $doc->version_number }} — {{ $doc->original_name }}
                                    @if($doc->is_current)<span class="badge badge-sm">current</span>@endif
                                </span>
                                <a href="{{ route('audit-reports.documents.download', $doc) }}" class="text-xs text-rose-600">Download</a>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No documents uploaded.</p>
                @endforelse

                @can('edit-audit-reports', $report)
                    <form wire:submit="uploadDocument" class="space-y-2 pt-2 border-t border-gray-100">
                        <select wire:model="document_type" class="select select-bordered select-sm">
                            <option value="report">Report</option>
                            <option value="management_letter">Management Letter</option>
                            <option value="management_response">Management Response</option>
                            <option value="evidence">Evidence</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="file" wire:model="document" class="file-input file-input-bordered file-input-sm w-full" />
                        @error('document') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-sm btn-outline">Upload</button>
                    </form>
                @endcan
            </div>

            {{-- Restricted audience --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Restricted Access</h3>
                @forelse($audiences as $audience)
                    <div class="flex items-center justify-between text-sm py-1 border-b border-gray-100">
                        <span>
                            @if($audience->organization_id)
                                Institution: {{ $audience->organization?->display_name ?? $audience->organization?->legal_name }}
                            @elseif($audience->department_id)
                                Department: {{ $audience->department?->name }}
                            @elseif($audience->role_name)
                                Role: {{ $audience->role_name }}
                            @elseif($audience->person_id)
                                Person: {{ trim(($audience->person?->given_name ?? '') . ' ' . ($audience->person?->family_name ?? '')) }}
                            @endif
                        </span>
                        @can('edit-audit-reports', $report)
                            <button wire:click="removeAudience({{ $audience->id }})" type="button" class="text-xs text-gray-500">Remove</button>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No audience rules — restricted reports with no rules are visible only to org/department managers.</p>
                @endforelse

                @can('edit-audit-reports', $report)
                    <form wire:submit="addAudience" class="space-y-2 mt-3 pt-2 border-t border-gray-100">
                        <select wire:model.live="audienceType" class="select select-bordered select-sm">
                            <option value="organization">Institution</option>
                            <option value="department">Department</option>
                            <option value="role">Role</option>
                            <option value="person">Person</option>
                        </select>
                        @if($audienceType === 'organization')
                            <select wire:model="audienceOrganizationId" class="select select-bordered select-sm w-full">
                                <option value="">— Select institution —</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->display_name ?? $org->legal_name }}</option>
                                @endforeach
                            </select>
                        @elseif($audienceType === 'department')
                            <select wire:model="audienceDepartmentId" class="select select-bordered select-sm w-full">
                                <option value="">— Select department —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        @elseif($audienceType === 'role')
                            <input type="text" wire:model="audienceRoleName" class="input input-bordered input-sm w-full" placeholder="Role name" />
                        @elseif($audienceType === 'person')
                            <div class="relative">
                                <input type="text" wire:model.live="audiencePersonSearch" class="input input-bordered input-sm w-full" placeholder="Search person…" />
                                @if(count($audiencePersonResults) > 0)
                                    <div class="absolute z-10 bg-white border border-gray-200 rounded-lg shadow-lg w-full mt-1 max-h-48 overflow-y-auto">
                                        @foreach($audiencePersonResults as $person)
                                            <button type="button" wire:click="selectAudiencePerson({{ $person['id'] }}, '{{ $person['given_name'] }} {{ $person['family_name'] }}')" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">
                                                {{ $person['given_name'] }} {{ $person['family_name'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                        @error('audienceOrganizationId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('audienceDepartmentId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('audienceRoleName') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('audiencePersonId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-sm btn-outline">Add Audience Rule</button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="space-y-4">
            {{-- Follow-up owner --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Responsible Follow-up Owner</h3>
                @can('edit-audit-reports', $report)
                    <form wire:submit="saveFollowUpOwner" class="space-y-2">
                        <div class="relative">
                            <input type="text" wire:model.live="followUpOwnerSearch" class="input input-bordered input-sm w-full" placeholder="Search person…" />
                            @if(count($followUpOwnerResults) > 0)
                                <div class="absolute z-10 bg-white border border-gray-200 rounded-lg shadow-lg w-full mt-1 max-h-48 overflow-y-auto">
                                    @foreach($followUpOwnerResults as $person)
                                        <button type="button" wire:click="selectFollowUpOwner({{ $person['id'] }}, '{{ $person['given_name'] }} {{ $person['family_name'] }}')" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">
                                            {{ $person['given_name'] }} {{ $person['family_name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline w-full">Save</button>
                    </form>
                @else
                    <p class="text-sm text-gray-600">{{ $report->followUpOwner ? trim($report->followUpOwner->given_name . ' ' . $report->followUpOwner->family_name) : '—' }}</p>
                @endcan
            </div>
        </div>
    </div>
</div>
</div>
