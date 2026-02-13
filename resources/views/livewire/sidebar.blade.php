<div class="sidebar-container h-full flex flex-col" style="background: #eeeff2">
    <!-- Logo Section -->
    <div class="p-6 border-b border-base-300">
        <div class="flex items-center gap-3">
            <div class="rounded-xl flex items-center justify-center bg-white">
               <a href="{{ route('dashboard') }}">
                {{-- <img src="{{ url('images/ankole-diocese-logo.png') }}" alt="Ankole Diocese Logo" class="w-32 h-32 object-contain" /> --}}
                <img src="/images/Ankole-Diocese-Logo.png" alt="Ankole Diocese Logo" class="w-32 h-32 object-contain" />
                </a>
            </div>
            {{-- <span class="text-xl font-bold text-base-content">Profiler</span> --}}
        </div>
    </div>

     {{-- <div class="p-4 border-b border-indigo-700">
        @livewire('organization-switcher')
    </div> --}}

    <!-- Search Section -->
    <div class="p-4 border-b border-base-300">
        <div class="relative">
            <input type="text"
                   wire:model.live="searchTerm"
                   placeholder="Search menu..."
                   class="sidebar-search">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-base-content opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 sidebar-scroll">
        @foreach($menuItems as $sectionKey => $section)
            @php
                $isExpanded = isset($this->expandedSections[$sectionKey]);
                $hasVisibleItems = false;

                // Check if any items are visible based on permissions
                foreach($section['items'] as $item) {
                    if (auth()->user() && auth()->user()->can($item['permission'] ?? 'view-dashboard')) {
                        $hasVisibleItems = true;
                        break;
                    }
                }
            @endphp

            @if($hasVisibleItems)
                <!-- Section Header -->
                <div class="menu-section">
                    <button wire:click="toggleSection('{{ $sectionKey }}')"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-base-content opacity-70 hover:opacity-100 hover:bg-base-200 rounded-lg transition-all duration-200 group">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"></path>
                            </svg>
                            {{ $section['title'] }}
                        </span>
                        <svg class="w-4 h-4 transition-transform duration-200 {{ $isExpanded ? 'rotate-90' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"></path>
                        </svg>
                    </button>

                    <!-- Section Items -->
                    <div class="menu-items {{ $isExpanded ? 'block' : 'hidden' }} ml-4 mt-1 space-y-1">
                        @foreach($section['items'] as $item)
                            @can($item['permission'] ?? 'view-dashboard')
                                @php
                                    $isActive = $item['active'] ?? false;
                                @endphp

                                <a href="{{ route($item['route']) }}"
                                   class="group flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-all duration-200 {{ $isActive ? 'bg-[#982B55]/10 text-[#982B55] font-medium border border-[#982B55]/20 shadow-sm' : 'text-base-content opacity-80 hover:bg-base-200 hover:opacity-100' }} hover:translate-x-0.5 hover:shadow-sm">
                                    <span class="flex items-center gap-3">
                                        @if(isset($item['icon']))
                                            <svg class="w-4 h-4 flex-shrink-0 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                                            </svg>
                                        @endif
                                        {{ $item['label'] }}
                                    </span>
                                    @if(isset($item['badge']))
                                        <span class="badge badge-sm bg-primary text-white">{{ $item['badge'] }}</span>
                                    @endif
                                </a>

                                <!-- Sub-items (if any) -->
                                @if(isset($item['items']) && count($item['items']) > 0)
                                    <div class="ml-4 mt-1 space-y-1">
                                        @foreach($item['items'] as $subItem)
                                            @can($subItem['permission'] ?? 'view-dashboard')
                                                @php $isSubActive = request()->routeIs($subItem['route']); @endphp
                                                <a href="{{ route($subItem['route']) }}"
                                                   class="flex items-center justify-between px-3 py-2 text-xs rounded-lg transition-all duration-200 {{ $isSubActive ? 'bg-accent/10 text-accent font-medium' : 'text-base-content opacity-60 hover:bg-base-200 hover:opacity-100' }}">
                                                    <span class="flex items-center gap-2">
                                                        <div class="w-1.5 h-1.5 rounded-full {{ $isSubActive ? 'bg-accent' : 'bg-base-content opacity-20' }}"></div>
                                                        {{ $subItem['label'] }}
                                                    </span>

                                                    @if(isset($subItem['badge']) && $subItem['badge'] > 0)
                                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-warning rounded-full">
                                                            {{ $subItem['badge'] }}
                                                        </span>
                                                    @endif
                                                </a>
                                            @endcan
                                        @endforeach
                                    </div>
                                @endif
                            @endcan
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <!-- Recent Items Section (if user has recent activity) -->
    {{-- @auth
        <div class="border-t border-base-300 p-4">
            <h4 class="text-xs font-semibold text-base-content opacity-50 uppercase tracking-wider mb-2">Recent</h4>
            <div class="space-y-1">
                <a href="#" class="flex items-center gap-2 px-2 py-1 text-xs text-base-content opacity-60 hover:opacity-100 rounded transition-opacity">
                    <div class="w-1 h-1 rounded-full bg-base-content opacity-30"></div>
                    John Doe Profile
                </a>
                <a href="#" class="flex items-center gap-2 px-2 py-1 text-xs text-base-content opacity-60 hover:opacity-100 rounded transition-opacity">
                    <div class="w-1 h-1 rounded-full bg-base-content opacity-30"></div>
                    Staff Reports
                </a>
                <a href="#" class="flex items-center gap-2 px-2 py-1 text-xs text-base-content opacity-60 hover:opacity-100 rounded transition-opacity">
                    <div class="w-1 h-1 rounded-full bg-base-content opacity-30"></div>
                    Organization Settings
                </a>
            </div>
        </div>
    @endauth --}}

    <!-- User Info Section -->
    {{-- @auth
        <div class="border-t border-base-300 p-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-accent/20 rounded-full flex items-center justify-center">
                    <span class="text-sm font-semibold text-accent">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-base-content truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-base-content opacity-60 truncate">
                        @if(auth()->user()->getRoleNames()->isNotEmpty())
                            {{ auth()->user()->getRoleNames()->first() }}
                        @else
                            User
                        @endif
                    </p>
                </div>
                <button class="p-1 text-base-content opacity-60 hover:opacity-100 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endauth --}}
</div>
