<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Communication</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Send Message</h1>
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 md:px-8">
        <livewire:communication.send-message />
    </div>
</x-app-layout>
