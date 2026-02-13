<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="profiler">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-base-200">
    <div class="flex h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-base-100 border-r border-base-300 flex flex-col">
            @livewire('sidebar')
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 flex flex-col overflow-hidden">
            {{-- Header --}}
            <header class="bg-base-100 border-b border-base-300 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        @if(isset($header))
                            {{ $header }}
                        @elseif(isset($title) || isset($subtitle))
                            @if(isset($pageCategory))
                                <div class="text-xs text-base-content/60 uppercase tracking-wider font-medium mb-2">{{ $pageCategory }}</div>
                            @endif
                            @if(isset($title))
                                <h1 class="text-3xl font-bold text-base-content mb-2">{{ $title }}</h1>
                            @endif
                            @if(isset($subtitle))
                                <p class="text-base-content/70 text-sm max-w-2xl">{{ $subtitle }}</p>
                            @endif
                        @else
                            <div class="text-xs text-base-content/60 uppercase tracking-wider font-medium mb-2">Ankole
                                Person Profiler</div>
                            <h1 class="text-2xl font-bold text-base-content mb-2">Welcome to Person Registry</h1>
                            <p class="text-base-content/70 text-sm max-w-2xl">
                                Comprehensive person identity management system with role-based access,</br>
                                organizational affiliations, and compliance tracking
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3" id='topbar-menu'>
                        <x-topbar-menu />
                    </div>
                </div>
            </header>

            {{-- Content --}}
            <div class="flex-1 h-[calc(100vh-8rem)] overflow-y-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

</body>
</html>
