<div class="flex flex-col h-full" style="background: #eeeff2;">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-base-300">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="rounded-lg bg-white p-1 flex items-center justify-center shadow-sm">
                <img src="/images/Ankole-Diocese-Logo.png" alt="Logo" class="h-9 w-9 object-contain" />
            </div>
            <div>
                <div class="text-sm font-bold text-base-content leading-tight">Ankole Diocese</div>
                <div class="text-xs text-base-content/50">Profiler Portal</div>
            </div>
        </a>
    </div>

    {{-- Search --}}
    <div class="px-4 py-3 border-b border-base-300">
        <input type="text"
               wire:model.live="searchTerm"
               placeholder="Search menu..."
               class="sidebar-search">
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 sidebar-scroll">
        @foreach($menuItems as $sectionKey => $section)
            @php
                $isExpanded = isset($this->expandedSections[$sectionKey]);
                $hasVisibleItems = false;
                if (isset($section['items']) && is_array($section['items'])) {
                    foreach($section['items'] as $item) {
                        if (auth()->user() && auth()->user()->can($item['permission'] ?? 'view-dashboard')) {
                            $hasVisibleItems = true;
                            break;
                        }
                    }
                }
            @endphp

            @if($hasVisibleItems)
                <div class="menu-section">
                    @php $sectionActive = $section['active'] ?? false; @endphp
                    <button wire:click="toggleSection('{{ $sectionKey }}')"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold rounded-lg transition-all duration-200 {{ $sectionActive ? 'text-[#982B55] bg-[#982B55]/8' : 'text-base-content opacity-70 hover:opacity-100 hover:bg-base-200' }}">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 {{ $sectionActive ? 'text-[#982B55]' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/>
                            </svg>
                            {{ $section['title'] }}
                        </span>
                        <svg class="w-4 h-4 transition-transform duration-200 {{ $isExpanded ? 'rotate-90' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>

                    <div class="menu-items {{ $isExpanded ? 'block' : 'hidden' }} ml-4 mt-1 space-y-1">
                        @foreach($section['items'] as $item)
                            @can($item['permission'] ?? 'view-dashboard')
                                @php
                                    $isActive = $item['active'] ?? false;
                                    $itemHref = $item['url'] ?? route($item['route']);
                                @endphp
                                <a href="{{ $itemHref }}"
                                   class="group flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-all duration-200 {{ $isActive ? 'bg-[#982B55]/10 text-[#982B55] font-medium border border-[#982B55]/20 shadow-sm' : 'text-base-content opacity-80 hover:bg-base-200 hover:opacity-100' }} hover:translate-x-0.5 hover:shadow-sm">
                                    <span class="flex items-center gap-3">
                                        @if(isset($item['icon']))
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                            </svg>
                                        @endif
                                        {{ $item['label'] }}
                                    </span>
                                    @if(isset($item['badge']))
                                        <span class="badge badge-sm bg-primary text-white">{{ $item['badge'] }}</span>
                                    @endif
                                </a>

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

    {{-- User info --}}
    @auth
    <div class="border-t border-base-300 px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm text-white flex-shrink-0"
                 style="background: #982B55;">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-base-content truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-base-content/50 truncate">
                    {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Logout"
                        class="p-1.5 rounded-lg text-base-content/40 hover:text-red-500 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endauth
</div>
