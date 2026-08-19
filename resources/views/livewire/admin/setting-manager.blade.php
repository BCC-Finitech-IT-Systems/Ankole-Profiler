<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Settings</h1>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-4 w-full">

        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ $editing ? 'Edit Setting' : 'Add New Setting' }}</h3>
            </div>
            <div class="p-5">
                <form wire:submit.prevent="{{ $editing ? 'update' : 'create' }}" class="space-y-4">
                    <div>
                        <label for="key" class="block text-xs font-medium text-gray-700 mb-1.5">Key</label>
                        <input type="text" id="key" wire:model="key" placeholder="e.g., support_contact_email"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('key')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="label" class="block text-xs font-medium text-gray-700 mb-1.5">Label</label>
                        <input type="text" id="label" wire:model="label" placeholder="e.g., Support Contact Email"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('label')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="value" class="block text-xs font-medium text-gray-700 mb-1.5">Value</label>
                        <input type="text" id="value" wire:model="value" placeholder="e.g., support@ankoleprofiler.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('value')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                            style="background:#982B55;">
                            {{ $editing ? 'Update Setting' : 'Add Setting' }}
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

        {{-- Settings Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Key</th>
                            <th class="px-4 py-3 text-left font-medium">Label</th>
                            <th class="px-4 py-3 text-left font-medium">Value</th>
                            <th class="px-4 py-3 text-left font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($settings as $setting)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $setting->key }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $setting->label ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $setting->value ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="edit({{ $setting->id }})"
                                            class="text-xs font-medium hover:underline" style="color:#982B55;">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="delete({{ $setting->id }})"
                                            wire:confirm="Delete this setting?"
                                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-400 text-sm">No settings configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $settings->links() }}
            </div>
        </div>

    </div>
</div>
