{{-- resources/views/relationships/personal.blade.php --}}
@extends('layouts.app')

@section('title', 'Personal Relationships')

@section('content')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">Personal Relationships</h1>
                    <a href="{{ route('relationships.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('relationships.personal') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small" for="search">Search person</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm"
                            value="{{ request('search') }}" placeholder="Name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small" for="relationship_type">Relationship type</label>
                        <select name="relationship_type" id="relationship_type" class="form-select form-select-sm">
                            <option value="">All types</option>
                            @foreach (\App\Models\PersonRelationship::getRelationshipTypes() as $value => $label)
                                <option value="{{ $value }}" @selected(request('relationship_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small" for="verification_status">Verification</label>
                        <select name="verification_status" id="verification_status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach (['unverified' => 'Unverified', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small" for="confidence_min">Min confidence</label>
                        <input type="number" step="0.05" min="0" max="1" name="confidence_min" id="confidence_min"
                            class="form-control form-control-sm" value="{{ request('confidence_min') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Relationship list --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Relationships ({{ $relationships->total() }})
                </h6>
            </div>
            <div class="card-body p-0">
                @if ($relationships->isEmpty())
                    <p class="text-muted p-4 mb-0">No personal relationships found.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Person A</th>
                                    <th>Person B</th>
                                    <th>Type</th>
                                    <th>Confidence</th>
                                    <th>Status</th>
                                    <th>Discovery</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($relationships as $relationship)
                                    <tr>
                                        <td>
                                            @if ($relationship->personA)
                                                <a href="{{ route('relationships.person.network', $relationship->personA) }}">
                                                    {{ $relationship->personA->given_name }} {{ $relationship->personA->family_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($relationship->personB)
                                                <a href="{{ route('relationships.person.network', $relationship->personB) }}">
                                                    {{ $relationship->personB->given_name }} {{ $relationship->personB->family_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>{{ \App\Models\PersonRelationship::getRelationshipTypes()[$relationship->relationship_type] ?? $relationship->relationship_type }}</td>
                                        <td>{{ number_format($relationship->confidence_score * 100, 0) }}%</td>
                                        <td>
                                            @if ($relationship->verification_status === 'verified')
                                                <span class="badge bg-success">Verified</span>
                                            @elseif ($relationship->verification_status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Unverified</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $relationship->discovery_method }}</td>
                                        <td class="small text-muted">{{ $relationship->created_at?->format('Y-m-d') }}</td>
                                        <td class="text-end">
                                            @if ($relationship->verification_status === 'unverified')
                                                <button class="btn btn-sm btn-success"
                                                    onclick="decideRelationship({{ $relationship->id }}, 'verify')">
                                                    Verify
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="decideRelationship({{ $relationship->id }}, 'reject')">
                                                    Reject
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @if ($relationships->hasPages())
                <div class="card-footer">
                    {{ $relationships->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function decideRelationship(id, action) {
            const base = action === 'verify' ?
                `{{ route('relationships.personal.verify', '') }}/${id}` :
                `{{ route('relationships.personal.reject', '') }}/${id}`;

            fetch(base, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Action failed.');
                    }
                })
                .catch(() => alert('Action failed.'));
        }
    </script>
@endsection
