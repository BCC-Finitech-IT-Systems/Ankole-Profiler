<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Policies</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">{{ $policy->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @can('view-audit-logs')
                <a href="{{ route('policies.audit', $policy) }}" class="btn btn-ghost btn-sm">Audit Trail</a>
            @endcan
            @if($draft && $draft->isPublishable())
                @can('publish-policies')
                    <button wire:click="openPublishModal" type="button" class="btn btn-sm border-0 text-white" style="background:#982B55;">
                        Publish
                    </button>
                @endcan
            @endif
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @error('selectedInstitutionIds')
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Version timeline --}}
        <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Version History</h3>
            <ul class="space-y-2">
                @foreach($versions as $version)
                    <li class="p-2 rounded-lg border border-gray-100 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">v{{ $version->version_label ?? $version->version_number }}</span>
                            <span class="badge badge-sm">{{ $version->status }}</span>
                        </div>
                        @if($version->published_at)
                            <div class="text-xs text-gray-400 mt-1">Published {{ $version->published_at->format('d M Y') }}</div>
                        @endif
                        @can('archive-policies')
                            @if(!in_array($version->status, ['archived']))
                                <button wire:click="archiveVersion({{ $version->id }})" wire:confirm="Archive this version?"
                                        type="button" class="text-xs text-red-500 mt-1">Archive</button>
                            @endif
                        @endcan
                    </li>
                @endforeach
            </ul>

            @can('create-policies')
                <button wire:click="createRevision" type="button" class="btn btn-sm btn-outline w-full mt-3">
                    Create Revision
                </button>
            @endcan
        </div>

        {{-- Draft editor --}}
        <div class="lg:col-span-2 space-y-4">
            @if($draft)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Draft v{{ $draft->version_label }}
                        <span class="badge badge-sm ml-1">{{ $draft->status }}</span>
                    </h3>

                    <form wire:submit="saveMetadata" class="space-y-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Summary</label>
                            <textarea wire:model="summary" class="textarea textarea-bordered w-full" rows="2"></textarea>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="label label-text text-xs font-medium">Effective Date</label>
                                <input type="date" wire:model="effective_date" class="input input-bordered w-full" />
                            </div>
                            <div>
                                <label class="label label-text text-xs font-medium">Review Date</label>
                                <input type="date" wire:model="review_date" class="input input-bordered w-full" />
                            </div>
                            <div>
                                <label class="label label-text text-xs font-medium">Adoption Due Date</label>
                                <input type="date" wire:model="adoption_due_date" class="input input-bordered w-full" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label label-text text-xs font-medium">Issuing Authority</label>
                                <input type="text" wire:model="issuing_authority" class="input input-bordered w-full" />
                            </div>
                            <div>
                                <label class="label label-text text-xs font-medium">Visibility</label>
                                <select wire:model="visibility" class="select select-bordered w-full">
                                    <option value="diocese_wide">Diocese-wide</option>
                                    <option value="restricted">Restricted</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save Draft</button>
                        </div>
                    </form>
                </div>

                {{-- Synod approval --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Synod Approval</h3>
                    <form wire:submit="recordSynodApproval" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Approval Date</label>
                            <input type="date" wire:model="synod_approval_date" class="input input-bordered w-full" />
                            @error('synod_approval_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Reference</label>
                            <input type="text" wire:model="synod_approval_reference" class="input input-bordered w-full" />
                            @error('synod_approval_reference') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 flex justify-end">
                            <button type="submit" class="btn btn-sm btn-outline">Record Synod Approval</button>
                        </div>
                    </form>
                </div>

                {{-- Document --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Approved Document</h3>
                    @if($draft->document_path)
                        <p class="text-sm text-gray-600 mb-2">Current: {{ $draft->document_original_name }}</p>
                    @endif
                    <form wire:submit="uploadDocument" class="flex items-center gap-2">
                        <input type="file" wire:model="document" class="file-input file-input-bordered file-input-sm w-full" />
                        <button type="submit" class="btn btn-sm btn-outline">Upload</button>
                    </form>
                    @error('document') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                    <div class="mt-3 border-t border-gray-100 pt-3">
                        <p class="text-xs text-gray-500 mb-2">Supporting attachments</p>
                        @foreach($attachments as $file)
                            <div class="text-sm text-gray-600">{{ $file->original_name }}</div>
                        @endforeach
                        <form wire:submit="uploadAttachment" class="flex items-center gap-2 mt-2">
                            <input type="file" wire:model="attachment" class="file-input file-input-bordered file-input-sm w-full" />
                            <button type="submit" class="btn btn-sm btn-outline">Add</button>
                        </form>
                    </div>
                </div>

                {{-- Approve --}}
                @can('approve-policies')
                    @if($draft->status === 'draft')
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Publish requires: Synod date + reference{{ !$draft->hasSynodApproval() ? ' (missing)' : '' }},
                                document{{ !$draft->document_path ? ' (missing)' : '' }}.
                            </div>
                            <button wire:click="approve" type="button" class="btn btn-sm btn-outline"
                                    @disabled(!$draft->hasSynodApproval())>
                                Approve Version
                            </button>
                        </div>
                    @endif
                @endcan

                {{-- Restricted audience --}}
                @if($visibility === 'restricted')
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Audience Rules</h3>
                        @foreach($draft->audiences as $audience)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span>
                                    {{ $audience->organization?->display_name ?? $audience->department?->name ?? $audience->role_name }}
                                </span>
                                <button wire:click="removeAudience({{ $audience->id }})" type="button" class="text-xs text-red-500">Remove</button>
                            </div>
                        @endforeach
                        <form wire:submit="addAudience" class="flex items-center gap-2 mt-2">
                            <select wire:model="audienceType" class="select select-bordered select-sm">
                                <option value="organization">Institution</option>
                                <option value="department">Department</option>
                                <option value="role">Role</option>
                            </select>
                            @if($audienceType === 'role')
                                <input type="text" wire:model="audienceRoleName" class="input input-bordered input-sm" placeholder="Role name" />
                            @endif
                            <button type="submit" class="btn btn-sm btn-outline">Add</button>
                        </form>
                    </div>
                @endif
            @else
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-sm text-gray-500">
                    No draft version in progress. Create a revision to make changes.
                </div>
            @endif
        </div>
    </div>
</div>

    {{-- Publish modal --}}
    @if($showPublishModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closePublishModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Publish to Institutions</h2>
                    <button wire:click="closePublishModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <label class="label label-text text-xs font-medium">Adoption Due Date</label>
                        <input type="date" wire:model="publishDueDate" class="input input-bordered w-full" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Select institutions</span>
                        <button wire:click="selectAllInstitutions" type="button" class="text-xs text-rose-600">Select all</button>
                    </div>
                    <div class="max-h-64 overflow-y-auto border border-gray-100 rounded-lg divide-y">
                        @foreach($institutions as $institution)
                            <label class="flex items-center gap-2 px-3 py-2 text-sm">
                                <input type="checkbox" wire:model="selectedInstitutionIds" value="{{ $institution->id }}" class="checkbox checkbox-sm" />
                                {{ $institution->display_name ?? $institution->legal_name }}
                                <span class="text-xs text-gray-400">({{ $institution->category }})</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="closePublishModal" type="button" class="btn btn-ghost btn-sm">Cancel</button>
                        <button wire:click="publish" type="button" class="btn btn-sm border-0 text-white" style="background:#982B55;">
                            Confirm Publish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
