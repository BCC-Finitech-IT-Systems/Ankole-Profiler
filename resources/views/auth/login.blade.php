@extends('layouts.auth-card')
@section('content')

@if (auth()->check())
    <script>window.location.href = '{{ route('dashboard') }}';</script>
@endif

<div style="max-width: 420px; width: 100%;">

    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem;">Sign In</h2>
        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Enter your credentials to access your account</p>
    </div>

    @if (session('status'))
        <div class="auth-alert success">
            <svg style="width:16px;height:16px;flex-shrink:0;margin-top:2px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('self_register_success'))
        <div class="auth-alert info">
            <svg style="width:16px;height:16px;flex-shrink:0;margin-top:2px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <span>Registration successful! Check your email for verification instructions.</span>
        </div>
    @endif

    <button type="button" onclick="fillDemoTenantAdmin()" style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:1.25rem;padding:0.7rem 0.875rem;border:1px solid #f0d6e1;border-radius:8px;background:#fff7fb;color:#982B55;font-size:0.9rem;font-weight:600;cursor:pointer;">
        <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.879 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20.662V19a5 5 0 0110 0v1.662"/>
        </svg>
        Use demo tenant admin
    </button>
    <p style="text-align:center;margin-top:-0.9rem;margin-bottom:1.25rem;font-size:0.78rem;color:#9a8f95;">
        demo.tenantadmin@ankole.test / Demo@Ankole2026
    </p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="field-group">
            <label for="email">Email address</label>
            <div class="input-wrap">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    autocomplete="email" required placeholder="you@bcc.co.ug"
                    class="{{ $errors->has('email') ? 'error' : '' }}">
            </div>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="field-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input id="password" name="password" type="password"
                    autocomplete="current-password" required placeholder="Enter your password"
                    style="padding-right: 2.75rem;"
                    class="{{ $errors->has('password') ? 'error' : '' }}">
                <button type="button" class="toggle-pw" onclick="togglePassword()" tabindex="-1">
                    <svg id="eyeIcon" style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember & Forgot --}}
        <div class="row-between">
            <label class="remember-label">
                <input type="checkbox" name="remember" id="remember_me">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-signin" id="loginBtn">
            <svg id="loginIcon" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            <span id="loginText">Sign In</span>
        </button>
    </form>

    <div style="text-align:center;margin-top:1.25rem;font-size:0.875rem;color:#6b7280;">
        New to the portal?
        <a href="{{ route('person.self-register') }}"
           style="color:#982B55;font-weight:600;text-decoration:none;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">
            Register as a member
        </a>
    </div>

    <div class="auth-footer">&copy; {{ date('Y') }} Ankole Diocese Profiler Portal. All rights reserved.</div>
</div>

<script>
    const demoTenantAdmin = {
        email: @js('demo.tenantadmin@ankole.test'),
        password: @js('Demo@Ankole2026'),
    };

    function fillDemoTenantAdmin() {
        document.getElementById('email').value = demoTenantAdmin.email;
        document.getElementById('password').value = demoTenantAdmin.password;
        document.getElementById('remember_me').checked = false;
        document.getElementById('email').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('password').dispatchEvent(new Event('input', { bubbles: true }));
    }

    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        icon.innerHTML = showing
            ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`
            : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
    }

    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        const icon = document.getElementById('loginIcon');
        const text = document.getElementById('loginText');
        icon.innerHTML = `<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" opacity="0.25"/><path d="M12 2a10 10 0 010 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="animation:spin 1s linear infinite;transform-origin:center"/>`;
        text.textContent = 'Signing In...';
        btn.disabled = true;
        btn.style.opacity = '0.75';
    });
</script>
@endsection
