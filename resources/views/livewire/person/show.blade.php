@extends('layouts.app')

@section('content')
    <div class="py-6 px-4 md:px-8">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Person Details</h3>
            </div>
            <dl>
                <div class="bg-gray-50 px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $person->full_name }}</dd>
                </div>
                <div class="bg-white px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 border-t border-gray-100">
                    <dt class="text-sm font-medium text-gray-500">Contact Information</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <p><strong>Phone:</strong> {{ $person->phones->pluck('number')->join(', ') ?: 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $person->emailAddresses->pluck('email')->join(', ') ?: 'N/A' }}</p>
                    </dd>
                </div>
                <div class="bg-gray-50 px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 border-t border-gray-100">
                    <dt class="text-sm font-medium text-gray-500">Affiliations</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 space-y-2">
                        @foreach ($person->affiliations as $affiliation)
                            <div>
                                <span class="font-medium">{{ $affiliation->Organization->legal_name ?? 'Not Provided' }}</span>
                                <span class="text-gray-500"> — {{ $affiliation->role_title ?? ($affiliation->role_type ?? 'No Role') }}</span>
                            </div>
                        @endforeach
                    </dd>
                </div>
                <div class="bg-white px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4 border-t border-gray-100">
                    <dt class="text-sm font-medium text-gray-500">Location</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $person->city ?? 'N/A' }}, {{ $person->country ?? 'N/A' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
