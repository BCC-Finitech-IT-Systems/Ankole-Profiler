<div class="min-h-full">

    {{-- Page header rendered inline (not via x-slot="header") — see the
         regression documented in tests/Feature/ProjectsManagementTest.php:
         Livewire's page-layout mechanism renders x-slot="header" content
         outside this component's wire:id root, silently breaking wire:click. --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Governance & Compliance</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Audit Report Register</h1>
        </div>
        @can('create-audit-reports')
            <button wire:click="create" type="button" class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Audit Report
            </button>
        @endcan
    </div>

<div class="py-6 px-4 md:px-8">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="w-full space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <input type="text" wire:model.live.debounce.300ms="search" class="input input-bordered input-sm" placeholder="Search title or issuing body…" />
                <select wire:model.live="auditTypeFilter" class="select select-bordered select-sm">
                    <option value="">All Types</option>
                    <option value="internal">Internal</option>
                    <option value="external">External</option>
                    <option value="financial">Financial</option>
                    <option value="compliance">Compliance</option>
                    <option value="operational">Operational</option>
                    <option value="institutional">Institutional</option>
                </select>
                <select wire:model.live="statusFilter" class="select select-bordered select-sm">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="issued">Issued</option>
                    <option value="under_review">Under Review</option>
                    <option value="closed">Closed</option>
                </select>
                <select wire:model.live="departmentFilter" class="select select-bordered select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($reports->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <p class="text-gray-500 font-medium">No audit reports found</p>
                    @can('create-audit-reports')
                        <button wire:click="create" type="button" class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">Record First Audit Report</button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Audited</th>
                                <th class="px-4 py-3 text-left">Issue Date</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($reports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('audit-reports.show', $report) }}" class="font-medium text-gray-900 hover:underline">{{ $report->title }}</a>
                                        @if($report->restricted)
                                            <span class="badge badge-sm badge-warning ml-1">restricted</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ ucfirst($report->audit_type) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $report->department?->name ?? $report->audited_institution_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $report->issue_date?->format('d M Y') }}</td>
                                    <td class="px-4 py-3"><span class="badge badge-sm">{{ str_replace('_', ' ', $report->status) }}</span></td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('audit-reports.show', $report) }}" class="btn btn-ghost btn-xs">View</a>
                                            @can('edit-audit-reports')
                                                <button wire:click="edit({{ $report->id }})" type="button" class="btn btn-ghost btn-xs">Edit</button>
                                            @endcan
                                            @can('archive-audit-reports')
                                                <button wire:click="confirmArchive({{ $report->id }})" type="button" class="btn btn-ghost btn-xs text-red-600">Archive</button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($reports->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $reports->links() }}</div>
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
                    <h2 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Audit Report' : 'Record Audit Report' }}</h2>
                    <button wire:click="closeModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>
                <form wire:submit="save" class="p-5 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Organization <span class="text-red-500">*</span></label>
                            <select wire:model.live="organization_id" class="select select-bordered w-full">
                                <option value="">— Select —</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->display_name ?? $org->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('organization_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Audited Department</label>
                            <select wire:model="department_id" class="select select-bordered w-full">
                                <option value="">— None —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="label label-text text-xs font-medium">Audited Institution (if external)</label>
                            <input type="text" wire:model="audited_institution_name" class="input input-bordered w-full" />
                        </div>
                        <div class="col-span-2">
                            <label class="label label-text text-xs font-medium">Title <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="title" class="input input-bordered w-full" />
                            @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Audit Type <span class="text-red-500">*</span></label>
                            <select wire:model="audit_type" class="select select-bordered w-full">
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                                <option value="financial">Financial</option>
                                <option value="compliance">Compliance</option>
                                <option value="operational">Operational</option>
                                <option value="institutional">Institutional</option>
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Status</label>
                            <select wire:model="status" class="select select-bordered w-full">
                                <option value="draft">Draft</option>
                                <option value="issued">Issued</option>
                                <option value="under_review">Under Review</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Period Start</label>
                            <input type="date" wire:model="period_start" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Period End</label>
                            <input type="date" wire:model="period_end" class="input input-bordered w-full" />
                            @error('period_end') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Issuing Body / Auditor <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="issuing_body" class="input input-bordered w-full" />
                            @error('issuing_body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Issue Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="issue_date" class="input input-bordered w-full" />
                            @error('issue_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label class="label label-text text-xs font-medium">Overall Rating</label>
                            <input type="text" wire:model="overall_rating" class="input input-bordered w-full" placeholder="e.g. Satisfactory" />
                        </div>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Summary</label>
                        <textarea wire:model="summary" class="textarea textarea-bordered w-full" rows="3"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="restricted" class="checkbox checkbox-sm" /> Restricted report
                    </label>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Archive confirmation --}}
    @if($confirmingArchiveId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancelArchive">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5">
                <h2 class="text-base font-semibold text-gray-800 mb-2">Archive Audit Report?</h2>
                <p class="text-sm text-gray-500 mb-5">This will remove the report from the active register.</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="cancelArchive" type="button" class="btn btn-ghost btn-sm">Cancel</button>
                    <button wire:click="archive" type="button" class="btn btn-sm bg-red-500 hover:bg-red-600 border-0 text-white">Archive</button>
                </div>
            </div>
        </div>
    @endif

</div>
