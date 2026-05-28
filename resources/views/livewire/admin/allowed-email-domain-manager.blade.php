<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Allowed Email Domains</h1>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-4 w-full">

        {{-- Form --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ $editing ? 'Edit Domain' : 'Add New Domain' }}</h3>
            </div>
            <div class="p-5">
                <form wire:submit.prevent="{{ $editing ? 'update' : 'create' }}" class="space-y-4">
                    <div>
                        <label for="domain" class="block text-xs font-medium text-gray-700 mb-1.5">Domain</label>
                        <input type="text" id="domain" wire:model="domain" placeholder="e.g., ankole.org"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('domain')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="organization_id" class="block text-xs font-medium text-gray-700 mb-1.5">Organization</label>
                        <select id="organization_id" wire:model="organization_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                            <option value="">Select an organization</option>
                            @foreach ($organizations as $organization)
                                <option value="{{ $organization->id }}">{{ $organization->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('organization_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" id="is_active" wire:model="is_active"
                            class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                            style="background:#982B55;">
                            {{ $editing ? 'Update Domain' : 'Add Domain' }}
                        </button>
                        @if ($editing)
                            <button type="button" wire:click="resetFields"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Domains Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Domain</th>
                            <th class="px-4 py-3 text-left font-medium">Organization</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($domains as $domain)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-sm text-gray-800">{{ $domain->domain }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $domain->organization->legal_name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($domain->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="edit({{ $domain->id }})"
                                            class="text-xs font-medium hover:underline" style="color:#982B55;">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="delete({{ $domain->id }})"
                                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-400 text-sm">No domains configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $domains->links() }}
            </div>
        </div>

    </div>
</div>
