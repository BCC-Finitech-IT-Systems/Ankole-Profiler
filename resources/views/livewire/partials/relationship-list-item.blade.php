{{-- resources/views/livewire/partials/relationship-list-item.blade.php --}}
<div class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow p-4">
    <div class="flex items-center justify-between">
        {{-- Left: Person Info --}}
        <div class="flex items-center space-x-4 flex-1">
            {{-- Avatar --}}
            <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ substr($person->first_name, 0, 1) }}{{ substr($person->last_name, 0, 1) }}
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <h4 class="text-sm font-semibold text-gray-900">{{ $person->full_name }}</h4>
                    @if($person->primary_relationship)
                        <i class="fas fa-star text-yellow-500 text-xs" title="Primary Contact"></i>
                    @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                        {{ $person->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($person->status) }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">ID: {{ $person->person_id }}</p>

                {{-- Relationships --}}
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach($person->relationships as $rel)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-rose-50 text-rose-700">
                            {{ ucwords(str_replace('_', ' ', $rel['type'])) }} of {{ $rel['source_person_name'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Middle: Contact Info --}}
        <div class="hidden md:flex flex-col space-y-1 px-6">
            @if($person->phones->first())
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-phone text-gray-400 w-4 mr-2"></i>
                    <span>{{ $person->phones->first()->number }}</span>
                </div>
            @endif
            @if($person->emailAddresses->first())
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-envelope text-gray-400 w-4 mr-2"></i>
                    <span>{{ Str::limit($person->emailAddresses->first()->email, 25) }}</span>
                </div>
            @endif
            @if($person->city)
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-map-marker-alt text-gray-400 w-4 mr-2"></i>
                    <span>{{ $person->city }}</span>
                </div>
            @endif
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center space-x-2">
            <a href="{{ route('persons.show', $person) }}" 
               class="px-3 py-2 text-white rounded-lg text-xs font-medium hover:opacity-90" style="background:#982B55; transition-colors">
                <i class="fas fa-eye mr-1"></i>View
            </a>
            <button wire:click="selectRelationship({{ $person->id }})"
                    class="p-2 {{ in_array($person->id, $selectedRelationships) ? 'text-rose-600' : 'text-gray-400' }} hover:text-rose-600 transition-colors">
                <i class="fas fa-{{ in_array($person->id, $selectedRelationships) ? 'check-square' : 'square' }}"></i>
            </button>
        </div>
    </div>
</div>