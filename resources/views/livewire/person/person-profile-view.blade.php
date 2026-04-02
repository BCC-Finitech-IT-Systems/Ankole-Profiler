<div class="min-h-screen bg-gray-50">
    <!-- Header -->


    <!-- Profile Summary -->
    <div class="max-w-6xl mt-6">
        <div class="flex flex-col md:flex-row gap-6">

            <div class="flex-1">
                <div class="flex flex-wrap gap-4 mb-4">
                    <div class="bg-white rounded-xl shadow p-4 flex-1 min-w-[180px]">
                        <div class="min-h-screen bg-[#fafbfc]">
                            <!-- Header -->
                            <div class="bg-white border-b px-8 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-[15px] text-[#8b8b8b]">
                                    <a href="#" class="hover:underline">Back</a>
                                    <span>/</span>
                                    <a href="#" class="hover:underline">Members</a>
                                    <span>/</span>
                                    <span class="font-semibold text-[#232323]">{{ $person->given_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">Filter</button>
                                    <input type="text" class="border rounded px-3 py-2 text-[15px] w-56"
                                        value="12/02/2024 - 12/03/2024" />
                                    <button
                                        class="bg-[#ff5c1a] text-white px-6 py-2 rounded hover:bg-[#ff7c3a] text-[15px]">+
                                        Create</button>
                                    <button
                                        class="ml-2 border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">
                                        <i class="fas fa-cog"></i></button>
                                </div>
                            </div>

                            <!-- Profile Summary -->
                            <div class="max-w-7xl mt-8">
                                <div class="flex flex-col md:flex-row gap-8">
                                    <div
                                        class="flex flex-col items-center md:items-start bg-white rounded-xl shadow p-8 w-full md:w-1/4">
                                        <div
                                            class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden mb-3">
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="text-center md:text-left w-full">
                                            <div class="text-xl font-bold text-[#232323]">
                                                {{ optional($person->user)->name ?? ($person->full_name ?? $person->given_name . ' ' . $person->family_name) }}
                                            </div>
                                            <div class="text-[15px] text-[#8b8b8b] mb-2">
                                                @if (optional($person->user)->email)
                                                    <div>{{ $person->user->email }}</div>
                                                @else
                                                    No email
                                                @endif
                                            </div>
                                            <div class="flex flex-col gap-2 text-[15px] mb-4">
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">About:</span> <span
                                                        class="text-[#8b8b8b]">{{ $person->classification ?? 'N/A' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Organization:</span> <span
                                                        class="text-[#8b8b8b]">{{ optional($person->affiliations->first())->Organization->name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Payroll</span> <span
                                                        class="text-[#8b8b8b]">N/A</span></div>
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Active projects</span>
                                                    <span class="text-[#8b8b8b]">N/A</span></div>

                                            </div>
                                            <div class="flex flex-col gap-2 text-[15px] mb-4">
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Location</span> <span
                                                        class="text-[#8b8b8b]">{{ $person->city ?? 'N/A' }},
                                                        {{ $person->country ?? '' }}</span></div>
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Phone</span>
                                                    <span class="text-[#8b8b8b]">
                                                        @if ($person->phones->count())
                                                            @foreach ($person->phones as $phone)
                                                                <div>{{ $phone->number }} @if ($phone->is_primary)
                                                                        <span
                                                                            class="text-xs text-green-600">(primary)</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            No phone
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Joined at</span> <span
                                                        class="text-[#8b8b8b]">{{ $person->created_at ? $person->created_at->format('d/m/Y') : 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-2 text-[15px] mb-4">
                                                <div class="flex items-center gap-2"><span
                                                        class="font-semibold text-[#232323]">Socials</span> <span
                                                        class="text-[#8b8b8b]">N/A</span></div>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex gap-2 w-full">
                                            <button
                                                class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 flex-1">Follow</button>
                                            <button
                                                class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 flex-1">Call</button>
                                            <button
                                                class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 flex-1">Message</button>
                                        </div>

                                        <div class="mt-0">
                                            <div id='affilation-id' class="font-semibold text-[#232323] text-sm mb-1">Affiliations:</div>
                                            <ul class="list-decimal list-inside text-[#8b8b8b] text-sm mb-2">
                                                @forelse($person->affiliations as $affiliation)
                                                       <li class="flex items-center gap-2 mb-1">
                                                           <div class="mask mask-squircle w-12 h-12 bg-gray-200 flex items-center justify-center">
                                                               <span class="text-lg font-bold text-[#ff5c1a]">
                                                                   {{ strtoupper(substr(optional($affiliation->Organization)->name ?? 'N/A', 0, 1)) }}
                                                               </span>
                                                           </div>
                                                           <span class="font-semibold">{{ optional($affiliation->Organization)->name ?? 'N/A' }}</span>
                                                           <span class="font-semibold">{{ $affiliation->affiliation_id ?? 'N/A' }}</span>
                                                           @if ($affiliation->role_title)
                                                               <span class="ml-1 text-xs text-[#ff5c1a]">({{ $affiliation->role_title }})</span>
                                                           @endif
                                                       </li>
                                                @empty
                                                    <li>No affiliations</li>
                                                @endforelse
                                            </ul>
                                                <a href="{{ route('organization-units.index') }}" class="inline-block px-3 py-1 bg-green-500 text-white rounded-full text-xs font-semibold align-middle hover:bg-green-600 transition">View Organization Units</a>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="flex flex-wrap gap-6 mb-6">

                                            <div class="bg-white rounded-xl shadow p-6 flex-1 min-w-[220px]">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="bg-[#ff5c1a] text-white rounded p-1"><i
                                                            class="fas fa-chart-line"></i></span>
                                                    <span class="text-[15px] text-[#8b8b8b]">Affiliations</span>
                                                </div>
                                                <div class="text-3xl font-bold text-[#232323]">
                                                    {{ $person->affiliations->count() }}</div>
                                                <div class="text-xs text-[#8b8b8b] mt-1">Active:
                                                    {{ $person->activeAffiliations()->count() }}</div>
                                            </div>
                                            <div class="bg-white rounded-xl shadow p-6 flex-1 min-w-[220px]">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="bg-[#ff5c1a] text-white rounded p-1"><i
                                                            class="fas fa-tasks"></i></span>
                                                    <span class="text-[15px] text-[#8b8b8b]">Phone Numbers</span>
                                                </div>
                                                <div class="text-3xl font-bold text-[#232323]">
                                                    {{ $person->phones->count() }}</div>
                                                <div class="text-xs text-[#8b8b8b] mt-1">Primary:
                                                    {{ optional($person->primaryPhone())->number ?? 'N/A' }}</div>
                                            </div>
                                            <div class="bg-white rounded-xl shadow p-6 flex-1 min-w-[220px]">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="bg-[#ff5c1a] text-white rounded p-1"><i
                                                            class="fas fa-users"></i></span>
                                                    <span class="text-[15px] text-[#8b8b8b]">Email Addresses</span>
                                                </div>
                                                <div class="text-3xl font-bold text-[#232323]">
                                                    {{ $person->emailAddresses->count() }}</div>
                                                <div class="text-xs text-[#8b8b8b] mt-1">Primary:
                                                     @if (optional($person->user)->email)
                                                    <div>{{ $person->user->email }}</div>
                                                @else
                                                    No email
                                                @endif
                                                    </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 border-b pb-2 mb-4 text-[15px]">
                                            <button
                                                class="px-4 py-2 border-b-2 border-[#ff5c1a] font-semibold text-[#232323]">Overview</button>
                                            <button class="px-4 py-2 text-[#8b8b8b] hover:text-[#232323]">Work
                                                History</button>
                                            <button class="px-4 py-2 text-[#8b8b8b] hover:text-[#232323]">Attendance &
                                                Schedule</button>
                                            <button class="px-4 py-2 text-[#8b8b8b] hover:text-[#232323]">Tasks &
                                                Projects</button>
                                            <button
                                                class="px-4 py-2 text-[#8b8b8b] hover:text-[#232323]">Payroll</button>
                                        </div>
                                        <!-- Table -->
                                        <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
                                            <div class="flex flex-wrap gap-2 mb-4">
                                                <input type="text" class="border rounded px-3 py-2 text-[15px]"
                                                    placeholder="Search for anything" />
                                                <button
                                                    class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">Filter</button>
                                                <button
                                                    class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">Sort</button>
                                                <button
                                                    class="bg-[#ff5c1a] text-white px-6 py-2 rounded hover:bg-[#ff7c3a] text-[15px]">+
                                                    Register</button>
                                                <button
                                                    class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">Export</button>
                                                <button
                                                    class="border px-4 py-2 rounded text-[#232323] hover:bg-gray-100 text-[15px]">Layout</button>
                                            </div>
                                            <table class="min-w-full text-[15px] mt-2">
                                                <thead>
                                                    <tr class="text-[#8b8b8b] border-b">
                                                        <th class="py-3 px-2 text-left font-semibold"><input
                                                                type="checkbox" /></th>
                                                        <th class="py-3 px-2 text-left font-semibold"># Ticket</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Assigned To</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Priority</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Status</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Customer</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Last invoice</th>
                                                        <th class="py-3 px-2 text-left font-semibold">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($person->affiliations as $affiliation)
                                                        <tr class="border-b hover:bg-[#f6f6f6]">
                                                            <td class="py-3 px-2"><input type="checkbox" /></td>
                                                            <td class="py-3 px-2">{{ $affiliation->id }}</td>
                                                            <td class="py-3 px-2">
                                                                {{ $affiliation->role_title ?? 'N/A' }}</td>
                                                            <td class="py-3 px-2">
                                                                <span
                                                                    class="bg-[#ffeaea] text-[#ff5c1a] px-3 py-1 rounded-full font-semibold">{{ $affiliation->role_type ?? 'N/A' }}</span>
                                                            </td>
                                                            <td class="py-3 px-2">
                                                                <span
                                                                    class="bg-[#eaffea] text-[#1ab34a] px-3 py-1 rounded-full font-semibold">{{ $affiliation->status ?? 'N/A' }}</span>
                                                            </td>
                                                            <td class="py-3 px-2 flex items-center gap-2">
                                                                <span
                                                                    class="w-7 h-7 rounded-full bg-gray-200 inline-block"></span>
                                                                {{ optional($affiliation->Organization)->name ?? 'N/A' }}
                                                            </td>
                                                            <td class="py-3 px-2">
                                                                {{ $affiliation->start_date ? \Carbon\Carbon::parse($affiliation->start_date)->format('d/m/y') : 'N/A' }}
                                                            </td>
                                                            <td class="py-3 px-2"><button
                                                                    class="text-[#8b8b8b] text-xl"><i
                                                                        class="fas fa-ellipsis-h"></i></button></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
