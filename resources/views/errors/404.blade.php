@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4">
    <div class="max-w-lg w-full bg-white shadow-lg rounded-lg p-6 sm:p-8 text-center">
        <h1 class="text-5xl sm:text-6xl font-bold text-yellow-500 mb-4">404</h1>
        <h2 class="text-xl sm:text-2xl font-semibold mb-2">Page Not Found</h2>
        <p class="text-gray-600 text-sm sm:text-base mb-6">Sorry, the page you are looking for could not be found.<br>It might have been moved or deleted.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary w-full sm:w-auto">Go Home</a>
            <a href="mailto:{{ setting('support_contact_email', 'support@ankoleprofiler.com') }}" class="btn btn-outline btn-secondary w-full sm:w-auto">Contact Support</a>
        </div>
    </div>
</div>
@endsection
