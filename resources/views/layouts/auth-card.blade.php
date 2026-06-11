<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="profiler">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - {{ $title ?? 'Sign In' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Figtree', sans-serif; }

        .auth-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left branding panel */
        .auth-brand {
            width: 42%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            background-image: url('{{ asset('images/loginbackground.jpg') }}');
            background-size: cover;
            background-position: center;
            position: relative;
            text-align: center;
        }
        .auth-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(150deg, #982B55ee 0%, #5a1432fa 100%);
        }
        .auth-brand > * { position: relative; z-index: 1; }
        .auth-brand-logo {
            background: #fff;
            border-radius: 20px;
            padding: 1.5rem 2.5rem;
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        }
        .auth-brand-logo img { width: 200px; height: auto; object-fit: contain; display: block; }
        .auth-brand h2 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0 0 0.3rem; letter-spacing: 0.01em; }
        .auth-brand .tagline { color: rgba(255,255,255,0.6); font-size: 0.7rem; letter-spacing: 0.18em; text-transform: uppercase; margin-bottom: 1.75rem; }
        .auth-brand .divider-line { width: 40px; height: 2px; background: rgba(255,255,255,0.3); margin: 0 auto 1.5rem; }
        .auth-brand p { color: rgba(255,255,255,0.7); font-size: 0.85rem; line-height: 1.75; max-width: 260px; }

        /* Right form panel */
        .auth-form-panel {
            width: 58%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 4rem;
            background: #fff;
        }

        /* Input styles */
        .field-group { margin-bottom: 1.25rem; }
        .field-group label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem; }
        .input-wrap { position: relative; }
        .input-wrap .icon { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: #9ca3af; width: 16px; height: 16px; }
        .input-wrap input {
            width: 100%;
            padding: 0.65rem 0.875rem 0.65rem 2.5rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #111827;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .input-wrap input:focus { border-color: #982B55; box-shadow: 0 0 0 3px rgba(152,43,85,0.12); background: #fff; }
        .input-wrap input.error { border-color: #ef4444; }
        .input-wrap .toggle-pw { position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 0; display: flex; }
        .input-wrap .toggle-pw:hover { color: #6b7280; }
        .field-error { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }

        .row-between { display: flex; align-items: center; justify-content: space-between; margin: 1rem 0 1.5rem; }
        .remember-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280; cursor: pointer; }
        .remember-label input[type=checkbox] { width: 15px; height: 15px; accent-color: #982B55; }
        .forgot-link { font-size: 0.875rem; font-weight: 500; color: #982B55; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }

        .btn-signin {
            width: 100%;
            padding: 0.75rem;
            background: #982B55;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-signin:hover { background: #7a2244; }
        .btn-signin:active { transform: scale(0.99); }
        .btn-signin:disabled { opacity: 0.7; cursor: not-allowed; }

        .auth-footer { margin-top: 2rem; text-align: center; font-size: 0.75rem; color: #9ca3af; }

        /* Alert */
        .auth-alert { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.25rem; }
        .auth-alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .auth-alert.info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

        @media (max-width: 768px) {
            .auth-brand { display: none; }
            .auth-form-panel { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">

        {{-- Left Branding Panel --}}
        <div class="auth-brand">
            <div class="auth-brand-logo">
                <img src="/images/Ankole-Diocese-Logo.png" alt="Ankole Diocese" />
            </div>
            <h2>Ankole Diocese</h2>
            <div class="tagline">Yesu Naamara</div>
            <div class="divider-line"></div>
            <p>Welcome to the Ankole Diocese Profiler Portal. Sign in to manage your diocese records and operations.</p>
        </div>

        {{-- Right Form Panel --}}
        <div class="auth-form-panel">
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </div>

    </div>
    @livewireScripts
</body>
</html>
