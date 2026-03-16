<div class="p-6">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Allowed Email Domains</h2>
            <p class="text-gray-600">Manage system Email Domains</p>
        </div>
    </div>

    <div class="mb-4">
        <form wire:submit.prevent="{{ $editing ? 'update' : 'create' }}">
            <div class="mb-4">
                <label for="domain" class="block text-sm font-medium text-gray-700">Domain</label>
                <input type="text" id="domain" wire:model="domain"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('domain')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label for="organization_id" class="block text-sm font-medium text-gray-700">Organization</label>
                <select id="organization_id" wire:model="organization_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Select an organization</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->legal_name }}</option>
                    @endforeach
                </select>
                @error('organization_id')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label for="is_active" class="block text-sm font-medium text-gray-700">Active</label>
                <input type="checkbox" id="is_active" wire:model="is_active" class="mt-1">
                @error('is_active')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md">
                    {{ $editing ? 'Update Domain' : 'Add Domain' }}
                </button>
                @if ($editing)
                    <button type="button" wire:click="resetFields"
                        class="px-4 py-2 bg-gray-500 text-white rounded-md">Cancel</button>
                @endif
            </div>
        </form>
    </div>

    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization
                    ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($domains as $domain)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $domain->domain }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $domain->organization->legal_name ?? 'not provided'}}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $domain->is_active ? 'Yes' : 'No' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button wire:click="edit({{ $domain->id }})"
                            class="text-blue-600 hover:text-blue-900">Edit</button>
                        <button wire:click="delete({{ $domain->id }})"
                            class="text-red-600 hover:text-red-900 ml-2">Delete</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $domains->links() }}
    </div>
</div>
