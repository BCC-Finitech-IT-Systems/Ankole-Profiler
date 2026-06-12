{{-- resources/views/relationships/cross-org.blade.php --}}
@extends('layouts.app')

@section('title', 'Cross-Organization Relationships')

@section('content')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">Cross-Organization Relationships</h1>
                    <a href="{{ route('relationships.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('relationships.cross-org') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small" for="search">Search person</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm"
                            value="{{ request('search') }}" placeholder="Name...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small" for="verified">Verification</label>
                        <select name="verified" id="verified" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1" @selected(request('verified') === '1')>Verified</option>
                            <option value="0" @selected(request('verified') === '0')>Unverified</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small" for="relationship_strength">Strength</label>
                        <select name="relationship_strength" id="relationship_strength" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach (['strong' => 'Strong', 'moderate' => 'Moderate', 'weak' => 'Weak'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('relationship_strength') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small" for="impact_min">Min impact</label>
                        <input type="number" step="0.05" min="0" max="1" name="impact_min" id="impact_min"
                            class="form-control form-control-sm" value="{{ request('impact_min') }}">
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
                    Connections ({{ $relationships->total() }})
                </h6>
            </div>
            <div class="card-body p-0">
                @if ($relationships->isEmpty())
                    <p class="text-muted p-4 mb-0">No cross-organization relationships found.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Person</th>
                                    <th>Primary Organization</th>
                                    <th>Secondary Organization</th>
                                    <th>Strength</th>
                                    <th>Impact</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($relationships as $relationship)
                                    <tr>
                                        <td>
                                            @if ($relationship->person)
                                                <a href="{{ route('relationships.person.network', $relationship->person) }}">
                                                    {{ $relationship->person->given_name }} {{ $relationship->person->family_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $relationship->primaryAffiliation?->Organization?->display_name
                                                ?? $relationship->primaryAffiliation?->Organization?->legal_name
                                                ?? 'Unknown' }}
                                            <div class="small text-muted">{{ $relationship->primaryAffiliation?->role_type }}</div>
                                        </td>
                                        <td>
                                            {{ $relationship->secondaryAffiliation?->Organization?->display_name
                                                ?? $relationship->secondaryAffiliation?->Organization?->legal_name
                                                ?? 'Unknown' }}
                                            <div class="small text-muted">{{ $relationship->secondaryAffiliation?->role_type }}</div>
                                        </td>
                                        <td class="text-capitalize">{{ $relationship->relationship_strength }}</td>
                                        <td>{{ number_format($relationship->impact_score * 100, 0) }}%</td>
                                        <td>
                                            @if ($relationship->verified)
                                                <span class="badge bg-success">Verified</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Unverified</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $relationship->created_at?->format('Y-m-d') }}</td>
                                        <td class="text-end">
                                            @unless ($relationship->verified)
                                                <button class="btn btn-sm btn-success"
                                                    onclick="verifyCrossOrg({{ $relationship->id }})">
                                                    Verify
                                                </button>
                                            @endunless
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
        function verifyCrossOrg(id) {
            fetch(`{{ route('relationships.cross-org.verify', '') }}/${id}`, {
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
                        alert(data.message || 'Verification failed.');
                    }
                })
                .catch(() => alert('Verification failed.'));
        }
    </script>
@endsection
