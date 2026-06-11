{{-- resources/views/livewire/partials/relationship-card.blade.php --}}
<div class="bg-white rounded-lg border-2 border-gray-200 hover:border-rose-400 hover:shadow-lg transition-all duration-200 overflow-hidden group">
    {{-- Header with Avatar --}}
    <div class="relative p-4" style="background:#982B55;">
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-14 w-14 rounded-full bg-white bg-opacity-20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-lg border-2 border-white">
                    {{ substr($person->first_name, 0, 1) }}{{ substr($person->last_name, 0, 1) }}
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm">{{ $person->full_name }}</h4>
                    <p class="text-white/70 text-xs">ID: {{ $person->person_id }}</p>
                </div>
            </div>
            
            @if($person->primary_relationship)
                <div class="flex items-center justify-center h-6 w-6 rounded-full bg-yellow-400" title="Primary Contact">
                    <i class="fas fa-star text-white text-xs"></i>
                </div>
            @endif
        </div>
    </div>

    {{-- Body --}}
    <div class="p-4 space-y-3">
        {{-- Relationship Tags --}}
        <div class="flex flex-wrap gap-1">
            @foreach($person->relationships as $rel)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                    <i class="fas fa-link mr-1"></i>
                    {{ ucwords(str_replace('_', ' ', $rel['type'])) }}
                </span>
            @endforeach
        </div>

        {{-- Connected To --}}
        <div class="bg-gray-50 rounded-md p-2">
            <p class="text-xs text-gray-500 font-medium mb-1">Connected to:</p>
            @foreach($person->relationships->take(2) as $rel)
                <div class="flex items-center text-xs text-gray-700 mb-1">
                    <i class="fas fa-arrow-right text-gray-400 mr-2"></i>
                    <span class="font-medium">{{ $rel['source_person_name'] }}</span>
                </div>
            @endforeach
            @if($person->relationships->count() > 2)
                <span class="text-xs text-rose-600 font-medium">
                    +{{ $person->relationships->count() - 2 }} more
                </span>
            @endif
        </div>

        {{-- Contact Info --}}
        <div class="space-y-1">
            @if($person->phones->first())
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-phone text-gray-400 w-4 mr-2"></i>
                    <span>{{ $person->phones->first()->number }}</span>
                </div>
            @endif
            @if($person->emailAddresses->first())
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-envelope text-gray-400 w-4 mr-2"></i>
                    <span class="truncate">{{ $person->emailAddresses->first()->email }}</span>
                </div>
            @endif
        </div>

        {{-- Demographics --}}
        <div class="flex items-center space-x-2 text-xs text-gray-500">
            @if($person->gender)
                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100">
                    <i class="fas fa-{{ $person->gender === 'male' ? 'mars' : ($person->gender === 'female' ? 'venus' : 'genderless') }} mr-1"></i>
                    {{ ucfirst($person->gender) }}
                </span>
            @endif
            @if($person->date_of_birth)
                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100">
                    <i class="fas fa-birthday-cake mr-1"></i>
                    {{ $person->date_of_birth->age }} yrs
                </span>
            @endif
        </div>

        {{-- Status --}}
        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $person->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst($person->status) }}
            </span>
            <a href="{{ route('persons.show', $person) }}" 
               class="text-rose-600 hover:text-rose-800 text-xs font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                View Details <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>