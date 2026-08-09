<div class="min-h-full">

    {{-- Page header rendered inline (not via x-slot="header") — see the
         regression documented in tests/Feature/ProjectsManagementTest.php:
         Livewire's page-layout mechanism renders x-slot="header" content
         outside this component's wire:id root, silently breaking wire:click. --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Policies</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Policy Register</h1>
        </div>
        @can('create-policies')
            <button wire:click="create" type="button"
                    class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Policy
            </button>
        @endcan
    </div>

<div class="py-6 px-4 md:px-8">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="w-full space-y-4">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search by title or reference…" class="input input-bordered flex-1" />

                <select wire:model.live="statusFilter" class="select select-bordered w-full sm:w-40">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>

                <select wire:model.live="departmentFilter" class="select select-bordered w-full sm:w-52">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($policies->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <p class="text-gray-500 font-medium">No policies found</p>
                    @can('create-policies')
                        @if(!$search && !$statusFilter && !$departmentFilter)
                            <button wire:click="create" type="button"
                                    class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">
                                Create First Policy
                            </button>
                        @endif
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">Current Version</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($policies as $policy)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $policy->reference_code }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('policies.show', $policy) }}" class="font-medium text-gray-900 hover:underline">
                                            {{ $policy->title }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $policy->policy_category }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $policy->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $policy->currentVersion?->version_label ?? '—' }}
                                        @if($policy->currentVersion)
                                            <span class="badge badge-sm ml-1">{{ $policy->currentVersion->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge badge-sm {{ $policy->status === 'active' ? 'badge-success' : ($policy->status === 'archived' ? 'badge-ghost' : 'badge-warning') }}">
                                            {{ ucfirst($policy->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('policies.show', $policy) }}" class="btn btn-ghost btn-xs" title="Open">
                                                View
                                            </a>
                                            @can('edit-policies')
                                                <button wire:click="edit({{ $policy->id }})" type="button" class="btn btn-ghost btn-xs" title="Edit">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('archive-policies')
                                                @if($policy->status !== 'archived')
                                                    <button wire:click="confirmArchive({{ $policy->id }})" type="button"
                                                            class="btn btn-ghost btn-xs text-red-500" title="Archive">
                                                        Archive
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($policies->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $policies->links() }}</div>
                @endif
            @endif
        </div>
    </div>

</div>

    {{-- Create / Edit modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Policy' : 'New Policy' }}</h2>
                    <button wire:click="closeModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label label-text text-xs font-medium">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" class="input input-bordered w-full" />
                        @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Reference Code <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="reference_code" class="input input-bordered w-full" placeholder="e.g. POL-EDU-004" />
                            @error('reference_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Category <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="policy_category" class="input input-bordered w-full" placeholder="e.g. Safeguarding" />
                            @error('policy_category') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label label-text text-xs font-medium">Diocese <span class="text-red-500">*</span></label>
                        <select wire:model.live="organization_id" class="select select-bordered w-full">
                            <option value="">— Select —</option>
                            @foreach($dioceses as $diocese)
                                <option value="{{ $diocese->id }}">{{ $diocese->display_name ?? $diocese->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('organization_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    @if(count($availableDepartments) > 0)
                        <div>
                            <label class="label label-text text-xs font-medium">Owning Department</label>
                            <select wire:model="department_id" class="select select-bordered w-full">
                                <option value="">— None (diocese-wide) —</option>
                                @foreach($availableDepartments as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;"
                                wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Archive confirmation --}}
    @if($confirmingArchiveId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Archive Policy?</h3>
                <p class="text-sm text-gray-500 mb-5">This will archive the policy and all its versions.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="cancelArchive" type="button" class="btn btn-ghost btn-sm">Cancel</button>
                    <button wire:click="archive" type="button" class="btn btn-sm bg-red-500 hover:bg-red-600 border-0 text-white">
                        Archive
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
