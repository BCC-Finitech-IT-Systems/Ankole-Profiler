<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Dioceses</h1>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-4 w-full">

        @if (session('success'))
            <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form: creation is Super Admin-only; Org Admins edit their own diocese --}}
        @if ($editing || auth()->user()->hasRole('Super Admin'))
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">{{ $editing ? 'Edit Diocese' : 'Add New Diocese' }}</h3>
                    @if(!$editing)
                        <x-fill-sample-data-btn />
                    @endif
                </div>
                <div class="p-5">
                    <form wire:submit.prevent="{{ $editing ? 'update' : 'create' }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="legal_name" class="block text-xs font-medium text-gray-700 mb-1.5">Legal Name <span class="text-red-500">*</span></label>
                                <input type="text" id="legal_name" wire:model="legal_name" placeholder="e.g., North Ankole Diocese"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('legal_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="display_name" class="block text-xs font-medium text-gray-700 mb-1.5">Display Name</label>
                                <input type="text" id="display_name" wire:model="display_name" placeholder="Defaults to legal name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('display_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="code" class="block text-xs font-medium text-gray-700 mb-1.5">Code <span class="text-red-500">*</span></label>
                                <input type="text" id="code" wire:model="code" placeholder="e.g., NAD-001"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('code')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="contact_email" class="block text-xs font-medium text-gray-700 mb-1.5">Contact Email</label>
                                <input type="email" id="contact_email" wire:model="contact_email" placeholder="office@diocese.org"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('contact_email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="contact_phone" class="block text-xs font-medium text-gray-700 mb-1.5">Contact Phone</label>
                                <input type="text" id="contact_phone" wire:model="contact_phone" placeholder="+2567..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('contact_phone')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="city" class="block text-xs font-medium text-gray-700 mb-1.5">City</label>
                                <input type="text" id="city" wire:model="city" placeholder="e.g., Mbarara"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                @error('city')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                            <textarea id="description" wire:model="description" rows="2" placeholder="Region and scope of this diocese"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                            @error('description')
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
                                {{ $editing ? 'Update Diocese' : 'Add Diocese' }}
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
        @endif

        {{-- Dioceses Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Diocese</th>
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="px-4 py-3 text-left font-medium">City</th>
                            <th class="px-4 py-3 text-left font-medium">Organizations</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($dioceses as $diocese)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ $diocese->display_name ?? $diocese->legal_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $diocese->contact_email }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $diocese->code }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $diocese->city ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $diocese->child_organizations_count }}</td>
                                <td class="px-4 py-3">
                                    @if($diocese->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="edit({{ $diocese->id }})"
                                            class="text-xs font-medium hover:underline" style="color:#982B55;">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="toggleActive({{ $diocese->id }})"
                                            class="text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline">
                                            {{ $diocese->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-gray-400 text-sm">No dioceses yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $dioceses->links() }}
            </div>
        </div>

    </div>
</div>
