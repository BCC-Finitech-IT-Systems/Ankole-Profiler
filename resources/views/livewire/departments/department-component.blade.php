<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Departments</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if (session()->has('message'))
            <div class="alert alert-success">
                <span>{{ session('message') }}</span>
            </div>
        @endif

        {{-- Organization Admin: My Department & Project Departments --}}
        @if($isOrgAdmin)
            {{-- My Department Info --}}
            <div class="bg-base-100 border border-base-300 rounded-lg overflow-hidden">
                <div class="p-4">
                    <h3 class="font-semibold text-base-content mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                        My Department(s)
                    </h3>
                </div>

                @if($orgAdminDepartments->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Sub-Categories</th>
                                    <th>Organization</th>
                                    <th>Code</th>
                                    <th>Admin</th>
                                    <th>Projects</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orgAdminDepartments as $dept)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="font-medium">{{ $dept->name }}</td>
                                        <td>
                                            @if($dept->subCategories->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($dept->subCategories as $subCat)
                                                        <span class="badge badge-outline badge-xs">{{ $subCat->name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $dept->organization?->legal_name ?? 'N/A' }}</td>
                                        <td>{{ $dept->code ?? '—' }}</td>
                                        <td>{{ $dept->admin?->name ?? 'Unassigned' }}</td>
                                        <td>{{ $dept->projects_count }}</td>
                                        <td>
                                            @if($dept->is_active)
                                                <span class="badge badge-success badge-sm">Active</span>
                                            @else
                                                <span class="badge badge-ghost badge-sm">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4">
                        <p class="text-sm text-base-content/50 italic">You are not affiliated with any department.</p>
                    </div>
                @endif
            </div>

            {{-- Project Departments: Organizations grouped by sub-category --}}
            <div class="bg-base-100 border border-primary/30 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-base-content mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        My Project Departments
                    </h3>
                    <div class="w-64">
                        <div class="flex gap-2">
                            <input type="text" class="input input-bordered input-sm w-full" placeholder="Search organizations..." wire:model.live.debounce.300ms="projectDeptSearch">
                            <button type="button" class="btn btn-sm btn-ghost" wire:click="clearProjectDeptSearch" title="Clear search" @if(empty($projectDeptSearch)) disabled @endif>
                               clear search <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-base-content/60 mb-4">Organizations matching your department sub-categories.</p>

                @if($orgAdminOrganizations->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($orgAdminOrganizations as $category => $orgs)
                            <div class="border border-base-300 rounded-lg overflow-hidden">
                                <div class="bg-base-200 px-4 py-2 flex items-center justify-between">
                                    <span class="font-medium text-sm">{{ $category }}</span>
                                    <span class="badge badge-primary badge-sm">{{ $orgs->count() }} {{ Str::plural('organization', $orgs->count()) }}</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="table table-sm w-full">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Code</th>
                                                <th>Registration number</th>
                                                <th>Name</th>
                                                <th>Address</th>
                                                <th>Contact</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orgs as $org)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $org->code ?? '—' }}</td>
                                                    <td>{{ $org->registration_number ?? '—' }}</td>
                                                    <td class="font-medium">
                                                        <button type="button" class="link link-primary" wire:click="showOrganizationPersons({{ $org->id }})">
                                                            {{ $org->legal_name }}
                                                        </button>
                                                    </td>
                                                    <td>{{ $org->address ?? '—' }}</td>
                                                    <td>{{ $org->contact_email ?? '—' }}
                                                        @if($org->contact_phone)
                                                            <br>{{ $org->contact_phone ?? '—' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($org->is_active)
                                                            <span class="badge badge-success badge-xs">Active</span>
                                                        @else
                                                            <span class="badge badge-ghost badge-xs">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-base-content/50">
                        <p class="text-sm italic">No organizations match your department sub-categories yet.</p>
                        <p class="text-xs mt-1">Add sub-categories to your department to see matching organizations here.</p>
                    </div>
                @endif
            </div>
        @endif

        @if(!$isOrgAdmin)
        <div class="bg-base-100 border border-base-300 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-base-content">Departments</h3>

                @if($canCreateDepartments)
                    <button type="button" class="btn btn-sm btn-primary" wire:click="openCreateModal">
                        Create Department
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="input input-bordered w-full"
                        placeholder="Search department, code, organization..."
                    >
                </div>

                <div>
                    <select wire:model.live="organizationFilter" class="select select-bordered w-full">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->legal_name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="label cursor-pointer justify-start md:justify-end gap-2">
                    <input type="checkbox" class="checkbox checkbox-sm" wire:model.live="includeInactive" />
                    <span class="label-text">Include inactive</span>
                </label>
            </div>
        </div>

        <div class="bg-base-100 border border-base-300 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Sub-Categories</th>
                            <th>Organization</th>
                            <th>Code</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td>{{ $departments->firstItem() + $loop->index }}</td>
                                <td class="font-medium">{{ $department->name }}</td>
                                <td>
                                    @if($department->subCategories->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($department->subCategories as $subCategory)
                                                <span class="badge badge-outline">{{ $subCategory->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $department->organization?->legal_name ?? 'N/A' }}</td>
                                <td>{{ $department->code ?? '—' }}</td>
                                <td>{{ $department->admin?->name ?? 'Unassigned' }}</td>
                                <td>
                                    @if($department->is_active)
                                        <span class="badge badge-success badge-sm">Active</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if($canEditDepartments)
                                            <button type="button" class="btn btn-xs" wire:click="openEditModal({{ $department->id }})">
                                                Edit
                                            </button>
                                        @endif

                                        @if($canDeleteDepartments)
                                            <button type="button" class="btn btn-xs btn-error btn-outline" wire:click="confirmDeleteDepartment({{ $department->id }})">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-base-content/70">No departments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $departments->links() }}
        </div>
        @endif

        @if($showCreateModal)
            <div class="modal modal-open">
                <div class="modal-box max-w-2xl">
                    <h3 class="font-bold text-lg mb-4">Create Department</h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label"><span class="label-text">Organization</span></label>
                                <select wire:model="createForm.organization_id" class="select select-bordered w-full">
                                    <option value="">Select organization</option>
                                    @foreach($organizations as $organization)
                                        <option value="{{ $organization->id }}">{{ $organization->legal_name }}</option>
                                    @endforeach
                                </select>
                                @error('createForm.organization_id')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Department Name</span></label>
                                <input type="text" wire:model="createForm.name" class="input input-bordered w-full" placeholder="Department name">
                                @error('createForm.name')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label"><span class="label-text">Code</span></label>
                                <input type="text" wire:model="createForm.code" class="input input-bordered w-full" placeholder="Optional code">
                                @error('createForm.code')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Sub-Categories</span></label>
                                <input type="text" wire:model="createForm.sub_categories_input" class="input input-bordered w-full" placeholder="Comma separated e.g. Secondary, Primary, Urban">
                                @error('createForm.sub_categories_input')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Department Admin</span></label>
                                <select wire:model="createForm.admin_user_id" class="select select-bordered w-full">
                                    <option value="">Unassigned</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                    @endforeach
                                </select>
                                @error('createForm.admin_user_id')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="label"><span class="label-text">Description</span></label>
                            <textarea wire:model="createForm.description" class="textarea textarea-bordered w-full" rows="3" placeholder="Optional description"></textarea>
                            @error('createForm.description')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <label class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm" wire:model="createForm.is_active" />
                            <span class="label-text">Active</span>
                        </label>
                    </div>

                    <div class="modal-action">
                        <button type="button" class="btn" wire:click="closeCreateModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="createDepartment">Save Department</button>
                    </div>
                </div>
                <div class="modal-backdrop" wire:click="closeCreateModal"></div>
            </div>
        @endif

        @if($showEditModal)
            <div class="modal modal-open">
                <div class="modal-box max-w-2xl">
                    <h3 class="font-bold text-lg mb-4">Edit Department</h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label"><span class="label-text">Organization</span></label>
                                <select wire:model="editForm.organization_id" class="select select-bordered w-full">
                                    <option value="">Select organization</option>
                                    @foreach($organizations as $organization)
                                        <option value="{{ $organization->id }}">{{ $organization->legal_name }}</option>
                                    @endforeach
                                </select>
                                @error('editForm.organization_id')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Department Name</span></label>
                                <input type="text" wire:model="editForm.name" class="input input-bordered w-full" placeholder="Department name">
                                @error('editForm.name')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label"><span class="label-text">Code</span></label>
                                <input type="text" wire:model="editForm.code" class="input input-bordered w-full" placeholder="Optional code">
                                @error('editForm.code')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Sub-Categories</span></label>
                                <input type="text" wire:model="editForm.sub_categories_input" class="input input-bordered w-full" placeholder="Comma separated e.g. Secondary, Primary, Urban">
                                @error('editForm.sub_categories_input')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Department Admin</span></label>
                                <select wire:model="editForm.admin_user_id" class="select select-bordered w-full">
                                    <option value="">Unassigned</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                    @endforeach
                                </select>
                                @error('editForm.admin_user_id')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="label"><span class="label-text">Description</span></label>
                            <textarea wire:model="editForm.description" class="textarea textarea-bordered w-full" rows="3" placeholder="Optional description"></textarea>
                            @error('editForm.description')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <label class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm" wire:model="editForm.is_active" />
                            <span class="label-text">Active</span>
                        </label>
                    </div>

                    <div class="modal-action">
                        <button type="button" class="btn" wire:click="closeEditModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="updateDepartment">Update Department</button>
                    </div>
                </div>
                <div class="modal-backdrop" wire:click="closeEditModal"></div>
            </div>
        @endif

        @if($confirmDeleteDepartmentId)
            <div class="modal modal-open">
                <div class="modal-box">
                    <h3 class="font-bold text-lg">Delete Department</h3>
                    <p class="py-3">Are you sure you want to delete this department?</p>
                    <div class="modal-action">
                        <button type="button" class="btn" wire:click="cancelDeleteDepartment">Cancel</button>
                        <button type="button" class="btn btn-error" wire:click="deleteDepartment">Delete</button>
                    </div>
                </div>
                <div class="modal-backdrop" wire:click="cancelDeleteDepartment"></div>
            </div>
        @endif
    </div>
</div>
            @if(!empty($selectedOrganizationPersons))
                <div class="mt-6 card bg-base-100 border border-info/30 shadow-sm">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold mb-2">Persons in Selected Project/Organization</h3>
                        <ul class="list-disc ml-6">
                            @foreach($selectedOrganizationPersons as $person)
                                <li>{{ $person->given_name }} {{ $person->family_name }} ({{ $person->email ?? 'No email' }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
