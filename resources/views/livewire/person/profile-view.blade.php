<div>
    @if ($showModal && $person)
        <dialog id="profile_modal" class="modal" open>
            <div class="modal-box max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div
                    class="text-white p-6 rounded-t-lg flex justify-between items-center" style="background:#982B55;">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $person->full_name }}</h2>
                        <p class="text-white/80">Person ID: {{ $person->person_id }} | Global ID:
                            {{ substr($person->global_identifier, 0, 8) }}...</p>
                    </div>
                    <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    {{-- Basic Information --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg></span> Basic Information</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Full Name:</strong> {{ $person->full_name }}</p>
                                <p><strong>Date of Birth:</strong>
                                    @php
                                        $dob = $person->date_of_birth;
                                        if (is_string($dob)) {
                                            try {
                                                $dob = \Carbon\Carbon::parse($dob);
                                            } catch (Exception $e) {
                                                $dob = null;
                                            }
                                        }
                                    @endphp
                                    {{ $dob ? $dob->format('F j, Y') : 'Not specified' }}
                                </p>
                                <p><strong>Gender:</strong>
                                    {{ $person->gender ? ucfirst($person->gender) : 'Not specified' }}</p>
                                <p><strong>Status:</strong>
                                    <span
                                        class="px-2 py-1 rounded text-xs {{ $person->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($person->status) }}</span>
                                </p>
                                @if ($person->classification)
                                    <p><strong>Classifications:</strong></p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @php
                                            $classifications = is_array($person->classification) ? $person->classification : (is_string($person->classification) ? array_filter(explode(',', $person->classification)) : []);
                                        @endphp
                                        @foreach ($classifications as $class)
                                            <span class="px-2 py-1 bg-rose-50 text-rose-700 rounded text-xs">{{ $class }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 12.414a4 4 0 10-5.657 5.657l4.243 4.243a8 8 0 0011.314-11.314l-4.243 4.243z" />
                                    </svg></span> Address Information</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Address:</strong> {{ $person->address ?: 'Not specified' }}</p>
                                <p><strong>City:</strong> {{ $person->city ?: 'Not specified' }}</p>
                                <p><strong>District:</strong> {{ $person->district ?: 'Not specified' }}</p>
                                <p><strong>Country:</strong> {{ $person->country ?: 'Not specified' }}</p>
                            </div>
                        </div>
                    </div>
                    {{-- Contact Information --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h2.153a2 2 0 011.986 1.672l.74 4.435a2 2 0 01-1.08 2.12l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a2 2 0 012.12-1.08l4.435.74A2 2 0 0119 17v2a2 2 0 01-2 2h-2C7.82 21 3 16.18 3 9V5z" />
                                    </svg></span> Phone Numbers</h3>
                            @forelse($person->phones as $phone)
                                <div class="mb-2 p-2 bg-white rounded border">
                                    <p class="font-medium">{{ $phone->number }}</p>
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span>{{ ucfirst($phone->type) }}</span>
                                        <div class="flex gap-2">
                                            @if ($phone->is_primary)
                                                <span class="px-1 bg-rose-50 text-rose-700 rounded">Primary</span>
                                            @endif
                                            @if ($phone->is_verified)
                                                <span class="px-1 bg-green-100 text-green-800 rounded">Verified</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 italic">No phone numbers</p>
                            @endforelse
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12H8m8 0a4 4 0 01-8 0m8 0a4 4 0 00-8 0m8 0V8a4 4 0 00-8 0v4m8 0V8a4 4 0 00-8 0v4" />
                                    </svg></span> Email Addresses</h3>
                            @forelse($person->emailAddresses as $email)
                                <div class="mb-2 p-2 bg-white rounded border">
                                    <p class="font-medium">{{ $email->email }}</p>
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span>{{ ucfirst($email->type) }}</span>
                                        <div class="flex gap-2">
                                            @if ($email->is_primary)
                                                <span class="px-1 bg-rose-50 text-rose-700 rounded">Primary</span>
                                            @endif
                                            @if ($email->is_verified)
                                                <span class="px-1 bg-green-100 text-green-800 rounded">Verified</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 italic">No email addresses</p>
                            @endforelse
                        </div>
                    </div>
                    {{-- Identifiers --}}
                    <div class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg></span> Identifiers</h3>
                            @forelse($person->identifiers as $identifier)
                                <div class="mb-2 p-3 bg-white rounded border">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium">{{ $identifier->identifier }}</p>
                                            <p class="text-sm text-gray-600">
                                                {{ ucfirst(str_replace('_', ' ', $identifier->type)) }}</p>
                                            @if ($identifier->issuing_authority)
                                                <p class="text-xs text-gray-500">Issued by:
                                                    {{ $identifier->issuing_authority }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right text-xs">
                                            @if ($identifier->is_verified)
                                                <span
                                                    class="px-2 py-1 bg-green-100 text-green-800 rounded">Verified</span>
                                            @endif
                                            <p class="text-gray-500 mt-1">{{ ucfirst($identifier->status) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 italic">No identifiers</p>
                            @endforelse
                        </div>
                    </div>
                    {{-- Organization Affiliations --}}
                    <div class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                    class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 7v4a1 1 0 001 1h3v4a1 1 0 001 1h3v4a1 1 0 001 1h3v-4a1 1 0 00-1-1h-3v-4a1 1 0 00-1-1H7V7a1 1 0 00-1-1H3z" />
                                    </svg></span> Organization Affiliations</h3>
                            @forelse($person->affiliations as $affiliation)
                                <div
                                    class="mb-3 p-4 bg-white rounded-lg border {{ $affiliation->status === 'active' ? 'border-green-200' : 'border-gray-200' }}">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-semibold text-lg">
                                                {{ $affiliation->Organization->display_name ?? ($affiliation->Organization->legal_name ?? 'Unknown Organization') }}
                                            </h4>
                                            <p class="text-gray-600">{{ $affiliation->role_type }} @if ($affiliation->role_title)
                                                    - {{ $affiliation->role_title }}
                                                @endif
                                            </p>
                                            @if ($affiliation->site)
                                                <p class="text-sm text-gray-500">Site: {{ $affiliation->site }}</p>
                                            @endif
                                        </div>
                                        <span
                                            class="px-3 py-1 rounded text-sm {{ $affiliation->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($affiliation->status) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <p><strong>Started:</strong>
                                                {{ $affiliation->start_date->format('M j, Y') }}</p>
                                            @if ($affiliation->end_date)
                                                <p><strong>Ended:</strong>
                                                    {{ $affiliation->end_date->format('M j, Y') }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            <p><strong>Duration:</strong> {{ $affiliation->duration }}</p>
                                            <p><strong>Affiliation ID:</strong> {{ $affiliation->affiliation_id }}</p>
                                        </div>
                                    </div>
                                    @if ($affiliation->permissions)
                                        <div class="mt-2">
                                            <p class="text-sm font-medium">Permissions:</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach ($affiliation->permissions as $permission)
                                                    <span
                                                        class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">{{ $permission }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-gray-500 italic">No organization affiliations</p>
                            @endforelse
                        </div>
                    </div>
                    {{-- Timeline/Audit Information --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3 text-gray-800"><span
                                class="inline-block align-middle"><svg class="w-5 h-5 text-black inline"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg></span> Timeline</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><strong>Created:</strong> {{ $person->created_at->format('F j, Y \a\t g:i A') }}</p>
                            <p><strong>Last Updated:</strong> {{ $person->updated_at->format('F j, Y \a\t g:i A') }}
                            </p>
                            <p><strong>Total Affiliations:</strong> {{ $person->affiliations->count() }}</p>
                            <p><strong>Active Affiliations:</strong>
                                {{ $person->affiliations->where('status', 'active')->count() }}</p>
                        </div>
                    </div>
                    {{-- Actions --}}
                    <div class="flex justify-end mt-6 pt-4 border-t">
                        <form method="dialog">
                            <button class="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Close</button>
                        </form>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    @endif
</div>
