<div>
    <x-slot name="header">
        <div
            class="flex items-center justify-between {{ $category ? 'bg-gradient-to-r from-primary/5 to-transparent p-4 rounded-lg border border-primary/10' : '' }}">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Register New Project') }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">Step {{ $currentStep }} of {{ $totalSteps }}:
                    @switch($currentStep)
                        @case(1)
                            Projects Category
                            @if ($category)
                                <span
                                    class="inline-flex items-center px-2 py-1 ml-2 text-xs font-medium text-primary bg-primary/10 border border-primary/20 rounded-full">
                                    {{ $categories[$category]['label'] ?? 'Selected' }}</span>
                            @endif
                        @break

                        @case(2)
                            Basic Information
                        @break

                        @case(3)
                            Address Details
                        @break

                        @case(4)
                            Contact & Regulatory
                        @break

                        @case(5)
                            Category-Specific Details
                            @if ($category)
                                <span
                                    class="inline-flex items-center px-2 py-1 ml-2 text-xs font-medium text-primary bg-primary/10 border border-primary/20 rounded-full">
                                    {{ $categories[$category]['label'] ?? 'Selected' }}</span>
                            @endif
                        @break

                        @case(6)
                            System Configuration
                        @break

                    @endswitch
                </p>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('organizations.index') }}" class="btn btn-ghost">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Projects
                </a>
                <a href="{{ route('organizations.template') }}" class="btn btn-outline btn-primary" target="_blank">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Export Template
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        {{-- Import Organizations --}}
        <div class="max-w-6xl sm:px-6 lg:px-8 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Import Projects</h3>
                    <p class="text-gray-600 text-sm">Upload an Excel or CSV file to import Projects in bulk. <a href="{{ route('organizations.template') }}" class="link link-primary underline ml-1" target="_blank">Download template</a></p>
                </div>
                <form wire:submit.prevent="importOrganizations" class="flex flex-col md:flex-row md:items-center gap-2">
                    <input type="file" wire:model="importFile" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="file-input file-input-bordered file-input-sm" />
                    @error('importFile')
                        <span class="text-red-600 text-xs">{{ $message }}</span>
                    @enderror
                    <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="importFile,importOrganizations">
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
        <div class="max-w-6xl sm:px-6 lg:px-8">
            {{-- Tab Navigation --}}
            <div class="mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="flex overflow-x-auto">
                        @for ($i = 1; $i <= $totalSteps; $i++)
                            <button type="button" wire:click="goToStep({{ $i }})"
                                class="flex-1 px-3 py-2 text-sm font-medium border-b-2 transition-colors duration-200 whitespace-nowrap {{ $currentStep === $i
                                    ? 'text-primary border-primary bg-primary/5'
                                    : ($i < $currentStep
                                        ? 'text-gray-600 border-gray-300 hover:text-gray-800 hover:border-gray-400'
                                        : 'text-gray-400 border-gray-200 cursor-not-allowed') }}"
                                {{ $i > $currentStep && !$this->canGoToStep($i) ? 'disabled' : '' }}>
                                <div class="flex items-center justify-center gap-2">
                                    <span
                                        class="w-5 h-5 rounded-full text-xs flex items-center justify-center {{ $currentStep === $i
                                            ? 'bg-primary text-white'
                                            : ($i < $currentStep
                                                ? 'bg-green-100 text-green-600'
                                                : 'bg-gray-100 text-gray-400') }}">
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

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <form wire:submit="submit">
                    <div class="p-4">
                        {{-- Step 1: Organization Category Selection --}}
                        @if ($currentStep === 1)
                            <div class="space-y-3">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Select Project Category</h3>
                                    <p class="text-gray-600 mb-4">Choose the type of project you're registering.
                                        This will determine the specific fields required for your organization.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($categories as $value => $categoryData)
                                        <label class="cursor-pointer" wire:key="category-{{ $value }}">
                                            <input type="radio" wire:model.live="category"
                                                value="{{ $value }}" class="sr-only">
                                            <div
                                                class="border-2 rounded-lg p-3 transition-all duration-200 hover:border-primary/50 relative {{ $category === $value ? 'border-primary bg-gradient-to-br from-blue-50 via-primary/20 to-primary/10 ring-2 ring-primary/30 shadow-lg shadow-primary/20' : 'border-gray-200 hover:bg-gray-50 hover:shadow-md' }}">

                                                {{-- Loading Spinner (show only when this specific category is being processed) --}}
                                                <div wire:loading.delay wire:target="category"
                                                     x-show="$wire.category === '{{ $value }}' && $wire.__instance.fingerprint.loading.includes('category')"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     class="absolute inset-0 bg-white/90 rounded-lg flex items-center justify-center z-10">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <svg class="animate-spin h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span class="text-xs text-primary font-medium">Selecting...</span>
                                                    </div>
                                                </div>

                                                {{-- Selection indicator --}}
                                                @if ($category === $value)
                                                    <div class="absolute top-2 right-2">
                                                        <div
                                                            class="w-5 h-5 bg-primary rounded-full flex items-center justify-center shadow-sm">
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
                                                            class="w-8 h-8 rounded-lg {{ $category === $value ? 'bg-primary/40 shadow-md border border-primary/20' : 'bg-primary/10' }} flex items-center justify-center">
                                                            <svg class="w-4 h-4 {{ $category === $value ? 'text-primary' : 'text-primary' }}"
                                                                fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="{{ $categoryData['icon'] }}" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h4
                                                            class="font-semibold text-sm {{ $category === $value ? 'text-primary' : 'text-gray-900' }}">
                                                            {{ $categoryData['label'] }}</h4>
                                                        <p
                                                            class="text-xs {{ $category === $value ? 'text-primary/80' : 'text-gray-500' }} mt-1">
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
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Basic Information</h3>
                                    <p class="text-gray-600 mb-4">Provide the fundamental details about your
                                        organization.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Legal Name <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model.blur="legal_name" class="input input-bordered input-sm"
                                            placeholder="Enter the official legal name">
                                        @error('legal_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Display Name</span>
                                        </label>
                                        <input type="text" wire:model="display_name" class="input input-bordered input-sm"
                                            placeholder="Common name or trading name">
                                        @error('display_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Project Code <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="code" class="input input-bordered input-sm"
                                            placeholder="Unique organization identifier">
                                        @error('code')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Project Type</span>
                                        </label>
                                        <select wire:model="organization_type" class="select select-bordered select-sm">
                                            <option value="STANDALONE">Standalone Organization</option>
                                            <option value="HOLDING">Holding Company</option>
                                            <option value="SUBSIDIARY">Subsidiary</option>
                                        </select>
                                        @error('organization_type')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Registration Number <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="registration_number"
                                            class="input input-bordered input-sm" placeholder="Government registration number">
                                        @error('registration_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Tax Identification Number</span>
                                        </label>
                                        <input type="text" wire:model="tax_identification_number"
                                            class="input input-bordered input-sm" placeholder="TIN number">
                                        @error('tax_identification_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Date Established <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="date" wire:model="date_established"
                                            class="input input-bordered input-sm">
                                        @error('date_established')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Website URL</span>
                                        </label>
                                        <input type="url" wire:model="website_url" class="input input-bordered input-sm"
                                            placeholder="https://example.com">
                                        @error('website_url')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Contact Email <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="email" wire:model="contact_email" class="input input-bordered input-sm"
                                            placeholder="info@organization.com">
                                        @error('contact_email')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Contact Phone <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="tel" wire:model="contact_phone" class="input input-bordered input-sm"
                                            placeholder="+256123456789">
                                        @error('contact_phone')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label py-1">
                                        <span class="label-text font-medium text-sm">Description</span>
                                    </label>
                                    <textarea wire:model="description" class="textarea textarea-bordered textarea-sm h-16"
                                        placeholder="Brief description of the organization"></textarea>
                                    @error('description')
                                        <span class="text-red-600 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Step 3: Address Information --}}
                        @if ($currentStep === 3)
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Address Information</h3>
                                    <p class="text-gray-600 mb-4">Provide the primary address and location details.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="form-control md:col-span-3">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Address Line 1 <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="address_line_1"
                                            class="input input-bordered input-sm" placeholder="Street address, building name">
                                        @error('address_line_1')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control md:col-span-3">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Address Line 2</span>
                                        </label>
                                        <input type="text" wire:model="address_line_2"
                                            class="input input-bordered input-sm" placeholder="Apartment, suite, floor">
                                        @error('address_line_2')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">City <span
                                                    class="text-red-500">*</span></span>
                                        </label>
                                        <input type="text" wire:model="city" class="input input-bordered input-sm"
                                            placeholder="City or town">
                                        @error('city')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">District</span>
                                        </label>
                                        <input type="text" wire:model="district" class="input input-bordered input-sm"
                                            placeholder="District or region">
                                        @error('district')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Postal Code</span>
                                        </label>
                                        <input type="text" wire:model="postal_code" class="input input-bordered input-sm"
                                            placeholder="Postal or ZIP code">
                                        @error('postal_code')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Country</span>
                                        </label>
                                        <select wire:model="country" class="select select-bordered select-sm">
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

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Latitude</span>
                                        </label>
                                        <input type="number" step="any" wire:model="latitude"
                                            class="input input-bordered input-sm" placeholder="GPS latitude">
                                        @error('latitude')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Longitude</span>
                                        </label>
                                        <input type="number" step="any" wire:model="longitude"
                                            class="input input-bordered input-sm" placeholder="GPS longitude">
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
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Contact Persons & Regulatory
                                        Information</h3>
                                    <p class="text-gray-600 mb-4">Provide contact persons and regulatory compliance
                                        details.</p>
                                </div>

                                {{-- Primary Contact --}}
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <h4 class="font-medium text-gray-900 text-sm">Primary Contact Person</h4>
                                        @if($admin_assignment_type === 'primary')
                                            <div class="badge badge-primary badge-sm">Admin Contact</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 {{ $admin_assignment_type === 'primary' ? 'ring-2 ring-indigo-200 p-3 rounded-lg bg-indigo-50/30' : '' }}">
                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Full Name <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="text" wire:model="primary_contact_name"
                                                class="input input-bordered input-sm" placeholder="Contact person name">
                                            @error('primary_contact_name')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Title/Position</span>
                                            </label>
                                            <input type="text" wire:model="primary_contact_title"
                                                class="input input-bordered input-sm" placeholder="Job title or position">
                                            @error('primary_contact_title')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Email <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="email" wire:model="primary_contact_email"
                                                class="input input-bordered input-sm" placeholder="contact@organization.com">
                                            @error('primary_contact_email')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Phone <span
                                                        class="text-red-500">*</span></span>
                                            </label>
                                            <input type="tel" wire:model="primary_contact_phone"
                                                class="input input-bordered input-sm" placeholder="+256123456789">
                                            @error('primary_contact_phone')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    @if($admin_assignment_type === 'primary')
                                        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-blue-700 text-xs font-medium">This person will be assigned as the system administrator.</span>
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
                                            <div class="badge badge-primary badge-sm">Admin Contact</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 {{ $admin_assignment_type === 'secondary' ? 'ring-2 ring-indigo-200 p-3 rounded-lg bg-indigo-50/30' : '' }}">
                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">
                                                    Full Name
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="text" wire:model.blur="secondary_contact_name"
                                                class="input input-bordered input-sm" placeholder="Secondary contact name">
                                            @error('secondary_contact_name')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">
                                                    Email
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="email" wire:model.blur="secondary_contact_email"
                                                class="input input-bordered input-sm" placeholder="secondary@organization.com">
                                            @error('secondary_contact_email')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">
                                                    Phone
                                                    @if($admin_assignment_type === 'secondary')
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </span>
                                            </label>
                                            <input type="tel" wire:model.blur="secondary_contact_phone"
                                                class="input input-bordered input-sm" placeholder="+256987654321">
                                            @error('secondary_contact_phone')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    @if($admin_assignment_type === 'secondary')
                                        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-blue-700 text-xs font-medium">This person will be assigned as the system administrator.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="py-2">
                                    <div class="bg-indigo-50 border-2 border-indigo-300 rounded-lg p-3">
                                        <h3 class="font-semibold text-base mb-2">System Administrator Assignment</h3>
                                        <p class="text-sm text-gray-700 mb-3">Choose how to assign the organization
                                            admin:</p>

                                        <div class="space-y-2">
                                            <label
                                                class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer hover:bg-white transition-colors"
                                                :class="$wire.admin_assignment_type === 'primary' ?
                                                    'border-indigo-500 bg-white' : 'border-gray-200'">
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
                                                    'border-indigo-500 bg-white' : 'border-gray-200'">
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
                                                    'border-indigo-500 bg-white' : 'border-gray-200'">
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
                                                    'border-indigo-500 bg-white' : 'border-gray-200'">
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
                                            <div class="mt-4 p-4 bg-white rounded-lg border-2 border-indigo-300 shadow-sm">
                                                <div class="flex items-center gap-2 mb-3">
                                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <h4 class="font-semibold text-indigo-900">System Administrator Details</h4>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-4">Please provide the details of the person who will be the system administrator for this organization.</p>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="md:col-span-2">
                                                        <label class="label py-1">
                                                            <span class="label-text font-medium text-sm">Full Name <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="text" wire:model.blur="custom_admin_name"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="Enter administrator's full name">
                                                        @error('custom_admin_name')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label class="label py-1">
                                                            <span class="label-text font-medium text-sm">Email Address <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="email" wire:model.blur="custom_admin_email"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="admin@organization.com">
                                                        @error('custom_admin_email')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label class="label py-1">
                                                            <span class="label-text font-medium text-sm">Phone Number <span class="text-red-500">*</span></span>
                                                        </label>
                                                        <input type="tel" wire:model.blur="custom_admin_phone"
                                                            class="input input-bordered input-sm w-full"
                                                            placeholder="+256123456789">
                                                        @error('custom_admin_phone')
                                                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="label py-1">
                                                            <span class="label-text font-medium text-sm">Job Title/Position</span>
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
                                                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                    <div class="flex items-start gap-2">
                                                        <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div class="text-xs text-blue-700">
                                                            <p class="font-medium mb-1">System Administrator Privileges:</p>
                                                            <ul class="list-disc list-inside space-y-0.5 text-blue-600">
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
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Regulatory Body</span>
                                            </label>
                                            <input type="text" wire:model="regulatory_body"
                                                class="input input-bordered input-sm"
                                                placeholder="Government regulatory authority">
                                            @error('regulatory_body')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">License Number</span>
                                            </label>
                                            <input type="text" wire:model="license_number"
                                                class="input input-bordered input-sm"
                                                placeholder="Professional/operating license number">
                                            @error('license_number')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">License Issue Date</span>
                                            </label>
                                            <input type="date" wire:model="license_issue_date"
                                                class="input input-bordered input-sm">
                                            @error('license_issue_date')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">License Expiry Date</span>
                                            </label>
                                            <input type="date" wire:model="license_expiry_date"
                                                class="input input-bordered input-sm">
                                            @error('license_expiry_date')
                                                <span class="text-red-600 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-control md:col-span-2">
                                            <label class="label py-1">
                                                <span class="label-text font-medium text-sm">Accreditation Status</span>
                                            </label>
                                            <select wire:model="accreditation_status" class="select select-bordered select-sm">
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
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="{{ $categories[$category]['icon'] ?? 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5' }}" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ $categories[$category]['label'] ?? 'Category' }}-Specific Details
                                        </h3>
                                    </div>
                                    <p class="text-gray-600 mb-4">Provide details specific to your <span
                                            class="font-medium text-primary">{{ $categories[$category]['label'] ?? 'organization' }}</span>
                                        type.</p>
                                </div>

                                @if ($category)
                                    @include('livewire.organizations.partials.category-' . $category)
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-gray-500">Please select an organization category first.</p>
                                        <button type="button" wire:click="$set('currentStep', 1)"
                                            class="btn btn-primary mt-4">
                                            Go Back to Category Selection
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step 6: System Configuration --}}
                        @if ($currentStep === 6)
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">System Configuration</h3>
                                    <p class="text-gray-600 mb-4">Configure system settings and financial information.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Primary Bank Name</span>
                                        </label>
                                        <input type="text" wire:model="bank_name" class="input input-bordered input-sm"
                                            placeholder="Bank name">
                                        @error('bank_name')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Bank Account Number</span>
                                        </label>
                                        <input type="text" wire:model="bank_account_number"
                                            class="input input-bordered input-sm" placeholder="Account number">
                                        @error('bank_account_number')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Bank Branch</span>
                                        </label>
                                        <input type="text" wire:model="bank_branch" class="input input-bordered input-sm"
                                            placeholder="Branch name">
                                        @error('bank_branch')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Default Currency</span>
                                        </label>
                                        <select wire:model="default_currency" class="select select-bordered select-sm">
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

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Timezone</span>
                                        </label>
                                        <select wire:model="timezone" class="select select-bordered select-sm">
                                            <option value="Africa/Kampala">Africa/Kampala</option>
                                            <option value="Africa/Nairobi">Africa/Nairobi</option>
                                            <option value="Africa/Dar_es_Salaam">Africa/Dar_es_Salaam</option>
                                            <option value="UTC">UTC</option>
                                        </select>
                                        @error('timezone')
                                            <span class="text-red-600 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-1">
                                            <span class="label-text font-medium text-sm">Default Language</span>
                                        </label>
                                        <select wire:model="default_language" class="select select-bordered select-sm">
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
                                <button type="button" wire:click="nextStep" class="btn btn-primary" wire:loading.attr="disabled" wire:target="nextStep">
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
                                <button type="submit" class="btn btn-success">
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

    {{-- Success/Error Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif
</div>
