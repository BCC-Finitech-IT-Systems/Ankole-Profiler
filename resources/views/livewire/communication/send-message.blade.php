<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Communication</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Send Communication</h1>
            </div>
            <span class="text-xs text-gray-500 flex-shrink-0">Step {{ $currentStep }} of {{ $totalSteps }}</span>
        </div>
    </x-slot>

    <div class="p-6 space-y-4">

        {{-- Progress Steps --}}
        <div class="bg-white border border-gray-200 rounded-xl px-6 py-4">
            <div class="flex items-center">
                @foreach($step_names as $index => $stepName)
                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ ($index + 1) <= $currentStep ? 'text-white' : 'bg-gray-100 text-gray-500' }}"
                            style="{{ ($index + 1) <= $currentStep ? 'background:#982B55;' : '' }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ml-2 text-sm font-medium {{ ($index + 1) <= $currentStep ? 'text-rose-700' : 'text-gray-400' }}">
                            {{ $stepName }}
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 mx-4 {{ ($index + 1) < $currentStep ? 'bg-rose-400' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Step Content --}}
        <div class="bg-white border border-gray-200 rounded-xl">
            <div class="p-6">

                {{-- STEP 1: RECIPIENT SELECTION --}}
                @if($currentStep === 1)
                    <div class="space-y-6">
                        @if(auth()->user() && auth()->user()->hasRole('Super Admin'))
                            <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span><strong>Super Admin Access:</strong> You can search and communicate with persons from all organizations.</span>
                            </div>
                        @endif

                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-4">Select Recipients</h4>

                            {{-- Selection Mode Tabs --}}
                            <div class="border-b border-gray-200 mb-6">
                                <nav class="-mb-px flex space-x-6">
                                    <button wire:click="setSelectionMode('search')"
                                        class="py-2.5 px-1 border-b-2 text-sm font-medium transition-colors {{ $selectionMode === 'search' ? 'border-rose-600 text-rose-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                                        Search Persons
                                    </button>
                                    <button wire:click="setSelectionMode('filter_profile')"
                                        class="py-2.5 px-1 border-b-2 text-sm font-medium transition-colors {{ $selectionMode === 'filter_profile' ? 'border-rose-600 text-rose-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                                        Filter Profiles
                                    </button>
                                    <button wire:click="setSelectionMode('all')"
                                        class="py-2.5 px-1 border-b-2 text-sm font-medium transition-colors {{ $selectionMode === 'all' ? 'border-rose-600 text-rose-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                                        All Persons
                                    </button>
                                </nav>
                            </div>

                            {{-- Search Mode --}}
                            @if($selectionMode === 'search')
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Search for persons</label>
                                        <input wire:model.live.debounce.300ms="person_search" type="text"
                                            placeholder="Type name to search..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                    </div>

                                    @if(!empty($search_results))
                                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                            @foreach($search_results as $person)
                                                <div class="p-4 hover:bg-gray-50 cursor-pointer" wire:click="togglePersonSelection({{ $person['id'] }})">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-3">
                                                            <input type="checkbox" {{ in_array($person['id'], $selected_persons) ? 'checked' : '' }}
                                                                class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-900">{{ $person['given_name'] }} {{ $person['family_name'] }}</p>
                                                                <p class="text-xs text-gray-500">
                                                                    @if(isset($person['email_addresses'][0])) {{ $person['email_addresses'][0]['email'] }} @endif
                                                                    @if(isset($person['phones'][0])) &bull; {{ $person['phones'][0]['number'] }} @endif
                                                                </p>
                                                                @if(auth()->user()->hasRole('Super Admin') && isset($person['organization_name']))
                                                                    <p class="text-xs font-medium" style="color:#982B55;">{{ $person['organization_name'] }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-right text-sm text-gray-600">
                                                            @if(isset($person['affiliations'][0])) {{ $person['affiliations'][0]['role_title'] ?? 'Member' }} @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(!empty($selected_persons))
                                        <div class="flex items-center gap-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-700">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ count($selected_persons) }} person(s) selected
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Filter Profile Mode --}}
                            @if($selectionMode === 'filter_profile')
                                <div class="space-y-4">
                                    @if(count($available_filter_profiles) > 0)
                                        <div class="flex items-center justify-between">
                                            <label class="block text-xs font-medium text-gray-700">Select a Filter Profile</label>
                                            <a href="{{ route('communication.filter-profiles') }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                Create New
                                            </a>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach($available_filter_profiles as $profile)
                                                <div class="border rounded-lg p-4 cursor-pointer transition-colors {{ $selected_filter_profile_id == $profile['id'] ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }}"
                                                    wire:click="selectFilterProfile({{ $profile['id'] }})">
                                                    <div class="flex items-start gap-3">
                                                        <input type="radio" name="filter_profile"
                                                            {{ $selected_filter_profile_id == $profile['id'] ? 'checked' : '' }}
                                                            class="mt-1 w-4 h-4 border-gray-300" style="accent-color:#982B55;">
                                                        <div>
                                                            <h5 class="font-medium text-gray-900 text-sm">{{ $profile['name'] }}</h5>
                                                            @if($profile['description'])
                                                                <p class="text-xs text-gray-500 mt-0.5">{{ $profile['description'] }}</p>
                                                            @endif
                                                            <div class="flex items-center gap-4 mt-2">
                                                                <span class="text-xs font-medium text-rose-700">~{{ number_format($profile['estimated_count']) }} matches</span>
                                                                @if($profile['is_shared'])
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Shared</span>
                                                                @endif
                                                                <span class="text-xs text-gray-400">Used {{ $profile['usage_count'] }} times</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($selected_filter_profile_id)
                                            <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <div>
                                                    <p class="font-medium">Filter Profile Selected</p>
                                                    <p class="text-xs mt-0.5">This will send to approximately {{ number_format($filter_profile_preview_count) }} person(s) matching your selected filter criteria.</p>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-10">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/></svg>
                                            </div>
                                            <h3 class="text-sm font-semibold text-gray-900 mb-1">No Filter Profiles Available</h3>
                                            <p class="text-xs text-gray-500 mb-4">Create filter profiles to quickly target specific groups of people.</p>
                                            <a href="{{ route('communication.filter-profiles') }}"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg"
                                                style="background:#982B55;">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                Create Filter Profile
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- All Persons Mode --}}
                            @if($selectionMode === 'all')
                                <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                    <div>
                                        <p class="font-medium">Send to All Persons</p>
                                        <p class="mt-0.5">
                                            @if(auth()->user() && auth()->user()->hasRole('Super Admin'))
                                                This will send to all {{ $filter_preview_count }} persons across <strong>ALL organizations</strong>.
                                            @else
                                                This will send to all {{ $filter_preview_count }} persons in your organization.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- STEP 2: CHANNEL SELECTION --}}
                @if($currentStep === 2)
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Choose Communication Channels</h4>
                            <p class="text-xs text-gray-500 mb-4">Select one or more channels to send your message through.</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- Email --}}
                                <div class="border-2 rounded-lg p-4 cursor-pointer transition-colors {{ in_array('email', $selected_channels) ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }}"
                                    wire:click="toggleChannel('email')">
                                    <div class="flex items-center gap-2 mb-3">
                                        <input type="checkbox" {{ in_array('email', $selected_channels) ? 'checked' : '' }}
                                            class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                                        <svg class="w-5 h-5 {{ in_array('email', $selected_channels) ? 'text-rose-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h5 class="font-medium text-gray-900 text-sm">Email</h5>
                                    <p class="text-xs text-gray-500 mt-0.5">Send formatted emails with subject lines</p>
                                </div>

                                {{-- SMS --}}
                                <div class="border-2 rounded-lg p-4 cursor-pointer transition-colors {{ in_array('sms', $selected_channels) ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }} {{ !$this->isSmsChannelAvailable() ? 'opacity-60' : '' }}"
                                    wire:click="toggleChannel('sms')">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" {{ in_array('sms', $selected_channels) ? 'checked' : '' }}
                                                {{ !$this->isSmsChannelAvailable() ? 'disabled' : '' }}
                                                class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                                            <svg class="w-5 h-5 {{ in_array('sms', $selected_channels) ? 'text-rose-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        </div>
                                        @if($this->isSmsChannelAvailable())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Ready</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Unavailable</span>
                                        @endif
                                    </div>
                                    <h5 class="font-medium text-gray-900 text-sm">SMS</h5>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $this->isSmsChannelAvailable() ? 'Send text messages via Africa\'s Talking' : 'SMS service not configured' }}</p>
                                </div>

                                {{-- WhatsApp --}}
                                <div class="border-2 rounded-lg p-4 cursor-pointer transition-colors {{ in_array('whatsapp', $selected_channels) ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }}"
                                    wire:click="toggleChannel('whatsapp')">
                                    <div class="flex items-center gap-2 mb-3">
                                        <input type="checkbox" {{ in_array('whatsapp', $selected_channels) ? 'checked' : '' }}
                                            class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                                        <svg class="w-5 h-5 {{ in_array('whatsapp', $selected_channels) ? 'text-rose-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    </div>
                                    <h5 class="font-medium text-gray-900 text-sm">WhatsApp</h5>
                                    <p class="text-xs text-gray-500 mt-0.5">Send messages via WhatsApp Business</p>
                                </div>
                            </div>

                            @if(!empty($selected_channels))
                                <div class="flex items-center gap-3 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-700">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Selected: {{ implode(', ', array_map('ucfirst', $selected_channels)) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- STEP 3: MESSAGE COMPOSITION --}}
                @if($currentStep === 3)
                    <div class="space-y-5">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-800">Compose Your Message</h4>
                            <x-fill-sample-data-btn />
                        </div>

                        @if(in_array('email', $selected_channels))
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Subject</label>
                                <input wire:model="subject" type="text" placeholder="Enter email subject..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Message Content</label>
                            <textarea wire:model="message_content" rows="6" placeholder="Type your message here..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                            <p class="text-xs text-gray-400 mt-1">{{ strlen($message_content) }} characters</p>
                        </div>

                        {{-- Preview --}}
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">Preview</h5>
                            <div class="space-y-3">
                                @foreach($selected_channels as $channel)
                                    <div class="border border-gray-200 rounded-lg bg-white p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-semibold text-gray-600 uppercase">{{ ucfirst($channel) }}</span>
                                        </div>
                                        @if($channel === 'email' && $subject)
                                            <p class="text-xs font-medium text-gray-800 mb-1">Subject: {{ $subject }}</p>
                                        @endif
                                        <p class="text-sm text-gray-700">{{ $message_content ?: 'No message content yet...' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-center pt-2">
                            <button wire:click="sendMessage" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-8 py-3 rounded-lg text-sm font-semibold text-white transition-colors disabled:opacity-50"
                                style="background:#982B55;">
                                <span wire:loading.remove wire:target="sendMessage" class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Send Messages
                                </span>
                                <span wire:loading wire:target="sendMessage" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Preparing to Send...
                                </span>
                            </button>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Navigation --}}
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 rounded-b-xl flex justify-between">
                <button wire:click="previousStep"
                    @if($currentStep === 1) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </button>

                @if($currentStep < 3)
                    <button wire:click="nextStep"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
                        style="background:#982B55;">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @endif
            </div>
        </div>

    </div>

    {{-- Progress Modal --}}
    @if($show_progress_modal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:poll.500ms>
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-lg w-full mx-4">
                <div class="text-center">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">
                        @if($sending_status === 'sending')
                            Sending Messages...
                        @elseif($sending_status === 'completed')
                            Messages Sent!
                        @else
                            Sending Failed
                        @endif
                    </h3>

                    @if($sending_status === 'sending')
                        <div class="w-full bg-gray-100 rounded-full h-2.5 mb-4">
                            <div class="h-2.5 rounded-full transition-all duration-500" style="width:{{ $sending_progress }}%; background:#982B55;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mb-4">
                            <span>{{ $sending_progress }}% complete</span>
                            <span>{{ $sent_messages + $failed_messages }} / {{ $total_messages }}</span>
                        </div>

                        @if($current_recipient)
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 mb-4 text-left">
                                Sending via <strong>{{ $current_channel }}</strong> to <strong>{{ $current_recipient }}</strong>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                                <div class="text-lg font-bold text-green-600">{{ $sent_messages }}</div>
                                <div class="text-xs text-green-600">Sent</div>
                            </div>
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-center">
                                <div class="text-lg font-bold text-red-600">{{ $failed_messages }}</div>
                                <div class="text-xs text-red-600">Failed</div>
                            </div>
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-center">
                                <div class="text-lg font-bold text-gray-600">{{ $total_messages - $sent_messages - $failed_messages }}</div>
                                <div class="text-xs text-gray-500">Remaining</div>
                            </div>
                        </div>

                        @if(!empty($progress_details))
                            <div class="text-left max-h-28 overflow-y-auto bg-gray-50 rounded-lg p-3">
                                <p class="text-xs font-medium text-gray-600 mb-2">Recent Activity:</p>
                                @foreach(array_slice(array_reverse($progress_details), 0, 5) as $detail)
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="{{ $detail['status'] === 'success' ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $detail['status'] === 'success' ? '✓' : '✗' }}
                                            {{ $detail['recipient'] }} ({{ ucfirst($detail['channel']) }})
                                        </span>
                                        <span class="text-gray-400">{{ $detail['time'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if($sending_status === 'completed')
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $sent_messages }}</div>
                                <div class="text-xs text-green-600">Successfully Sent</div>
                            </div>
                            @if($failed_messages > 0)
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-red-600">{{ $failed_messages }}</div>
                                    <div class="text-xs text-red-600">Failed</div>
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-3 justify-center">
                            <button wire:click="resetComponent"
                                class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">
                                Send Another Message
                            </button>
                            <button wire:click="closeProgressModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Close
                            </button>
                        </div>
                    @endif

                    @if($sending_status === 'failed')
                        <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <p class="text-sm text-red-700 mb-4">An error occurred while sending messages.</p>
                        <button wire:click="closeProgressModal"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Close
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session()->has('error'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 shadow-lg">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 shadow-lg">
            {{ session('success') }}
        </div>
    @endif
</div>
