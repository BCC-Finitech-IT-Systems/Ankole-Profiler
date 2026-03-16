@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Persons in Project</h2>
    @if(isset($project))
        <div class="mb-6">
            <h3 class="text-lg font-semibold">Project: {{ $project->name }}</h3>
            <p class="text-base-content/70">Organization: {{ $project->department?->organization?->legal_name ?? '—' }}</p>
        </div>
    @endif
    <div class="bg-base-100 border border-base-300 rounded-lg p-4">
        @if($persons->isNotEmpty())
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($persons as $index => $person)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $person->given_name }} {{ $person->family_name }}</td>
                            <td>{{ $person->email ?? '—' }}</td>
                            <td>{{ $person->pivot->role_title ?? '—' }}</td>
                            <td>{{ $person->pivot->status ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-base-content/70">No persons found for this project.</p>
        @endif
    </div>
</div>
@endsection
