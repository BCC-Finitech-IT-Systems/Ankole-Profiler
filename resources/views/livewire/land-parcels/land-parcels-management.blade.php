<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Land Registration</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Land & Property Register</h1>
        </div>
        @can('create-land-parcels')
            <button wire:click="create" type="button" class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Parcel
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
                <select wire:model.live="stageFilter" class="select select-bordered select-sm">
                    <option value="">All Stages</option>
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
                    <option value="disputed">Disputed</option>
                    <option value="closed">Closed</option>
                </select>
                <select wire:model.live="departmentFilter" class="select select-bordered select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model.live.debounce.300ms="districtFilter" class="input input-bordered input-sm" placeholder="District…" />
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="disputedOnly" class="checkbox checkbox-sm" /> Disputed only
                </label>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($parcels->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <p class="text-gray-500 font-medium">No land parcels found</p>
                    @can('create-land-parcels')
                        <button wire:click="create" type="button" class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">Register First Parcel</button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Property</th>
                                <th class="px-4 py-3 text-left">District</th>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">Stage</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($parcels as $parcel)
                                <tr class="hover:bg-gray-50 {{ $parcel->stage === 'disputed' ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $parcel->reference_number }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('land-parcels.show', $parcel) }}" class="font-medium text-gray-900 hover:underline">{{ $parcel->property_name }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $parcel->district ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $parcel->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3"><span class="badge badge-sm">{{ str_replace('_', ' ', $parcel->stage) }}</span></td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('land-parcels.show', $parcel) }}" class="btn btn-ghost btn-xs">View</a>
                                            @can('edit-land-parcels')
                                                <button wire:click="edit({{ $parcel->id }})" type="button" class="btn btn-ghost btn-xs">Edit</button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($parcels->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $parcels->links() }}</div>
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
                    <h2 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Parcel' : 'Register Parcel' }}</h2>
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
                            <label class="label label-text text-xs font-medium">Custodian Department</label>
                            <select wire:model="department_id" class="select select-bordered w-full">
                                <option value="">— None —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Reference Number <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="reference_number" class="input input-bordered w-full" />
                            @error('reference_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Property Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="property_name" class="input input-bordered w-full" />
                            @error('property_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">District</label>
                            <input type="text" wire:model="district" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Sub-county</label>
                            <input type="text" wire:model="sub_county" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Parish</label>
                            <input type="text" wire:model="parish" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Village</label>
                            <input type="text" wire:model="village" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Acreage</label>
                            <input type="number" step="0.01" wire:model="acreage" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Tenure Type</label>
                            <input type="text" wire:model="tenure_type" class="input input-bordered w-full" placeholder="e.g. Freehold" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Current Use</label>
                            <input type="text" wire:model="current_use" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Acquisition Date</label>
                            <input type="date" wire:model="acquisition_date" class="input input-bordered w-full" />
                        </div>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Acquisition Details</label>
                        <textarea wire:model="acquisition_details" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
