<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="profiler">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Profiler') }} - {{ $pageTitle ?? 'Dashboard' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root { --brand: #982B55; --brand-dark: #7a2244; }

        body { font-family: 'Figtree', sans-serif; }

        /* Sidebar */
        #sidebar {
            width: 260px;
            min-width: 260px;
            background: #eeeff2;
            transition: transform 0.25s ease;
            z-index: 40;
        }

        /* Mobile: sidebar slides in from left */
        @media (max-width: 767px) {
            #sidebar {
                position: fixed;
                top: 0; left: 0; bottom: 0;
                transform: translateX(-100%);
            }
            #sidebar.open { transform: translateX(0); }
            #sidebar-overlay { display: block; }
        }

        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 39;
        }
        #sidebar-overlay.open { display: block; }

        /* Header */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            height: 60px;
            flex-shrink: 0;
        }

        /* Scrollable content */
        #page-content {
            flex: 1;
            overflow-y: auto;
            background: #f3f4f6;
        }

        /* Brand colour overrides for DaisyUI */
        .btn-primary, .bg-primary { background-color: var(--brand) !important; border-color: var(--brand) !important; color: #fff !important; }
        .btn-primary:hover { background-color: var(--brand-dark) !important; }
        .text-primary { color: var(--brand) !important; }
        .border-primary { border-color: var(--brand) !important; }
        .badge-primary { background-color: var(--brand) !important; color: #fff !important; }
    </style>
</head>

<body class="min-h-screen">
    <div class="flex h-screen overflow-hidden">

        {{-- Mobile overlay --}}
        <div id="sidebar-overlay" onclick="closeSidebar()"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="flex flex-col h-full">
            @livewire('sidebar')
        </aside>

        {{-- Main area --}}
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            {{-- Top bar --}}
            <header id="topbar" class="flex items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-3 min-w-0">
                    <button onclick="toggleSidebar()" class="md:hidden flex-shrink-0 p-2 rounded-lg hover:bg-gray-100 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-400 uppercase tracking-widest font-medium truncate">Ankole Diocese</div>
                        <h1 class="text-sm font-semibold text-gray-800 truncate">Profiler Portal</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2" id="topbar-menu">
                    <x-topbar-menu />
                </div>
            </header>

            {{-- Page content --}}
            <div id="page-content">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </div>

        </div>
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
        }

        // Theme
        const savedTheme = localStorage.getItem('theme') || 'profiler';
        document.documentElement.setAttribute('data-theme', savedTheme);

        document.addEventListener('livewire:load', () => {
            Livewire.on('swal', (data) => { Swal.fire(data); });
        });
    </script>

    <x-sweet-alerts />
</body>
</html>
