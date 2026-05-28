<div class="min-h-full py-6 px-4 md:px-8">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">People</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Import Persons</h1>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button wire:click="downloadTemplate"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download Template
                </button>
                <a href="{{ route('persons.all') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
                    style="background:#982B55;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    All Persons
                </a>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Alerts --}}
        @if (session()->has('message'))
            <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @error('import')
            <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-medium">Import Error</p>
                    <pre class="mt-1 whitespace-pre-wrap font-mono text-xs">{{ $message }}</pre>
                </div>
            </div>
        @enderror

        {{-- Import Configuration --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Import Configuration</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if ($isSuperAdmin)
                        Choose the target organization and configure how duplicates are handled.
                    @else
                        Persons will be affiliated to
                        <span class="font-medium text-gray-700">{{ $currentOrganization->display_name ?? ($currentOrganization->legal_name ?? 'your organization') }}</span>.
                    @endif
                </p>
            </div>
            <div class="p-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Organization --}}
                    @if ($isSuperAdmin)
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                Target Organization <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="selectedOrganizationId"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-transparent">
                                <option value="">Select Organization</option>
                                @foreach ($availableOrganizations as $org)
                                    <option value="{{ $org['id'] }}">{{ $org['display_name'] ?? $org['legal_name'] }}</option>
                                @endforeach
                            </select>
                            @error('selectedOrganizationId')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Target Organization</label>
                            <input type="text"
                                value="{{ $currentOrganization->display_name ?? ($currentOrganization->legal_name ?? 'Current Organization') }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
                                readonly>
                            <p class="text-xs text-gray-500 mt-1.5">Imported persons are automatically affiliated to your organization.</p>
                        </div>
                    @endif

                    {{-- Fallback Role --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Fallback Role Type</label>
                        <select wire:model="defaultRoleType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-transparent">
                            @foreach ($availableRoles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1.5">Used only when <code class="bg-gray-100 px-1 rounded">role_type</code> column is empty or missing in Excel.</p>
                        @error('defaultRoleType')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Duplicate Handling --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-2">Duplicate Handling</label>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" wire:model="skipDuplicates"
                                class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-300">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Skip duplicates (keep existing records)</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" wire:model="updateExisting"
                                class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-300">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Update existing records with new data</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- File Upload --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Upload File</h3>
            </div>
            <div class="p-5">
                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                    Select CSV or Excel File <span class="text-red-500">*</span>
                </label>
                <input type="file" wire:model="importFile" accept=".csv,.xlsx,.xls"
                    class="block w-full text-sm text-gray-600
                        file:mr-3 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-xs file:font-medium file:text-white
                        file:cursor-pointer file:transition-colors
                        file:[background:#982B55] file:hover:[background:#7a2244]
                        border border-gray-300 rounded-lg cursor-pointer
                        focus:outline-none focus:ring-2 focus:ring-rose-300">
                @error('importFile')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-400 mt-1.5">Supported formats: CSV, Excel (.xlsx, .xls). Max size: 10MB</p>

                @if ($importFile)
                    <div class="mt-3 flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $importFile->getClientOriginalName() }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($importFile->getSize() / 1024, 1) }} KB</p>
                            </div>
                        </div>
                        <button wire:click="resetImport"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div wire:loading wire:target="importFile" class="mt-3 flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <span class="loading loading-spinner loading-sm text-amber-500"></span>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Analyzing file...</p>
                            <p class="text-xs text-amber-600">Validating data structure</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- File Preview --}}
        @if ($showPreview || $importFile)
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">File Preview</h3>
                </div>
                <div class="p-5">
                    <div wire:loading wire:target="importFile" class="flex flex-col items-center justify-center py-10 gap-3">
                        <span class="loading loading-ring loading-lg" style="color:#982B55;"></span>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-700">Generating preview...</p>
                            <p class="text-xs text-gray-400">This may take a moment for large files</p>
                        </div>
                    </div>

                    <div wire:loading.remove wire:target="importFile">
                        @if (!empty($validationErrors))
                            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 mb-4">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">Validation Issues Found</p>
                                    <ul class="mt-1 space-y-0.5 text-xs list-disc list-inside">
                                        @foreach ($validationErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if (!empty($previewData))
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left font-medium">Row</th>
                                            <th class="px-4 py-2.5 text-left font-medium">Name</th>
                                            <th class="px-4 py-2.5 text-left font-medium">Email</th>
                                            <th class="px-4 py-2.5 text-left font-medium">Phone</th>
                                            <th class="px-4 py-2.5 text-left font-medium">Role</th>
                                            <th class="px-4 py-2.5 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach (array_slice($previewData, 0, 10) as $index => $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2.5 text-gray-500">{{ $index + 3 }}</td>
                                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $row['given_name'] ?? '' }} {{ $row['family_name'] ?? '' }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $row['email'] ?? '—' }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $row['phone'] ?? '—' }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $row['role_type'] ?? $defaultRoleType }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if (isset($row['_validation_errors']) && !empty($row['_validation_errors']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Error</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Valid</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if (count($previewData) > 10)
                                <p class="text-xs text-gray-400 mt-2">Showing first 10 of {{ count($previewData) }} rows.</p>
                            @endif

                            <div class="flex justify-end gap-2 mt-4">
                                <button wire:click="resetImport"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button wire:click="importPersons"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50"
                                    style="background:#982B55;"
                                    @if ($isProcessing || !empty($validationErrors)) disabled @endif>
                                    @if ($isProcessing)
                                        <span class="loading loading-spinner loading-xs"></span>
                                        Processing...
                                    @else
                                        Import {{ count($previewData) }} Persons
                                    @endif
                                </button>
                            </div>
                        @elseif ($showPreview)
                            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">No data found</p>
                                    <p class="text-xs mt-0.5">The file appears to be empty or contains only headers. Please check your file and try again.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Import Results --}}
        @if ($showResults && ($importResults || !empty($validationErrors)))
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Import Results</h3>
                </div>
                <div class="p-5 space-y-4">

                    @if (!empty($validationErrors) && empty($importResults))
                        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium">Import Failed — Validation Errors</p>
                                <p class="text-xs mt-0.5">Fix the errors below in your file and try again.</p>
                            </div>
                        </div>

                        <div class="border border-red-200 rounded-lg overflow-hidden">
                            <div class="bg-red-50 px-4 py-2.5 text-xs font-medium text-red-700 border-b border-red-200">
                                Validation Errors ({{ count($validationErrors) }})
                            </div>
                            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-red-100">
                                        @foreach ($validationErrors as $error)
                                            <tr>
                                                <td class="px-4 py-2 font-mono text-xs text-red-800">{{ $error }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($importResults)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-700">{{ $importResults['summary']['success'] ?? 0 }}</div>
                                <div class="text-xs text-green-600 font-medium mt-0.5">Successful</div>
                            </div>
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-center">
                                <div class="text-2xl font-bold text-amber-700">{{ $importResults['summary']['skipped'] ?? 0 }}</div>
                                <div class="text-xs text-amber-600 font-medium mt-0.5">Skipped</div>
                            </div>
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-center">
                                <div class="text-2xl font-bold text-red-700">{{ $importResults['summary']['failed'] ?? 0 }}</div>
                                <div class="text-xs text-red-600 font-medium mt-0.5">Failed</div>
                            </div>
                            <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-center">
                                <div class="text-2xl font-bold text-rose-700">{{ $importResults['summary']['total'] ?? 0 }}</div>
                                <div class="text-xs text-rose-600 font-medium mt-0.5">Total</div>
                            </div>
                        </div>

                        @if (!empty($importResults['details']))
                            <details class="border border-gray-200 rounded-lg overflow-hidden group">
                                <summary class="flex items-center justify-between px-4 py-3 bg-gray-50 text-sm font-medium text-gray-700 cursor-pointer select-none hover:bg-gray-100 transition-colors">
                                    View Detailed Results
                                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider sticky top-0">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left font-medium">Row</th>
                                                <th class="px-4 py-2.5 text-left font-medium">Name</th>
                                                <th class="px-4 py-2.5 text-left font-medium">Status</th>
                                                <th class="px-4 py-2.5 text-left font-medium">Message</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($importResults['details'] as $detail)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $detail['row'] ?? '' }}</td>
                                                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $detail['name'] ?? '' }}</td>
                                                    <td class="px-4 py-2.5">
                                                        @if ($detail['status'] === 'success')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Success</span>
                                                        @elseif($detail['status'] === 'skipped')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Skipped</span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Failed</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-gray-600">{{ $detail['message'] ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        @endif
                    @endif

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="resetImport"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Import Another File
                        </button>
                        <a href="{{ route('persons.all') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background:#982B55;">
                            View All Persons
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Processing Overlay --}}
    @if ($isProcessing)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center gap-4 min-w-[260px]">
                <span class="loading loading-spinner loading-md" style="color:#982B55;"></span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Processing import...</p>
                    <p class="text-xs text-gray-500 mt-0.5">Please wait while we process your file</p>
                </div>
            </div>
        </div>
    @endif
</div>
