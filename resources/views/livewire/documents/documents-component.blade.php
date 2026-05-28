<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Person Registry</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Documents</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @if(count($documents) > 0)
                <ul class="divide-y divide-gray-100">
                    @foreach ($documents as $document)
                        <li class="flex items-center gap-3 px-6 py-4">
                            <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <a href="{{ asset($document->path) }}" target="_blank"
                               class="text-sm font-medium text-gray-800 hover:text-rose-600 transition-colors">
                                {{ $document->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No documents found</p>
                    <p class="text-sm text-gray-400 mt-1">Uploaded documents will appear here.</p>
                </div>
            @endif
        </div>
    </div>
</div>
