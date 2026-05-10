<div
    x-data="{
        mobileOpen: $wire.entangle('mobileDrawerOpen'),
        searchFocused: false
    }"
    class="sidebar-wrapper">
    <style>
        [x-cloak] { display: none !important; }

        /* Custom Scrollbar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Smooth scroll behavior */
        .sidebar-scroll {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        /* Mobile safe area support */
        @supports (padding: max(0px)) {
            .sidebar-panel {
                padding-bottom: max(1rem, env(safe-area-inset-bottom));
            }
        }

        /* Active state glow */
        .menu-item-active {
            box-shadow: 0 4px 12px -2px rgba(152, 43, 85, 0.35);
        }

        /* Badge pulse */
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .badge-pulse { animation: badgePulse 2s ease-in-out infinite; }

        /* Touch-friendly spacing on mobile */
        @media (max-width: 1023px) {
            .menu-section button,
            .menu-section a { min-height: 48px; }
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    <!-- Mobile Overlay -->
    <div
        x-cloak
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileOpen = false"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
    ></div>

    <!-- Mobile Toggle Button (Fixed FAB) -->
    <button
        x-cloak
        x-show="!mobileOpen"
        @click="mobileOpen = true"
        class="fixed bottom-6 left-6 z-30 lg:hidden flex items-center justify-center w-14 h-14 rounded-2xl bg-[#982B55] text-white shadow-lg shadow-[#982B55]/30 hover:scale-105 active:scale-95 transition-all duration-200"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Sidebar Panel -->
    <aside
        x-cloak
        :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="sidebar-panel fixed lg:sticky top-0 left-0 z-50 lg:z-auto h-screen w-[280px] lg:w-[260px] flex flex-col bg-gradient-to-b from-slate-50 to-slate-100/95 border-r border-slate-200/60 transition-transform duration-300 ease-out will-change-transform"
    >
        <!-- Header Section -->
        <div class="flex-shrink-0 p-5 border-b border-slate-200/60">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-white shadow-md shadow-slate-200/50 flex items-center justify-center overflow-hidden ring-1 ring-slate-100 group-hover:shadow-lg group-hover:scale-[1.02] transition-all duration-200">
                            <img src="/images/Ankole-Diocese-Logo.png" alt="Ankole Diocese Logo" class="w-12 h-12 object-contain" />
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white"></div>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-sm font-bold text-slate-800 leading-tight">Ankole Diocese</h1>
                        <p class="text-[11px] text-slate-500 font-medium">Profiling System</p>
                    </div>
                </a>

                <!-- Mobile Close Button -->
                <button
                    @click="mobileOpen = false"
                    class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors duration-150"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Section -->
        <div class="flex-shrink-0 px-4 py-3">
            <div class="relative" :class="searchFocused ? 'scale-[1.02]' : 'scale-100'" style="transition: transform 0.15s ease">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchTerm"
                    @focus="searchFocused = true"
                    @blur="searchFocused = false"
                    placeholder="Search menu..."
                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400 focus:outline-none focus:border-[#982B55]/40 focus:ring-2 focus:ring-[#982B55]/10 transition-all duration-200"
                >
                <svg
                    class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors duration-200"
                    :class="searchFocused ? 'text-[#982B55]' : 'text-slate-400'"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                @if($searchTerm)
                    <button
                        wire:click="$set('searchTerm', '')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1.5 sidebar-scroll">
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
                    <div
                        x-data="{ open: {{ $isExpanded ? 'true' : 'false' }} }"
                        class="menu-section"
                    >
                        <!-- Section Header -->
                        <button
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-600 hover:bg-white hover:shadow-sm hover:text-slate-800 group transition-all duration-200"
                        >
                            <span class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-[#982B55]/10 group-hover:text-[#982B55] transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"></path>
                                    </svg>
                                </span>
                                <span class="text-sm font-semibold">{{ $section['title'] }}</span>
                            </span>
                            <svg
                                class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                :class="open ? 'rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>

                        <!-- Section Items -->
                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mt-1 ml-3 pl-5 border-l-2 border-slate-200 space-y-0.5"
                        >
                            @foreach($section['items'] as $item)
                                @can($item['permission'] ?? 'view-dashboard')
                                    @php
                                        $isActive = $item['active'] ?? false;
                                        $itemHref = $item['url'] ?? route($item['route']);
                                    @endphp

                                    <a
                                        href="{{ $itemHref }}"
                                        @click="if(window.innerWidth < 1024) { mobileOpen = false }"
                                        class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-200 {{ $isActive
                                            ? 'bg-[#982B55] text-white menu-item-active'
                                            : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-slate-800 hover:translate-x-0.5' }}"
                                    >
                                        <span class="flex items-center gap-3">
                                            @if(isset($item['icon']))
                                                <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-white/80' : 'text-slate-400 group-hover:text-[#982B55]' }} transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                                                </svg>
                                            @else
                                                <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-white' : 'bg-slate-300 group-hover:bg-[#982B55]' }} transition-colors duration-200"></span>
                                            @endif
                                            <span class="font-medium">{{ $item['label'] }}</span>
                                        </span>
                                        @if(isset($item['badge']) && $item['badge'] > 0)
                                            <span class="flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-bold rounded-full badge-pulse {{ $isActive
                                                ? 'bg-white/20 text-white'
                                                : 'bg-rose-500 text-white' }}">
                                                {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                            </span>
                                        @endif
                                    </a>

                                    <!-- Sub-items -->
                                    @if(isset($item['items']) && count($item['items']) > 0)
                                        <div class="ml-4 mt-0.5 space-y-0.5">
                                            @foreach($item['items'] as $subItem)
                                                @can($subItem['permission'] ?? 'view-dashboard')
                                                    @php $isSubActive = request()->routeIs($subItem['route']); @endphp
                                                    <a
                                                        href="{{ route($subItem['route']) }}"
                                                        @click="if(window.innerWidth < 1024) { mobileOpen = false }"
                                                        class="flex items-center justify-between px-3 py-2 rounded-lg text-xs transition-all duration-200 {{ $isSubActive
                                                            ? 'bg-[#982B55]/10 text-[#982B55] font-semibold'
                                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700' }}"
                                                    >
                                                        <span class="flex items-center gap-2">
                                                            <span class="w-1 h-1 rounded-full {{ $isSubActive ? 'bg-[#982B55]' : 'bg-slate-300' }}"></span>
                                                            {{ $subItem['label'] }}
                                                        </span>
                                                        @if(isset($subItem['badge']) && $subItem['badge'] > 0)
                                                            <span class="text-[10px] font-bold text-rose-500">{{ $subItem['badge'] }}</span>
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

        <!-- User Footer Section -->
        @auth
            <div class="flex-shrink-0 p-4 border-t border-slate-200/60 bg-gradient-to-t from-slate-100/50 to-transparent">
                <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/80 transition-colors duration-200 cursor-pointer group">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#982B55] to-[#7a2245] flex items-center justify-center shadow-md shadow-[#982B55]/20">
                            <span class="text-sm font-bold text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-slate-50"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">
                            @if(auth()->user()->getRoleNames()->isNotEmpty())
                                {{ auth()->user()->getRoleNames()->first() }}
                            @else
                                User
                            @endif
                        </p>
                    </div>
                    <button class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 opacity-0 group-hover:opacity-100 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endauth
    </aside>
</div>
