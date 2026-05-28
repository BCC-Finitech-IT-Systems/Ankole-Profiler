<div class="min-h-full py-6 px-4 md:px-8">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Projects Mgt</div>
                <h1 class="text-base font-semibold text-gray-800 truncate flex items-center gap-2">
                    Register New Project
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-rose-600 bg-rose-50 border border-rose-200 rounded-full">
                        Step {{ $currentStep }}/{{ $totalSteps }}:
                        @switch($currentStep)
                            @case(1)
                                Projects Category{{ $category ? ' — ' . ($categories[$category]['label'] ?? 'Selected') : '' }}
                            @break
                            @case(2)
                                Basic Information
                            @break
                            @case(3)
                                Address Details
                            @break
                            @case(4)
                                Contact &amp; Regulatory
                            @break
                            @case(5)
                                Category-Specific Details{{ $category ? ' — ' . ($categories[$category]['label'] ?? 'Selected') : '' }}
                            @break
                            @case(6)
                                System Configuration
                            @break
                        @endswitch
                    </span>
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('organizations.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Projects
                </a>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Session messages --}}
        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        {{-- Import Organizations --}}
        <div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Import Projects</h3>
                    <p class="text-gray-600 text-sm">Upload an Excel or CSV file to import Projects in bulk. <a href="{{ route('organizations.template') }}" class="link link-primary underline ml-1" target="_blank">Download template</a></p>
                </div>
                <form wire:submit.prevent="importOrganizations" class="flex flex-col md:flex-row md:items-center gap-2">
                    <input type="file" wire:model="importFile" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="file-input file-input-bordered file-input-sm" />
                    @error('importFile')
                        <span class="text-red-600 text-xs">{{ $message }}</span>
                    @enderror
                    <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;" wire:loading.attr="disabled" wire:target="importFile,importOrganizations">
                        <span wire:loading.remove wire:target="importOrganizations">Import</span>
                        <span wire:loading wire:target="importOrganizations" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing...
                        </span>
                    </button>
                </form>
            </div>
        </div>
        <div>
            {{-- Tab Navigation --}}
            <div class="mb-4 flex items-center justify-between gap-4">
                <div class="flex-1">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex overflow-x-auto">
                        @for ($i = 1; $i <= $totalSteps; $i++)
                            <button type="button" wire:click="goToStep({{ $i }})"
                                class="flex-1 px-3 py-2 text-sm font-medium border-b-2 transition-colors duration-200 whitespace-nowrap {{ $currentStep === $i
                                    ? 'text-rose-600 border-rose-600 bg-rose-50'
                                    : ($i < $currentStep
                                        ? 'text-gray-600 border-gray-300 hover:text-gray-800 hover:border-gray-400'
                                        : 'text-gray-400 border-gray-200 cursor-not-allowed') }}"
                                {{ $i > $currentStep && !$this->canGoToStep($i) ? 'disabled' : '' }}>
                                <div class="flex items-center justify-center gap-2">
                                    <span
                                        class="w-5 h-5 rounded-full text-xs flex items-center justify-center {{ $currentStep === $i
                                            ? 'text-white'
                                            : ($i < $currentStep
                                                ? 'bg-green-100 text-green-600'
                                                : 'bg-gray-100 text-gray-400') }}"
                                        @if($currentStep === $i) style="background:#982B55;" @endif>
                                        @if ($i < $currentStep)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            {{ $i }}
                                        @endif
                                    </span>
                                    <span class="text-xs sm:text-sm">
                                        @switch($i)
                                            @case(1)
                                                Category
                                            @break

                                            @case(2)
                                                Basic Info
                                            @break

                                            @case(3)
                                                Address
                                            @break

                                            @case(4)
                                                Contacts
                                            @break

                                            @case(5)
                                                Details
                                            @break

                                            @case(6)
                                                Config
                                            @break
                                        @endswitch
                                    </span>
                                </div>
                            </button>
                        @endfor
                    </div>
                </div>
                </div>
                <div class="flex-shrink-0">
                    <x-fill-sample-data-btn />
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <form wire:submit="submit">
                    <div class="p-6">
                        {{-- Step 1: Organization Category Selection --}}
                        @if ($currentStep === 1)
                            <div class="space-y-4">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">Select Project Category</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Choose the type of project you're registering. This will determine the specific fields required for your organization.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($categories as $value => $categoryData)
                                        <label class="cursor-pointer" wire:key="category-{{ $value }}">
                                            <input type="radio" wire:model.live="category"
                                                value="{{ $value }}" class="sr-only">
                                            <div
                                                class="border-2 rounded-lg p-3 transition-all duration-200 hover:border-rose-300 relative {{ $category === $value ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-200 shadow-md' : 'border-gray-200 hover:bg-gray-50 hover:shadow-md' }}">

                                                {{-- Loading Spinner (show only when this specific category is being processed) --}}
                                                <div wire:loading.delay wire:target="category"
                                                     x-show="$wire.category === '{{ $value }}' && $wire.__instance.fingerprint.loading.includes('category')"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     class="absolute inset-0 bg-white/90 rounded-lg flex items-center justify-center z-10">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <svg class="animate-spin h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span class="text-xs text-rose-600 font-medium">Selecting...</span>
                                                    </div>
                                                </div>

                                                {{-- Selection indicator --}}
                                                @if ($category === $value)
                                                    <div class="absolute top-2 right-2">
                                                        <div
                                                            class="w-5 h-5 rounded-full flex items-center justify-center shadow-sm" style="background:#982B55;">
                                                            <svg class="w-3 h-3 text-white" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="flex items-start gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div
                                                            class="w-8 h-8 rounded-lg {{ $category === $value ? 'bg-rose-200 shadow-md border border-rose-300' : 'bg-rose-50' }} flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-rose-600"
                                                                fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="{{ $categoryData['icon'] }}" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h4
                                                            class="font-semibold text-sm {{ $category === $value ? 'text-rose-600' : 'text-gray-900' }}">
                                                            {{ $categoryData['label'] }}</h4>
                                                        <p
                                                            class="text-xs {{ $category === $value ? 'text-rose-500' : 'text-gray-500' }} mt-1">
                                                            {{ $categoryData['description'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @error('category')
                                    <div class="text-red-600 text-sm mt-2">{{ $message }}</div>
                                @enderror

                                {{-- Selected Category Confirmation --}}
                                @if ($category)
                                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-green-100 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-green-800 font-medium">
                                                    {{ $categories[$category]['label'] }} Selected</p>
                                                <p class="text-green-600 text-sm">
                                                    {{ $categories[$category]['description'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step 2: Basic Information --}}
                        @if ($currentStep === 2)
                            <div class="space-y-6">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">Basic Information</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Provide the fundamental details about your organization.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Legal Name <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model.blur="legal_name" class="input input-bordered input-sm w-full"
                                            placeholder="Enter the official legal name">
                                        @error('legal_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Display Name</span>
                                        </label>
                                        <input type="text" wire:model="display_name" class="input input-bordered input-sm w-full"
                                            placeholder="Common name or trading name">
                                        @error('display_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Project Code <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="code" class="input input-bordered input-sm w-full"
                                            placeholder="Unique organization identifier">
                                        @error('code')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Project Type</span>
                                        </label>
                                        <select wire:model="organization_type" class="select select-bordered select-sm w-full">
                                            <option value="STANDALONE">Standalone Organization</option>
                                            <option value="HOLDING">Holding Company</option>
                                            <option value="SUBSIDIARY">Subsidiary</option>
                                        </select>
                                        @error('organization_type')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Registration Number <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="registration_number"
                                            class="input input-bordered input-sm w-full" placeholder="Government registration number">
                                        @error('registration_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Tax Identification Number</span>
                                        </label>
                                        <input type="text" wire:model="tax_identification_number"
                                            class="input input-bordered input-sm w-full" placeholder="TIN number">
                                        @error('tax_identification_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Date Established <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="date" wire:model="date_established"
                                            class="input input-bordered input-sm w-full">
                                        @error('date_established')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email <span class="text-red-500">*</span></label>
                                        <input type="email" wire:model="contact_email" class="input input-bordered input-sm w-full"
                                            placeholder="info@organization.com">
                                        @error('contact_email')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone <span class="text-red-500">*</span></label>
                                        <input type="tel" wire:model="contact_phone" class="input input-bordered input-sm w-full"
                                            placeholder="+256123456789">
                                        @error('contact_phone')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                                        <input type="url" wire:model="website_url" class="input input-bordered input-sm w-full"
                                            placeholder="https://example.com">
                                        @error('website_url')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea wire:model="description" class="textarea textarea-bordered textarea-sm w-full"
                                        rows="3" placeholder="Brief description of the organization"></textarea>
                                    @error('description')
                                        <span class="text-red-600 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Step 3: Address Information --}}
                        @if ($currentStep === 3)
                            <div class="space-y-6">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">Address Information</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Provide the primary address and location details.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-3">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Address Line 1 <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="address_line_1"
                                            class="input input-bordered input-sm w-full" placeholder="Street address, building name">
                                        @error('address_line_1')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Address Line 2</span>
                                        </label>
                                        <input type="text" wire:model="address_line_2"
                                            class="input input-bordered input-sm w-full" placeholder="Apartment, suite, floor">
                                        @error('address_line_2')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">City <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="city" class="input input-bordered input-sm w-full"
                                            placeholder="City or town">
                                        @error('city')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">District</span>
                                        </label>
                                        <input type="text" wire:model="district" class="input input-bordered input-sm w-full"
                                            placeholder="District or region">
                                        @error('district')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Postal Code</span>
                                        </label>
                                        <input type="text" wire:model="postal_code" class="input input-bordered input-sm w-full"
                                            placeholder="Postal or ZIP code">
                                        @error('postal_code')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Country</span>
                                        </label>
                                        <select wire:model="country" class="select select-bordered select-sm w-full">
                                            <option value="UGA">Uganda</option>
                                            <option value="KEN">Kenya</option>
                                            <option value="TZA">Tanzania</option>
                                            <option value="RWA">Rwanda</option>
                                            <option value="USA">United States</option>
                                            <option value="GBR">United Kingdom</option>
                                        </select>
                                        @error('country')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Latitude</span>
                                        </label>
                                        <input type="number" step="any" wire:model="latitude"
                                            class="input input-bordered input-sm w-full" placeholder="GPS latitude">
                                        @error('latitude')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Longitude</span>
                                        </label>
                                        <input type="number" step="any" wire:model="longitude"
                                            class="input input-bordered input-sm w-full" placeholder="GPS longitude">
                                        @error('longitude')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Step 4: Contact & Regulatory Information --}}
                        @if ($currentStep === 4)
                            <div class="space-y-6">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">Contact Persons & Regulatory Information</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Provide contact persons and regulatory compliance details.</p>
                                </div>

                                {{-- Primary Contact --}}
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <h4 class="font-medium text-gray-900 text-sm">Primary Contact Person</h4>
                                        @if($admin_assignment_type === 'primary')
                                            <div class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white" style="background:#982B55;">Admin Contact</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 {{ $admin_assignment_type === 'primary' ? 'ring-2 ring-rose-200 p-3 rounded-lg bg-rose-50/30' : '' }}">
                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Full Name <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="text" wire:model="primary_contact_name"
                                                class="input input-bordered input-sm w-full" placeholder="Contact person name">
                                            @error('primary_contact_name')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Title/Position</span>
                                            </label>
                                            <input type="text" wire:model="primary_contact_title"
                                                class="input input-bordered input-sm w-full" placeholder="Job title or position">
                                            @error('primary_contact_title')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Email <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="email" wire:model="primary_contact_email"
                                                class="input input-bordered input-sm w-full" placeholder="contact@organization.com">
                                            @error('primary_contact_email')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Phone <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="tel" wire:model="primary_contact_phone"
                                                class="input input-bordered input-sm w-full" placeholder="+256123456789">
                                            @error('primary_contact_phone')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    @if($admin_assignment_type === 'primary')
                                        <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-rose-700 text-xs font-medium">This person will be assigned as the system administrator.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Secondary Contact --}}
                                <div class="py-2">
                                    <div class="flex items-center gap-2 mb-3">
                                        <h4 class="font-medium text-gray-900 text-sm">
                                            Secondary Contact Person
                                            @if($admin_assignment_type === 'secondary')
                                                <span class="text-red-500 text-xs">(Required for Admin)</span>
                                            @else
                                                <span class="text-gray-500 text-xs">(Optional)</span>
                                            @endif
                                        </h4>
                                        @if($admin_assignment_type === 'secondary')
                                            <div class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white" style="background:#982B55;">Admin Contact</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 {{ $admin_assignment_type === 'secondary' ? 'ring-2 ring-rose-200 p-3 rounded-lg bg-rose-50/30' : '' }}">
                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">
                                                    Full Name
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="text" wire:model.blur="secondary_contact_name"
                                                class="input input-bordered input-sm w-full" placeholder="Secondary contact name">
                                            @error('secondary_contact_name')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">
                                                    Email
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="email" wire:model.blur="secondary_contact_email"
                                                class="input input-bordered input-sm w-full" placeholder="secondary@organization.com">
                                            @error('secondary_contact_email')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">
                                                    Phone
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="tel" wire:model.blur="secondary_contact_phone"
                                                class="input input-bordered input-sm w-full" placeholder="+256987654321">
                                            @error('secondary_contact_phone')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    @if($admin_assignment_type === 'secondary')
                                        <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-rose-700 text-xs font-medium">This person will be assigned as the system administrator.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="py-2">
                                    <div class="bg-rose-50 border-2 border-rose-300 rounded-lg p-3">
                                        <h3 class="font-semibold text-base mb-2">System Administrator Assignment</h3>
                                        <p class="text-sm text-gray-700 mb-3">Choose how to assign the organization
                                            admin:</p>

                                        <div class="space-y-2">
                                            <label
                                                class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer hover:bg-white transition-colors"
                                                :class="$wire.admin_assignment_type === 'primary' ?
                                                    'border-rose-500 bg-white' : 'border-gray-200'">
                                                <input type="radio" wire:model="admin_assignment_type"
                                                    value="primary" class="mt-1">
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">Use Primary Contact</div>
                                                    <div class="text-xs text-gray-600">
                                                        {{ $primary_contact_name ?: 'Primary contact' }} will be the
                                                        admin</div>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer hover:bg-white transition-colors"
                                                :class="$wire.admin_assignment_type === 'secondary' ?
                                                    'border-rose-500 bg-white' : 'border-gray-200'">
                                                <input type="radio" wire:model="admin_assignment_type"
                                                    value="secondary" class="mt-1">
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">Use Secondary Contact</div>
                                                    <div class="text-xs text-gray-600">
                                                        {{ $secondary_contact_name ?: 'Secondary contact' }} will be
                                                        the admin</div>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer hover:bg-white transition-colors"
                                                :class="$wire.admin_assignment_type === 'custom' ?
                                                    'border-rose-500 bg-white' : 'border-gray-200'">
                                                <input type="radio" wire:model="admin_assignment_type"
                                                    value="custom" class="mt-1">
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">Different Person</div>
                                                    <div class="text-xs text-gray-600">Specify a different person for
                                                        admin access</div>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer hover:bg-white transition-colors"
                                                :class="$wire.admin_assignment_type === 'defer' ?
                                                    'border-rose-500 bg-white' : 'border-gray-200'">
                                                <input type="radio" wire:model="admin_assignment_type"
                                                    value="defer" class="mt-1">
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">Assign Later</div>
                                                    <div class="text-xs text-gray-600">Create organization without
                                                        admin, assign later</div>
                                                </div>
                                            </label>

                                        </div>

                                        {{-- Admin Assignment Preview --}}
                                        @if ($admin_assignment_type !== 'defer')
                                            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <div>
                                                        <p class="text-green-800 font-medium text-sm">Administrator Selected</p>
                                                        <p class="text-green-600 text-xs">{{ $this->adminPreview }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Custom Admin Fields (show when 'custom' selected) --}}
                                        @if ($admin_assignment_type === 'custom')
                                            <div class="mt-4 p-4 bg-white rounded-lg border-2 border-rose-300 shadow-sm">
                                                <div class="flex items-center gap-2 mb-3">
                                                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <h4 class="font-semibold text-gray-900">System Administrator Details</h4>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-4">Please provide the details of the person who will be the system administrator for this organization.</p>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="md:col-span-2">
                                                        <label class="mb-1">
                                                            <span class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="text" wire:model.blur="custom_admin_name"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="Enter administrator's full name">
                                                        @error('custom_admin_name')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label class="mb-1">
                                                            <span class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="email" wire:model.blur="custom_admin_email"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="admin@organization.com">
                                                        @error('custom_admin_email')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label class="mb-1">
                                                            <span class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="tel" wire:model.blur="custom_admin_phone"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="+256123456789">
                                                        @error('custom_admin_phone')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="mb-1">
                                                            <span class="block text-sm font-medium text-gray-700">Job Title/Position</span>
                                                        </label>
                                                        <input type="text" wire:model="custom_admin_title"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="IT Manager, System Administrator, etc.">
                                                        @error('custom_admin_title')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                {{-- Admin Privileges Info --}}
                                                <div class="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                                                    <div class="flex items-start gap-2">
                                                        <svg class="w-4 h-4 text-rose-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div class="text-xs text-rose-700">
                                                            <p class="font-medium mb-1">System Administrator Privileges:</p>
                                                            <ul class="list-disc list-inside space-y-0.5 text-rose-600">
                                                                <li>Full system configuration access</li>
                                                                <li>User management and role assignment</li>
                                                                <li>Organization settings modification</li>
                                                                <li>System monitoring and reporting</li>
                                                                <li>Data backup and security management</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Security Notice --}}
                                                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                                    <div class="flex items-start gap-2">
                                                        <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L4.18 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                        <div class="text-xs text-amber-700">
                                                            <p class="font-medium mb-1">Security Notice:</p>
                                                            <p>A temporary password will be generated and displayed after organization creation. Please share it securely with the administrator and ensure they change it on first login.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                {{-- Regulatory Information --}}
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-3 text-sm">Regulatory & Compliance</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Regulatory Body</span>
                                            </label>
                                            <input type="text" wire:model="regulatory_body"
                                                class="input input-bordered input-sm w-full"
                                                placeholder="Government regulatory authority">
                                            @error('regulatory_body')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">License Number</span>
                                            </label>
                                            <input type="text" wire:model="license_number"
                                                class="input input-bordered input-sm w-full"
                                                placeholder="Professional/operating license number">
                                            @error('license_number')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">License Issue Date</span>
                                            </label>
                                            <input type="date" wire:model="license_issue_date"
                                                class="input input-bordered input-sm w-full">
                                            @error('license_issue_date')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="space-y-1">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">License Expiry Date</span>
                                            </label>
                                            <input type="date" wire:model="license_expiry_date"
                                                class="input input-bordered input-sm w-full">
                                            @error('license_expiry_date')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-1">
                                                <span class="block text-sm font-medium text-gray-700">Accreditation Status</span>
                                            </label>
                                            <select wire:model="accreditation_status" class="select select-bordered select-sm w-full">
                                                <option value="NOT_APPLICABLE">Not Applicable</option>
                                                <option value="PENDING">Pending</option>
                                                <option value="ACCREDITED">Accredited</option>
                                                <option value="EXPIRED">Expired</option>
                                            </select>
                                            @error('accreditation_status')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Step 5: Category-Specific Details --}}
                        @if ($currentStep === 5)
                            <div class="space-y-6">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $categories[$category]['label'] ?? 'Category' }}-Specific Details</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Provide details specific to your <span class="font-medium text-rose-600">{{ $categories[$category]['label'] ?? 'organization' }}</span> type.</p>
                                </div>

                                @if ($category)
                                    @include('livewire.organizations.partials.category-' . strtolower($category))
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-gray-500">Please select an organization category first.</p>
                                        <button type="button" wire:click="$set('currentStep', 1)"
                                            class="btn border-0 text-white mt-4" style="background:#982B55;">
                                            Go Back to Category Selection
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step 6: System Configuration --}}
                        @if ($currentStep === 6)
                            <div class="space-y-6">
                                <div class="border-b border-gray-100 pb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">System Configuration</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Configure system settings and financial information.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Primary Bank Name</span>
                                        </label>
                                        <input type="text" wire:model="bank_name" class="input input-bordered input-sm w-full"
                                            placeholder="Bank name">
                                        @error('bank_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Bank Account Number</span>
                                        </label>
                                        <input type="text" wire:model="bank_account_number"
                                            class="input input-bordered input-sm w-full" placeholder="Account number">
                                        @error('bank_account_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Bank Branch</span>
                                        </label>
                                        <input type="text" wire:model="bank_branch" class="input input-bordered input-sm w-full"
                                            placeholder="Branch name">
                                        @error('bank_branch')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Default Currency</span>
                                        </label>
                                        <select wire:model="default_currency" class="select select-bordered select-sm w-full">
                                            <option value="UGX">Uganda Shilling (UGX)</option>
                                            <option value="USD">US Dollar (USD)</option>
                                            <option value="EUR">Euro (EUR)</option>
                                            <option value="GBP">British Pound (GBP)</option>
                                            <option value="KES">Kenya Shilling (KES)</option>
                                        </select>
                                        @error('default_currency')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Timezone</span>
                                        </label>
                                        <select wire:model="timezone" class="select select-bordered select-sm w-full">
                                            <option value="Africa/Kampala">Africa/Kampala</option>
                                            <option value="Africa/Nairobi">Africa/Nairobi</option>
                                            <option value="Africa/Dar_es_Salaam">Africa/Dar_es_Salaam</option>
                                            <option value="UTC">UTC</option>
                                        </select>
                                        @error('timezone')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="space-y-1">
                                        <label class="mb-1">
                                            <span class="block text-sm font-medium text-gray-700">Default Language</span>
                                        </label>
                                        <select wire:model="default_language" class="select select-bordered select-sm w-full">
                                            <option value="en">English</option>
                                            <option value="sw">Swahili</option>
                                            <option value="lg">Luganda</option>
                                            <option value="fr">French</option>
                                        </select>
                                        @error('default_language')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Navigation Buttons --}}
                    <div class="px-4 py-3 bg-gray-50 border-t flex justify-between">
                        <div>
                            @if ($currentStep > 1)
                                <button type="button" wire:click="previousStep" class="btn btn-ghost" wire:loading.attr="disabled" wire:target="previousStep">
                                    <span wire:loading.remove wire:target="previousStep" class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        Previous
                                    </span>
                                    <span wire:loading wire:target="previousStep" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Going Back...
                                    </span>
                                </button>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            @if ($currentStep < $totalSteps)
                                <button type="button" wire:click="nextStep" class="btn border-0 text-white" style="background:#982B55;" wire:loading.attr="disabled" wire:target="nextStep">
                                    <span wire:loading.remove wire:target="nextStep">Next</span>
                                    <span wire:loading wire:target="nextStep" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                    <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4 ml-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            @else
                                <button type="submit" class="btn border-0 text-white" style="background:#982B55;">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Create Organization
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
