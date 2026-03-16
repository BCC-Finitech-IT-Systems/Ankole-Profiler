@extends('layouts.app')

@section('content')
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-base-content">Departments</h2>
            <span class="text-sm text-base-content/70">Total: {{ $departments->total() }}</span>
        </div>

        <div class="bg-base-100 border border-base-300 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Organization</th>
                            <th>Code</th>
                            <th>Admin</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td class="font-medium">{{ $department->name }}</td>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-base-content/70">
                                    No departments found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $departments->links() }}
        </div>
    </div>
@endsection
