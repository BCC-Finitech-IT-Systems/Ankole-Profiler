@if(config('app.sample_data_enabled'))
<button type="button"
    wire:click="{{ $action ?? 'fillSampleData' }}"
    class="inline-flex items-center gap-1 px-2 py-1 text-xs text-amber-600 border border-dashed border-amber-300 rounded-md hover:bg-amber-50 transition-colors"
    title="Fill form with sample data">
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.155-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
    </svg>
    Fill sample data
</button>
@endif
