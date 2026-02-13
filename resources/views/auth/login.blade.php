@extends('layouts.auth-card')
@section('content')

 @if (auth()->check())
        <div class="mb-4 text-center">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    @endif
    @if (auth()->check())
        <script>
            window.location.href = '{{ route('dashboard') }}';
        </script>
    @endif
    <h3 class="card-title text-2xl font-bold text-center justify-center mb-6" style="max-width: 400px; margin: 0 auto;">Sign
        In</h3>
    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success mb-4">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="max-width: 800px; margin: 0 auto;">
        @csrf
        <fieldset class="fieldset">
            <legend class="fieldset-legend sr-only">Login Credentials</legend>
            <!-- Email Address -->
            <div class="form-control">
                <label class="label" for="email">
                    <span class="label-text font-medium">Email address</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                    required class="input input-bordered input-lg w-full @error('email') input-error @enderror"
                    placeholder="Enter your email">
                @error('email')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>
            <!-- Password -->
            <div class="form-control">
                <label class="label" for="password">
                    <span class="label-text font-medium">Password</span>
                </label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="input input-bordered input-lg w-full @error('password') input-error @enderror"
                    placeholder="Enter your password">
                @error('password')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>
            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mt-6">
                <label class="label cursor-pointer">
                    <input id="remember_me" name="remember" type="checkbox" class="checkbox checkbox-secondary checkbox-sm">
                    <span class="label-text ml-2">Remember me</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link link-secondary text-sm">
                        Forgot password?
                    </a>
                @endif
            </div>
        </fieldset>
        <!-- Submit Button -->
        <div class="form-control mt-6">
            <button type="submit" id="loginBtn" class="btn btn-primary btn-lg w-full">
                <!-- Loading Spinner (hidden by default) -->
                <span id="loginSpinner" class="loading loading-spinner loading-sm mr-2 hidden"></span>
                <!-- Login Icon -->
                <svg id="loginIcon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
                <span id="loginText">Sign In</span>
            </button>
        </div>
    </form>
    <!-- Divider -->
    <div class="divider"></div>
    <!-- Self Registration Link -->

    <!-- Help Links -->
    {{-- <div class="flex justify-center space-x-4 text-sm">
        <a href="#" class="link link-secondary">Contact Support</a>
        <span class="text-base-content/40">|</span>
        <a href="#" class="link link-secondary">Documentation</a>
    </div> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('form');
            const loginBtn = document.getElementById('loginBtn');
            const loginSpinner = document.getElementById('loginSpinner');
            const loginIcon = document.getElementById('loginIcon');
            const loginText = document.getElementById('loginText');
            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function() {
                    // Show loading state
                    loginSpinner.classList.remove('hidden');
                    loginIcon.classList.add('hidden');
                    loginText.textContent = 'Signing In...';
                    loginBtn.disabled = true;
                    loginBtn.classList.add('btn-disabled');
                });
            }
        });
    </script>
@endsection
