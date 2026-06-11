<div class="space-y-6">

    {{-- Person Contacts --}}
    <div class="space-y-4">

        {{-- Search --}}
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by name, email, or phone..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
            </div>
        </div>

        {{-- Persons Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Person Contact Details</h3>
                <span class="text-xs text-gray-400">{{ $persons->total() }} {{ Str::plural('person', $persons->total()) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Person</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Primary Email</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Primary Phone</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Other Contacts</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($persons as $person)
                            @php
                                $primaryEmail = $person->emailAddresses->firstWhere('is_primary', true)
                                    ?? $person->emailAddresses->first();
                                $primaryPhone = $person->phones->firstWhere('is_primary', true)
                                    ?? $person->phones->first();
                                $extraEmails = $person->emailAddresses->count() - ($primaryEmail ? 1 : 0);
                                $extraPhones = $person->phones->count() - ($primaryPhone ? 1 : 0);
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#982B55;">
                                            {{ strtoupper(substr($person->given_name, 0, 1) . substr($person->family_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $person->given_name }} {{ $person->family_name }}</div>
                                            <div class="text-xs text-gray-400">{{ $person->person_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($primaryEmail)
                                        <div class="text-gray-700">{{ $primaryEmail->email }}</div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">{{ $primaryEmail->type }}</span>
                                            @if ($primaryEmail->is_verified)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">Verified</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">Unverified</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs italic">No email</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($primaryPhone)
                                        <div class="text-gray-700">{{ $primaryPhone->number }}</div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">{{ $primaryPhone->type }}</span>
                                            @if ($primaryPhone->is_verified)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">Verified</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">Unverified</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs italic">No phone</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        @if ($extraEmails > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">+{{ $extraEmails }} email{{ $extraEmails > 1 ? 's' : '' }}</span>
                                        @endif
                                        @if ($extraPhones > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">+{{ $extraPhones }} phone{{ $extraPhones > 1 ? 's' : '' }}</span>
                                        @endif
                                        @if ($extraEmails === 0 && $extraPhones === 0)
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-16">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No persons found</p>
                                    <p class="text-xs text-gray-500">Try adjusting your search</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                {{ $persons->links() }}
            </div>
        </div>
    </div>

    {{-- Organization Contacts --}}
    @if ($organizations->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Organization Contacts</h3>
                <span class="text-xs text-gray-400">{{ $organizations->count() }} {{ Str::plural('organization', $organizations->count()) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Organization</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact Email</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact Phone</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach ($organizations as $org)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $org->legal_name }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $org->contact_email ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $org->contact_phone ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
