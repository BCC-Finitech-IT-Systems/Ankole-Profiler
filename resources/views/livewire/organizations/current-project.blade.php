<div>
    @if ($organization)
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                {{-- Header --}}
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-white">
                                        {{ substr($organization->legal_name, 0, 1) }}
                                    </span>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white">{{ $organization->legal_name }}</h1>
                                    @if ($organization->display_name && $organization->display_name !== $organization->legal_name)
                                        <p class="text-blue-100 text-sm mt-1">{{ $organization->display_name }}</p>
                                    @endif
                                    <div class="flex items-center gap-3 mt-2">
                                        @if ($organization->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                                                Inactive
                                            </span>
                                        @endif
                                        @if ($organization->category)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ ucfirst($organization->category) }}
                                            </span>
                                        @endif
                                        @if ($organization->code)
                                            <span class="text-blue-200 text-sm">Code: {{ $organization->code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Bar --}}
                    <div class="grid grid-cols-3 divide-x divide-gray-200 bg-gray-50">
                        <div class="px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $membersCount }}</p>
                            <p class="text-sm text-gray-500">Total Members</p>
                        </div>
                        <div class="px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-green-600">{{ $activeMembersCount }}</p>
                            <p class="text-sm text-gray-500">Active Members</p>
                        </div>
                        <div class="px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $sitesCount }}</p>
                            <p class="text-sm text-gray-500">Sites</p>
                        </div>
                    </div>
                </div>

                {{-- Details Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Basic Information --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                        </div>
                        <dl class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Legal Name</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ $organization->legal_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Organization Type</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ $organization->organization_type ?? 'Not specified' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Registration Number</dt>
                                    <dd class="text-sm text-gray-900 mt-1">{{ $organization->registration_number ?? 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date Established</dt>
                                    <dd class="text-sm text-gray-900 mt-1">
                                        {{ $organization->date_established ? \Carbon\Carbon::parse($organization->date_established)->format('F j, Y') : 'Not provided' }}
                                    </dd>
                                </div>
                                @if ($organization->tax_identification_number)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Tax ID Number</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->tax_identification_number }}</dd>
                                    </div>
                                @endif
                                @if ($organization->website_url)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Website</dt>
                                        <dd class="text-sm mt-1">
                                            <a href="{{ $organization->website_url }}" target="_blank" rel="noopener noreferrer"
                                                class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                                {{ $organization->website_url }}
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                            </div>
                            @if ($organization->description)
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Description</dt>
                                    <dd class="text-sm text-gray-900 leading-relaxed">{{ $organization->description }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Address & Location --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Address & Location</h3>
                        </div>
                        <dl class="space-y-4">
                            @if ($organization->address_line_1 || $organization->address)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                                    <dd class="text-sm text-gray-900 mt-1">
                                        {{ $organization->address_line_1 ?? $organization->address }}
                                        @if ($organization->address_line_2)
                                            <br>{{ $organization->address_line_2 }}
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            <div class="grid grid-cols-2 gap-4">
                                @if ($organization->city)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">City</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->city }}</dd>
                                    </div>
                                @endif
                                @if ($organization->district)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">District</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->district }}</dd>
                                    </div>
                                @endif
                                @if ($organization->country)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Country</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->country }}</dd>
                                    </div>
                                @endif
                                @if ($organization->postal_code)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Postal Code</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->postal_code }}</dd>
                                    </div>
                                @endif
                            </div>
                            @if ($organization->latitude && $organization->longitude)
                                <div class="p-3 bg-green-50 rounded-lg">
                                    <dt class="text-sm font-medium text-gray-500 mb-1">GPS Coordinates</dt>
                                    <dd class="text-sm text-gray-900 font-mono">{{ $organization->latitude }}, {{ $organization->longitude }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Contact Information --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Contact Information</h3>
                        </div>
                        <dl class="space-y-5">
                            @if ($organization->primary_contact_name)
                                <div class="p-4 bg-purple-50 rounded-lg">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Primary Contact</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Name</dt>
                                            <dd class="text-sm text-gray-900 mt-0.5">{{ $organization->primary_contact_name }}</dd>
                                        </div>
                                        @if ($organization->primary_contact_title)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-500">Title</dt>
                                                <dd class="text-sm text-gray-900 mt-0.5">{{ $organization->primary_contact_title }}</dd>
                                            </div>
                                        @endif
                                        @if ($organization->primary_contact_email)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-500">Email</dt>
                                                <dd class="text-sm mt-0.5">
                                                    <a href="mailto:{{ $organization->primary_contact_email }}" class="text-blue-600 hover:text-blue-800">
                                                        {{ $organization->primary_contact_email }}
                                                    </a>
                                                </dd>
                                            </div>
                                        @endif
                                        @if ($organization->primary_contact_phone)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-500">Phone</dt>
                                                <dd class="text-sm mt-0.5">
                                                    <a href="tel:{{ $organization->primary_contact_phone }}" class="text-blue-600 hover:text-blue-800">
                                                        {{ $organization->primary_contact_phone }}
                                                    </a>
                                                </dd>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                @if ($organization->contact_email)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">General Email</dt>
                                        <dd class="text-sm mt-1">
                                            <a href="mailto:{{ $organization->contact_email }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $organization->contact_email }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                                @if ($organization->contact_phone)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">General Phone</dt>
                                        <dd class="text-sm mt-1">
                                            <a href="tel:{{ $organization->contact_phone }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $organization->contact_phone }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                            </div>

                            @if ($organization->secondary_contact_name)
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Secondary Contact</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500">Name</dt>
                                            <dd class="text-sm text-gray-900 mt-0.5">{{ $organization->secondary_contact_name }}</dd>
                                        </div>
                                        @if ($organization->secondary_contact_email)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-500">Email</dt>
                                                <dd class="text-sm mt-0.5">
                                                    <a href="mailto:{{ $organization->secondary_contact_email }}" class="text-blue-600 hover:text-blue-800">
                                                        {{ $organization->secondary_contact_email }}
                                                    </a>
                                                </dd>
                                            </div>
                                        @endif
                                        @if ($organization->secondary_contact_phone)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-500">Phone</dt>
                                                <dd class="text-sm mt-0.5">
                                                    <a href="tel:{{ $organization->secondary_contact_phone }}" class="text-blue-600 hover:text-blue-800">
                                                        {{ $organization->secondary_contact_phone }}
                                                    </a>
                                                </dd>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Regulatory & Licensing --}}
                    @if ($organization->regulatory_body || $organization->license_number)
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Regulatory & Licensing</h3>
                            </div>
                            <dl class="grid grid-cols-2 gap-4">
                                @if ($organization->regulatory_body)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Regulatory Body</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->regulatory_body }}</dd>
                                    </div>
                                @endif
                                @if ($organization->license_number)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">License Number</dt>
                                        <dd class="text-sm text-gray-900 mt-1 font-mono">{{ $organization->license_number }}</dd>
                                    </div>
                                @endif
                                @if ($organization->license_issue_date)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">License Issue Date</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ \Carbon\Carbon::parse($organization->license_issue_date)->format('F j, Y') }}</dd>
                                    </div>
                                @endif
                                @if ($organization->license_expiry_date)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">License Expiry Date</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ \Carbon\Carbon::parse($organization->license_expiry_date)->format('F j, Y') }}</dd>
                                    </div>
                                @endif
                                @if ($organization->accreditation_status)
                                    <div class="col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Accreditation Status</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->accreditation_status }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- System Configuration --}}
                    @if ($organization->default_currency || $organization->timezone || $organization->bank_name)
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">System Configuration</h3>
                            </div>
                            <dl class="grid grid-cols-2 gap-4">
                                @if ($organization->default_currency)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Default Currency</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->default_currency }}</dd>
                                    </div>
                                @endif
                                @if ($organization->timezone)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Timezone</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->timezone }}</dd>
                                    </div>
                                @endif
                                @if ($organization->default_language)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Default Language</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->default_language }}</dd>
                                    </div>
                                @endif
                                @if ($organization->bank_name)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Primary Bank</dt>
                                        <dd class="text-sm text-gray-900 mt-1">{{ $organization->bank_name }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- No Organization State --}}
        <div class="py-12">
            <div class="max-w-lg mx-auto text-center">
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No Project Assigned</h3>
                    <p class="mt-2 text-sm text-gray-500">You are not currently associated with any project (organization). Contact your administrator for assistance.</p>
                </div>
            </div>
        </div>
    @endif
</div>
