@extends('layouts.app')
@section('content')

{{-- Page header --}}
<div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4 sticky top-0 z-10">
    <div class="min-w-0">
        <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">People</div>
        <h2 class="text-sm font-semibold text-gray-800">Search Persons</h2>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('persons.all') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            All Persons
        </a>
        <a href="{{ route('persons.create') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
            style="background:#982B55;">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Add Person
        </a>
    </div>
</div>

<div class="p-4">
    @livewire('person-search')
</div>

@endsection