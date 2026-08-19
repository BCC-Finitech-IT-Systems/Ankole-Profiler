@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
    <div class="max-w-lg w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <h1 class="text-6xl font-bold text-red-600 mb-4">500</h1>
        <h2 class="text-2xl font-semibold mb-2">Server Error</h2>
        <p class="text-gray-600 mb-6">Whoops! Something went wrong on our end.<br>Please try again later or contact support if the problem persists.</p>
        <div class="flex justify-center gap-4">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="mailto:{{ setting('support_contact_email', 'support@ankoleprofiler.com') }}" class="btn btn-outline btn-secondary">Contact Support</a>
        </div>
    </div>
</div>
@endsection
