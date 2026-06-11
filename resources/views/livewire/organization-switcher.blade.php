<div>
    <div x-data="{ open: @entangle('isOpen') }" @click.away="open = false" class="relative w-full">

        @if ($canSwitchOrganizations && count($availableOrganizations) > 0)
            {{-- Switcher Button --}}
            <button @click="open = !open" type="button"
                class="w-full rounded-lg p-3 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-400 text-white" style="background:#982B55;">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 text-left flex-1 min-w-0">
                        {{-- Organization Logo/Icon --}}
                        @if ($currentOrganizationLogo)
                            <img src="{{ Storage::url($currentOrganizationLogo) }}" alt="{{ $currentOrganizationName }}"
                                class="w-8 h-8 rounded-lg object-cover flex-shrink-0">
                        @else
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#7a2244;">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif

                        {{-- Organization Name --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-white/70 font-medium">Organization</div>
                            <div class="text-sm font-semibold text-white truncate">
                                {{ $currentOrganizationName }}
                            </div>
                        </div>
                    </div>

                    {{-- Dropdown Icon --}}
                    <svg class="w-4 h-4 text-white/70 transition-transform flex-shrink-0"
                        :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </div>

                {{-- Role Badge --}}
                @if ($userRole)
                    <div class="mt-2 pt-2 border-t border-white/20">
                        <span class="text-xs text-white/70">
                            Your role: <span class="font-semibold text-white">{{ $userRole }}</span>
                        </span>
                    </div>
                @endif
            </button>

            {{-- Dropdown Menu --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute z-50 mt-2 w-full bg-white rounded-lg shadow-xl border border-gray-200 max-h-96 overflow-hidden"
                style="display: none;">

                {{-- Search Box --}}
                @if (count($availableOrganizations) > 5)
                    <div class="p-3 border-b border-gray-200">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" wire:model.debounce.300ms="searchTerm"
                                placeholder="Search organizations..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent text-sm">
                        </div>
                    </div>
                @endif

                {{-- Organization List --}}
                <div class="overflow-y-auto max-h-80">
                    @if (count($availableOrganizations) > 0)
                        <div class="p-2">
                            @foreach ($availableOrganizations as $org)
                                <button wire:click="switchOrganization('{{ $org['id'] }}')" type="button"
                                    class="w-full text-left px-3 py-3 rounded-lg hover:bg-rose-50 transition-colors group
                                           {{ $org['id'] == $currentOrganizationId ? 'bg-rose-50 ring-2 ring-rose-500' : '' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            {{-- Organization Logo/Icon --}}
                                            @if ($org['logo_path'])
                                                <img src="{{ Storage::url($org['logo_path']) }}"
                                                    alt="{{ $org['display_name'] }}"
                                                    class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                            @else
                                                <div
                                                    class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#982B55;">
                                                    <span class="text-white font-bold text-sm">
                                                        {{ substr($org['display_name'], 0, 2) }}
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Organization Info --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <div class="text-sm font-semibold text-gray-900 truncate">
                                                        {{ $org['display_name'] }}
                                                    </div>

                                                    {{-- Primary Badge --}}
                                                    @if ($org['is_primary'])
                                                        <span
                                                            class="px-2 py-0.5 bg-rose-50 text-rose-700 text-xs font-medium rounded">
                                                            Primary
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-gray-500">
                                                        {{ $org['code'] }}
                                                    </span>
                                                    @if ($org['category'])
                                                        <span class="text-xs text-gray-400">•</span>
                                                        <span class="text-xs text-gray-500 capitalize">
                                                            {{ str_replace('_', ' ', $org['category']) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- User Role in Organization --}}
                                                @if ($org['user_role'])
                                                    <div class="text-xs text-rose-600 font-medium mt-1">
                                                        {{ $org['user_role'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Current Indicator --}}
                                        @if ($org['id'] == $currentOrganizationId)
                                            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- No Results --}}
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-sm text-gray-500">No organizations found</p>
                            @if ($searchTerm)
                                <button wire:click="$set('searchTerm', '')"
                                    class="mt-2 text-sm text-rose-600 hover:text-rose-700">
                                    Clear search
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Footer Info --}}
                <div class="p-3 border-t border-gray-200 bg-gray-50">
                    <p class="text-xs text-gray-600 text-center">
                        <span class="font-medium">{{ count($availableOrganizations) }}</span>
                        {{ count($availableOrganizations) === 1 ? 'organization' : 'organizations' }} available
                    </p>
                </div>
            </div>
        @else
            {{-- Single Organization (No Switching) --}}
            <div class="w-full rounded-lg p-3" style="background:#982B55;">
                <div class="flex items-center gap-3">
                    {{-- Organization Logo/Icon --}}
                    @if ($currentOrganizationLogo)
                        <img src="{{ Storage::url($currentOrganizationLogo) }}" alt="{{ $currentOrganizationName }}"
                            class="w-8 h-8 rounded-lg object-cover">
                    @else
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#7a2244;">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif

                    {{-- Organization Name --}}
                    <div class="flex-1">
                        <div class="text-xs text-white/70 font-medium">Organization</div>
                        <div class="text-sm font-semibold text-white">
                            {{ $currentOrganizationName }}
                        </div>
                    </div>
                </div>

                @if ($userRole)
                    <div class="mt-2 pt-2 border-t border-white/20">
                        <span class="text-xs text-white/70">
                            Your role: <span class="font-semibold text-white">{{ $userRole }}</span>
                        </span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Loading State --}}
        <div wire:loading wire:target="switchOrganization"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 shadow-xl">
                <div class="flex items-center gap-3">
                    <svg class="animate-spin h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-gray-700">Switching organization...</span>
                </div>
            </div>
        </div>
    </div>
</div>
