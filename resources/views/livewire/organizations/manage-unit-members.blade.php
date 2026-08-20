<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h4 class="font-semibold text-gray-800 text-sm">Unit Members</h4>
        @can('edit-units')
            <button wire:click="$set('showAddForm', true)"
                    class="btn btn-xs border-0 text-white gap-1" style="background:#982B55;">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Member
            </button>
        @endcan
    </div>

    {{-- Add Member Form --}}
    @if($showAddForm)
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 space-y-3">
            <p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">Add New Member</p>

            {{-- Person search --}}
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="personSearch"
                       placeholder="Search by name or person ID…"
                       class="input input-bordered input-sm w-full pr-8" />
                @if($selectedPersonId)
                    <svg class="w-4 h-4 text-green-500 absolute right-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                @endif

                @if(count($personResults))
                    <ul class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg text-sm max-h-48 overflow-y-auto">
                        @foreach($personResults as $p)
                            <li wire:click="selectPerson({{ $p['id'] }}, '{{ addslashes($p['given_name'] . ' ' . $p['family_name']) }}')"
                                class="px-3 py-2 cursor-pointer hover:bg-rose-50 flex items-center justify-between">
                                <span>{{ $p['given_name'] }} {{ $p['family_name'] }}</span>
                                <span class="text-xs text-gray-400">{{ $p['person_id'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($selectedPersonId)
                <p class="text-xs text-green-700">
                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $selectedPersonName }}
                </p>
            @endif

            {{-- Role --}}
            <div>
                <label class="text-xs text-gray-600 font-medium">Role</label>
                @if($availableRoleTypes->isEmpty())
                    <p class="text-xs text-gray-500 mt-1">
                        No roles are available for this unit's department yet. Create an occupation
                        for the department before adding members.
                    </p>
                @else
                    <select wire:model="newRoleTypeId" class="select select-bordered select-sm w-full mt-1">
                        <option value="">Select a role…</option>
                        @foreach($availableRoleTypes as $roleType)
                            <option value="{{ $roleType->id }}">{{ $roleType->name }}</option>
                        @endforeach
                    </select>
                @endif
                @error('newRoleTypeId')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Permissions --}}
            <div>
                <label class="text-xs text-gray-600 font-medium">Permissions</label>
                <div class="flex gap-4 mt-1">
                    <label class="flex items-center gap-1 text-xs cursor-pointer">
                        <input type="checkbox" wire:model="newCanView" class="checkbox checkbox-xs" />
                        View
                    </label>
                    <label class="flex items-center gap-1 text-xs cursor-pointer">
                        <input type="checkbox" wire:model="newCanEdit" class="checkbox checkbox-xs" />
                        Edit
                    </label>
                    <label class="flex items-center gap-1 text-xs cursor-pointer">
                        <input type="checkbox" wire:model="newCanApprove" class="checkbox checkbox-xs" />
                        Approve
                    </label>
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <button wire:click="addMember"
                        wire:loading.attr="disabled"
                        class="btn btn-xs border-0 text-white" style="background:#982B55;">
                    <span wire:loading wire:target="addMember">Saving…</span>
                    <span wire:loading.remove wire:target="addMember">Save Member</span>
                </button>
                <button wire:click="$set('showAddForm', false)" class="btn btn-xs btn-ghost">Cancel</button>
            </div>

            @error('selectedPersonId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- Members list --}}
    @if($members->isEmpty())
        <p class="text-xs text-gray-400 text-center py-4">No active members yet.</p>
    @else
        <div class="space-y-2">
            @foreach($members as $member)
                <div class="flex items-center justify-between bg-white border border-gray-100 rounded-lg px-3 py-2 gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($member->person?->given_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-800 truncate">
                                {{ $member->person?->given_name }} {{ $member->person?->family_name }}
                            </p>
                            <p class="text-xs text-gray-400 capitalize">{{ $member->role }}</p>
                        </div>
                    </div>

                    {{-- Permission toggles --}}
                    <div class="flex items-center gap-2 text-xs flex-shrink-0">
                        @foreach(['view' => 'V', 'edit' => 'E', 'approve' => 'A'] as $perm => $label)
                            @can('edit-units')
                                <button wire:click="updatePermissions({{ $member->id }}, '{{ $perm }}', {{ $member->{'can_'.$perm} ? 'false' : 'true' }})"
                                        title="{{ ucfirst($perm) }}"
                                        class="w-5 h-5 rounded text-xs font-bold border transition-colors
                                               {{ $member->{'can_'.$perm}
                                                    ? 'text-white border-transparent'
                                                    : 'text-gray-400 border-gray-200 bg-white hover:border-rose-300' }}"
                                        @if($member->{'can_'.$perm}) style="background:#982B55;" @endif>
                                    {{ $label }}
                                </button>
                            @else
                                <span title="{{ ucfirst($perm) }}"
                                      class="w-5 h-5 rounded text-xs font-bold border text-center leading-5
                                             {{ $member->{'can_'.$perm}
                                                  ? 'text-white border-transparent'
                                                  : 'text-gray-300 border-gray-200' }}"
                                      @if($member->{'can_'.$perm}) style="background:#982B55;" @endif>
                                    {{ $label }}
                                </span>
                            @endcan
                        @endforeach

                        @can('edit-units')
                            @if($confirmRevokeId === $member->id)
                                <button wire:click="revokeRole" class="btn btn-xs btn-error text-white">Confirm</button>
                                <button wire:click="cancelRevoke" class="btn btn-xs btn-ghost">Cancel</button>
                            @else
                                <button wire:click="confirmRevoke({{ $member->id }})"
                                        title="Revoke access"
                                        class="text-gray-300 hover:text-red-500 transition-colors ml-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Granted-at note --}}
    <p class="text-xs text-gray-300">V = View &nbsp; E = Edit &nbsp; A = Approve &nbsp; (click to toggle)</p>

</div>
