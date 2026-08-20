<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Organizations Mgt</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Import Organizations</h1>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('organizations.index') }}" class="btn btn-sm btn-ghost gap-1.5">
                    Back to Institutions
                </a>
                <a href="{{ route('organizations.template') }}" target="_blank"
                   class="btn btn-sm btn-ghost gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Template
                </a>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Upload card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Upload File</h2>
            <form wire:submit.prevent="import" class="flex flex-col sm:flex-row items-start gap-4">
                <div class="flex-1">
                    <input type="file" wire:model="file" accept=".csv,.xlsx"
                           class="file-input file-input-bordered w-full">
                    @error('file') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-sm border-0 text-white shrink-0" style="background:#982B55;">
                    Import Organizations
                </button>
            </form>
            @if($message)
                <div class="alert {{ ($results['summary']['failed'] ?? 0) > 0 ? 'alert-warning' : 'alert-success' }} mt-4">
                    {{ $message }}
                </div>
            @endif

            @if($results)
                <div class="mt-4">
                    <div class="flex gap-4 text-sm mb-3">
                        <span class="badge badge-neutral">Total: {{ $results['summary']['total'] }}</span>
                        <span class="badge badge-success">Success: {{ $results['summary']['success'] }}</span>
                        <span class="badge badge-error">Failed: {{ $results['summary']['failed'] }}</span>
                    </div>
                    @if(count($results['details']))
                        <div class="overflow-x-auto max-h-80 overflow-y-auto border rounded-lg">
                            <table class="table table-xs">
                                <thead class="sticky top-0 bg-white">
                                    <tr>
                                        <th>Row</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results['details'] as $detail)
                                        <tr>
                                            <td>{{ $detail['row'] }}</td>
                                            <td>{{ $detail['name'] }}</td>
                                            <td>
                                                <span class="badge badge-xs {{ $detail['status'] === 'success' ? 'badge-success' : 'badge-error' }}">
                                                    {{ ucfirst($detail['status']) }}
                                                </span>
                                            </td>
                                            <td class="text-gray-500">{{ $detail['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Template builder --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Existing fields --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Template Fields</h4>
                <div class="flex gap-2 mb-4">
                    <button type="button" class="btn btn-xs btn-outline" onclick="selectAllFields(true)">Select All</button>
                    <button type="button" class="btn btn-xs btn-outline" onclick="selectAllFields(false)">Deselect All</button>
                </div>
                <ul class="list-none text-sm space-y-1" id="fields-list">
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> category</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> legal_name</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> display_name</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> code</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> organization_type</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> registration_number</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> contact_email</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> contact_phone</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> date_established</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> address_line_1</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> city</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> country</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> primary_contact_name</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> primary_contact_email</label></li>
                    <li><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="field-checkbox checkbox checkbox-xs" checked onchange="updateSelectedFields()"> primary_contact_phone</label></li>
                </ul>
            </div>

            {{-- Custom fields --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Add Custom Field</h4>
                <form id="custom-field-form" onsubmit="addCustomField(event)">
                    <div class="flex gap-2 mb-4">
                        <input type="text" id="custom-field-input" class="input input-bordered input-sm flex-1"
                               placeholder="Custom field name" required>
                        <button type="submit" class="btn btn-sm btn-success">Add</button>
                    </div>
                </form>
                <ul class="list-none text-sm space-y-1" id="custom-fields-list"></ul>
            </div>

            {{-- Selected fields --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Selected Fields</h4>
                <ul class="list-none text-sm space-y-1 flex-1" id="selected-fields-list"></ul>
                <button type="button" class="btn btn-sm btn-outline mt-4 w-full"
                        style="color:#982B55; border-color:#982B55;"
                        onclick="exportCustomTemplate()">
                    Export Custom Template
                </button>
            </div>
        </div>

        <script>
            function selectAllFields(select) {
                document.querySelectorAll('#fields-list .field-checkbox').forEach(cb => { cb.checked = select; });
                updateSelectedFields();
            }
            function updateSelectedFields() {
                const selectedList = document.getElementById('selected-fields-list');
                selectedList.innerHTML = '';
                document.querySelectorAll('#fields-list .field-checkbox').forEach(cb => {
                    if (cb.checked) {
                        const li = document.createElement('li');
                        li.textContent = cb.parentNode.textContent.trim();
                        selectedList.appendChild(li);
                    }
                });
                document.querySelectorAll('#custom-fields-list input[type=checkbox]').forEach(cb => {
                    if (cb.checked) {
                        const li = document.createElement('li');
                        li.textContent = cb.parentNode.textContent.trim();
                        selectedList.appendChild(li);
                    }
                });
            }
            function addCustomField(e) {
                e.preventDefault();
                const input = document.getElementById('custom-field-input');
                const value = input.value.trim();
                if (value) {
                    const ul = document.getElementById('custom-fields-list');
                    const li = document.createElement('li');
                    li.innerHTML = `<label class="flex items-center gap-2 cursor-pointer"><input type='checkbox' class='checkbox checkbox-xs' checked onchange='updateSelectedFields()'> ${value}</label>`;
                    ul.appendChild(li);
                    input.value = '';
                    updateSelectedFields();
                }
            }
            function exportCustomTemplate() {
                const selected = [];
                document.querySelectorAll('#fields-list .field-checkbox').forEach(cb => {
                    if (cb.checked) selected.push(cb.parentNode.textContent.trim());
                });
                document.querySelectorAll('#custom-fields-list input[type=checkbox]').forEach(cb => {
                    if (cb.checked) selected.push(cb.parentNode.textContent.trim());
                });
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('organizations.export-template') }}";
                form.target = '_blank';
                const csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = "{{ csrf_token() }}";
                form.appendChild(csrf);
                selected.forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = 'fields[]'; input.value = field;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
            document.addEventListener('DOMContentLoaded', updateSelectedFields);
        </script>

    </div>
</div>
