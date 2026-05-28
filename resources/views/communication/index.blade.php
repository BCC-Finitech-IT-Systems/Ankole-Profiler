<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Communication</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Communication Center</h1>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 md:px-8">
        <div class="w-full">
            <div class="space-y-6">
                {{-- Quick Actions --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="{{ route('communication.send') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Send Message</h3>
                                <p class="text-sm text-gray-600">Send emails, SMS, or WhatsApp messages</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('communication.history') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Message History</h3>
                                <p class="text-sm text-gray-600">View sent messages and delivery status</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('communication.settings') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Settings</h3>
                                <p class="text-sm text-gray-600">Configure communication providers</p>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- Recent Activity Summary --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Communication Activity</h3>
                        <livewire:communication.message-history />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
