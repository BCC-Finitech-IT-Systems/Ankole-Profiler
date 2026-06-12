<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Organizations Mgt</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">{{ $organization->legal_name ?? $organization->name }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('organizations.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Organizations
                </a>
                <button class="btn btn-sm gap-1.5" style="background:#982B55;color:#fff;border-color:#982B55;" disabled>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Organization
                </button>
            </div>
        </div>
    </x-slot>

    <div class="min-h-full py-6 px-4 md:px-8">
        <div class="w-full">
            {{-- Hero Section --}}
            {{-- <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-8">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                        <div class="flex items-start gap-6">
                            <div class="w-20 h-20 bg-gradient-to-br from-primary via-secondary to-accent rounded-2xl flex items-center justify-center shadow-lg ring-4 ring-white">
                                <span class="text-3xl font-bold text-white">
                                    {{ substr($organization->name, 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $organization->name }}</h1>
                                @if ($organization->display_name && $organization->display_name !== $organization->legal_name)
                                    <p class="text-xl text-gray-600 mb-3">{{ $organization->display_name }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="badge badge-primary badge-lg shadow-sm">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                        </svg>
                                        {{ $organization->category_display }}
                                    </div>
                                    @if ($organization->is_active)
                                        <div class="badge badge-success badge-lg shadow-sm">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Active
                                        </div>
                                    @else
                                        <div class="badge badge-error badge-lg shadow-sm">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Inactive
                                        </div>
                                    @endif
                                    @if ($organization->is_verified)
                                        <div class="badge badge-info badge-lg shadow-sm">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Verified
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div> --}}

            {{-- Details Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {{-- Basic Information --}}
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#982B55;">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Basic Information</h3>
                        </div>

                        <div class="space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Legal Name</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->legal_name }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Organization
                                        Type</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->organization_type ?? 'Not specified' }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Registration
                                        Number</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->registration_number ?? 'Not provided' }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Date
                                        Established</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->date_established ? $organization->date_established->format('F j, Y') : 'Not provided' }}
                                    </p>
                                </div>
                                @if ($organization->tax_identification_number)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Tax ID
                                            Number</label>
                                        <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->tax_identification_number }}</p>
                                    </div>
                                @endif
                                @if ($organization->website_url)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Website</label>
                                        <a href="{{ $organization->website_url }}" target="_blank"
                                            class="text-primary hover:text-secondary font-medium transition-colors inline-flex items-center gap-1">
                                            {{ $organization->website_url }}
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            @if ($organization->description)
                                <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Description</label>
                                    <p class="text-gray-900 leading-relaxed">{{ $organization->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Address Information --}}
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Address & Location</h3>
                        </div>

                        <div class="space-y-5">
                            @if ($organization->address_line_1)
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Address</label>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-gray-900 font-medium leading-relaxed">
                                            {{ $organization->address_line_1 }}
                                            @if ($organization->address_line_2)
                                                <br>{{ $organization->address_line_2 }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                @if ($organization->city)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">City</label>
                                        <p
                                            class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->city }}</p>
                                    </div>
                                @endif
                                @if ($organization->district)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">District</label>
                                        <p
                                            class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->district }}</p>
                                    </div>
                                @endif
                                @if ($organization->country)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Country</label>
                                        <p
                                            class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->country }}</p>
                                    </div>
                                @endif
                                @if ($organization->postal_code)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Postal
                                            Code</label>
                                        <p
                                            class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->postal_code }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($organization->latitude && $organization->longitude)
                                <div
                                    class="p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-100">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">GPS
                                        Coordinates</label>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-gray-900 font-mono text-sm">{{ $organization->latitude }},
                                            {{ $organization->longitude }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Contact Information --}}
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Contact Information</h3>
                        </div>

                        <div class="space-y-6">
                            {{-- Primary Contact --}}
                            @if ($organization->primary_contact_name)
                                <div
                                    class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-5 border border-purple-100">
                                    <h4 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Primary Contact
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="group">
                                            <label class="block text-sm font-semibold text-gray-500 mb-1">Name</label>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->primary_contact_name }}</p>
                                        </div>
                                        @if ($organization->primary_contact_title)
                                            <div class="group">
                                                <label
                                                    class="block text-sm font-semibold text-gray-500 mb-1">Title</label>
                                                <p class="text-gray-900 font-medium">
                                                    {{ $organization->primary_contact_title }}</p>
                                            </div>
                                        @endif
                                        @if ($organization->primary_contact_email)
                                            <div class="group">
                                                <label
                                                    class="block text-sm font-semibold text-gray-500 mb-1">Email</label>
                                                <a href="mailto:{{ $organization->primary_contact_email }}"
                                                    class="text-primary hover:text-secondary font-medium transition-colors inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                        <path
                                                            d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                    </svg>
                                                    {{ $organization->primary_contact_email }}
                                                </a>
                                            </div>
                                        @endif
                                        @if ($organization->primary_contact_phone)
                                            <div class="group">
                                                <label
                                                    class="block text-sm font-semibold text-gray-500 mb-1">Phone</label>
                                                <a href="tel:{{ $organization->primary_contact_phone }}"
                                                    class="text-primary hover:text-secondary font-medium transition-colors inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                    </svg>
                                                    {{ $organization->primary_contact_phone }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- General Contact --}}
                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                                <h4 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2v.01h12V6H4zm0 2v6h12V8H4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    General Contact
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if ($organization->contact_email)
                                        <div class="group">
                                            <label class="block text-sm font-semibold text-gray-500 mb-1">Email</label>
                                            <a href="mailto:{{ $organization->contact_email }}"
                                                class="text-primary hover:text-secondary font-medium transition-colors inline-flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                </svg>
                                                {{ $organization->contact_email }}
                                            </a>
                                        </div>
                                    @endif
                                    @if ($organization->contact_phone)
                                        <div class="group">
                                            <label class="block text-sm font-semibold text-gray-500 mb-1">Phone</label>
                                            <a href="tel:{{ $organization->contact_phone }}"
                                                class="text-primary hover:text-secondary font-medium transition-colors inline-flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                    </svg>
                                                    {{ $organization->contact_phone }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status & Information --}}
                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#982B55;">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Status & Information</h3>
                        </div>

                        <div class="space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-3">Status</label>
                                    <div class="flex flex-wrap gap-2">
                                        @if ($organization->is_active)
                                            <div class="badge badge-success badge-lg shadow-sm">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Active
                                            </div>
                                        @else
                                            <div class="badge badge-error badge-lg shadow-sm">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Inactive
                                            </div>
                                        @endif
                                        @if ($organization->is_verified)
                                            <div class="badge badge-info badge-lg shadow-sm">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Verified
                                            </div>
                                        @endif
                                        @if ($organization->is_trial)
                                            <div class="badge badge-warning badge-lg shadow-sm">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Trial
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Category</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->category ?? 'Not specified' }}</p>
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Address</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->address_line_1 ?? 'Not provided' }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">City</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->city ?? 'Not provided' }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Country</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->country ?? 'Not provided' }}</p>
                                </div>
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Date Established</label>
                                    <p class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                        {{ $organization->date_established ? $organization->date_established->format('F j, Y') : 'Not provided' }}
                                    </p>
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Created</label>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->created_at->format('F j, Y g:i A') }}</p>
                                    </div>
                                </div>

                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-500 mb-2">Last Updated</label>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->updated_at->format('F j, Y g:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Regulatory Information --}}
                @if ($organization->regulatory_body || $organization->license_number)
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Regulatory Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                @if ($organization->regulatory_body)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Regulatory
                                            Body</label>
                                        <p
                                            class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                            {{ $organization->regulatory_body }}</p>
                                    </div>
                                @endif
                                @if ($organization->license_number)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">License
                                            Number</label>
                                        <p
                                            class="text-gray-900 font-medium font-mono tracking-wider group-hover:text-primary transition-colors">
                                            {{ $organization->license_number }}</p>
                                    </div>
                                @endif
                                @if ($organization->license_issue_date)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">License Issue
                                            Date</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->license_issue_date->format('F j, Y') }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($organization->license_expiry_date)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">License Expiry
                                            Date</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->license_expiry_date->format('F j, Y') }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($organization->accreditation_status)
                                    <div class="group md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Accreditation
                                            Status</label>
                                        <div
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-gray-900 font-medium">
                                                {{ $organization->accreditation_status }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- System Configuration --}}
                @if ($organization->default_currency || $organization->timezone)
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">System Configuration</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                @if ($organization->default_currency)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Default
                                            Currency</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p
                                                class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                                {{ $organization->default_currency }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($organization->timezone)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Timezone</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p
                                                class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                                {{ $organization->timezone }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($organization->default_language)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Default
                                            Language</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p
                                                class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                                {{ $organization->default_language }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($organization->bank_name)
                                    <div class="group">
                                        <label class="block text-sm font-semibold text-gray-500 mb-2">Primary
                                            Bank</label>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" />
                                            </svg>
                                            <p
                                                class="text-gray-900 font-medium group-hover:text-primary transition-colors">
                                                {{ $organization->bank_name }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ============ Structure & Components ============ --}}
                <div class="mt-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900">Structure &amp; Components</h2>
                    </div>

                    {{-- Hierarchy + membership summary --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Hierarchy</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-500 mb-1">Diocese / Parent</label>
                                    @if ($organization->parentOrganization)
                                        <a href="{{ route('organizations.show', $organization->parentOrganization->id) }}"
                                            class="text-gray-900 font-medium hover:underline" style="color:#982B55;">
                                            {{ $organization->parentOrganization->display_name ?? $organization->parentOrganization->legal_name }}
                                        </a>
                                    @else
                                        <p class="text-gray-500 italic">
                                            {{ $organization->is_super ? 'Top-level (head office)' : 'No parent diocese assigned' }}
                                        </p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-500 mb-1">
                                        Child Organizations ({{ $organization->childOrganizations->count() }})
                                    </label>
                                    @forelse ($organization->childOrganizations as $child)
                                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                                            <a href="{{ route('organizations.show', $child->id) }}"
                                                class="text-sm text-gray-800 hover:underline">
                                                {{ $child->display_name ?? $child->legal_name }}
                                            </a>
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ $child->category }}</span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">None</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Membership</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl bg-green-50 p-4">
                                    <div class="text-2xl font-bold text-green-700">{{ $membershipSummary['active'] ?? 0 }}</div>
                                    <div class="text-xs uppercase tracking-wide text-green-600">Active members</div>
                                </div>
                                <div class="rounded-xl bg-amber-50 p-4">
                                    <div class="text-2xl font-bold text-amber-700">{{ $membershipSummary['pending'] ?? 0 }}</div>
                                    <div class="text-xs uppercase tracking-wide text-amber-600">Pending applications</div>
                                </div>
                                <div class="rounded-xl bg-red-50 p-4">
                                    <div class="text-2xl font-bold text-red-700">{{ $membershipSummary['rejected'] ?? 0 }}</div>
                                    <div class="text-xs uppercase tracking-wide text-red-600">Rejected</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-4">
                                    <div class="text-2xl font-bold text-gray-700">{{ $membershipSummary['inactive'] ?? 0 }}</div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500">Inactive / past</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Departments --}}
                    <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900">Departments ({{ $departments->count() }})</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Department</th>
                                        <th class="px-4 py-3 text-left font-medium">Code</th>
                                        <th class="px-4 py-3 text-left font-medium">Units</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($departments as $department)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $department->name }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $department->code ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $department->units_count }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center py-8 text-gray-400">No departments.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Units --}}
                    <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900">Organization Units ({{ $units->count() }})</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Unit</th>
                                        <th class="px-4 py-3 text-left font-medium">Code</th>
                                        <th class="px-4 py-3 text-left font-medium">Department</th>
                                        <th class="px-4 py-3 text-left font-medium">Type</th>
                                        <th class="px-4 py-3 text-left font-medium">Active Members</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($units as $unit)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $unit->name }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $unit->code ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $unit->department->name ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $unit->unit_type ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $unit->active_members_count }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $unit->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $unit->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center py-8 text-gray-400">No units.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Sites --}}
                    @if ($organization->sites->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-xl font-bold text-gray-900">Sites ({{ $organization->sites->count() }})</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium">Site</th>
                                            <th class="px-4 py-3 text-left font-medium">City</th>
                                            <th class="px-4 py-3 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($organization->sites as $site)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-800">{{ $site->name ?? $site->site_name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ $site->city ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($site->is_active ?? true) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                        {{ ($site->is_active ?? true) ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
