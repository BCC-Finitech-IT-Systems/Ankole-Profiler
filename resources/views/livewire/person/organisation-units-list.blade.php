<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Projects Management</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Organization Units</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        @if($units->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($units as $unit)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-800">{{ $unit->name }}</h3>
                            <span class="badge badge-sm {{ $unit->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $unit->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if($unit->description)
                            <p class="text-sm text-gray-500 mb-3">{{ $unit->description }}</p>
                        @endif
                        <div class="text-xs text-gray-400 mb-4">Code: <span class="font-mono">{{ $unit->code }}</span></div>
                        <div class="flex gap-2">
                            <button class="btn btn-xs border-0 text-white" style="background:#982B55;"
                                    wire:click.prevent="applyToJoin({{ $unit->id }})">
                                Apply to Join
                            </button>
                            <a href="{{ route('organization-units.index', ['unit' => $unit->id]) }}"
                               class="btn btn-outline btn-xs">
                                View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center py-16 text-center px-4">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-gray-500 font-medium">No organization units found</p>
            </div>
        @endif

    </div>
</div>
