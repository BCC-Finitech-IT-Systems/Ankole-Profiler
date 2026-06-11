<li class="mb-2" style="margin-left: {{ $level * 20 }}px;">
    <div class="border rounded p-3 flex flex-col gap-1 bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <button class="font-semibold text-base text-blue-700 hover:underline focus:outline-none" style="background: none; border: none; padding: 0;" wire:click="selectUnit({{ $unit->id }})">
                    {{ $unit->name }}
                </button>
                <div class="text-xs text-gray-500">Organization: <span class="font-semibold text-gray-800">{{ optional($unit->Organization)->name ?? (optional(\App\Models\Organization::find($unit->organization_id))->name ?? 'N/A') }}</span></div>
                <div class="text-xs text-gray-600">Code: {{ $unit->code }}</div>
                <div class="text-xs text-gray-600">Description: {{ $unit->description }}</div>
                <div class="flex flex-wrap gap-2 mt-1">
                    <span class="text-xs {{ $unit->is_active ? 'text-green-600' : 'text-red-600' }} font-semibold">Status: {{ $unit->is_active ? 'Active' : 'Inactive' }}</span>
                    <span class="text-xs text-rose-600">Unit Type: {{ $unit->unit_type }}</span>
                    </div>
                    <span class="text-xs text-gray-700">Head: {{ $unit->unit_head ? (optional(\App\Models\Person::find($unit->unit_head))->full_name ?? 'N/A') : 'N/A' }}</span>
                    <span class="text-xs text-gray-700">Contact: {{ $unit->official_email ?? $unit->phone_contact ?? 'N/A' }}</span>
                
                <div class="text-xs text-gray-500 mt-1">
                    Members: <span class="font-bold">
                        {{ \App\Models\PersonAffiliation::where('domain_record_type', 'unit')->where('domain_record_id', $unit->id)->where('status', 'active')->count() }}
                    </span>
                </div>
            </div>
            <div class="flex gap-1">
                <button class="btn btn-xs" style="background-color: #00ADED; color: #fff;" wire:click="selectUnit({{ $unit->id }})" title="View Details">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </button>
                @can('move-units')
                <button class="btn btn-xs btn-warning" wire:click="startMove({{ $unit->id }})" title="Move Unit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16M4 12h16" /></svg>
                </button>
                @endcan
                @can('edit-units')
                <button class="btn btn-xs btn-outline btn-accent" wire:click="editUnit({{ $unit->id }})" title="Edit Unit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 10-4-4l-8 8v3z" /></svg>
                </button>
                @endcan
                @can('delete-units')
                <button class="btn btn-xs btn-outline btn-error" wire:click="deleteUnit({{ $unit->id }})" title="Delete Unit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
                @endcan
            </div>
        </div>

        @if($movingUnitId === $unit->id)
            <form wire:submit.prevent="moveUnit" class="mt-2 flex flex-col gap-2">
                <label for="newParentId-{{ $unit->id }}" class="text-xs">Select new parent:</label>
                <select id="newParentId-{{ $unit->id }}" wire:model="newParentId" class="input input-xs">
                    <option value="">-- No Parent (Top Level) --</option>
                    @foreach($units as $possibleParent)
                        @if($possibleParent->id !== $unit->id)
                            <option value="{{ $possibleParent->id }}">{{ $possibleParent->name }}</option>
                        @endif
                    @endforeach
                </select>
                <button type="submit" class="btn btn-xs btn-success">Confirm Move</button>
                <button type="button" class="btn btn-xs btn-secondary" wire:click="$set('movingUnitId', null)">Cancel</button>
            </form>
        @endif
    </div>
    @if(!empty($unit->children))
        <ul class="ml-2">
            @foreach($unit->children as $child)
                @include('livewire.organizations.partials.unit-tree', ['unit' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
