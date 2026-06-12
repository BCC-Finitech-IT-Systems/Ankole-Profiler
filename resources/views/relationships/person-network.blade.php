{{-- resources/views/relationships/person-network.blade.php --}}
@extends('layouts.app')

@section('title', 'Relationship Network')

@section('content')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        Network: {{ $person->given_name }} {{ $person->family_name }}
                        <span class="text-muted small">({{ $person->person_id }})</span>
                    </h1>
                    <div>
                        <a href="{{ route('persons.show', $person->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <a href="{{ route('relationships.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Network statistics --}}
        <div class="row mb-4">
            @foreach ([
                'Personal Relationships' => $networkStats['total_personal_relationships'],
                'Cross-Org Connections' => $networkStats['total_cross_org_relationships'],
                'Verified Relationships' => $networkStats['verified_relationships'],
                'Family Connections' => $networkStats['family_connections'],
                'Organizations' => $networkStats['organization_count'],
            ] as $label => $count)
                <div class="col mb-2">
                    <div class="card shadow h-100 py-2">
                        <div class="card-body py-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ $label }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($count) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            {{-- Personal relationships --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Personal Relationships</h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($personalRelationships->isEmpty())
                            <p class="text-muted p-4 mb-0">No personal relationships recorded.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Related Person</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($personalRelationships as $relationship)
                                            @php
                                                $other = $relationship->person_a_id === $person->id
                                                    ? $relationship->personB
                                                    : $relationship->personA;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if ($other)
                                                        <a href="{{ route('relationships.person.network', $other) }}">
                                                            {{ $other->given_name }} {{ $other->family_name }}
                                                        </a>
                                                    @else
                                                        <span class="text-muted">Unknown</span>
                                                    @endif
                                                </td>
                                                <td>{{ \App\Models\PersonRelationship::getRelationshipTypes()[$relationship->relationship_type] ?? $relationship->relationship_type }}</td>
                                                <td>
                                                    @if ($relationship->verification_status === 'verified')
                                                        <span class="badge bg-success">Verified</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">{{ ucfirst($relationship->verification_status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cross-org connections --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Cross-Organization Connections</h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($crossOrgRelationships->isEmpty())
                            <p class="text-muted p-4 mb-0">No cross-organization connections recorded.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Organizations</th>
                                            <th>Strength</th>
                                            <th>Impact</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($crossOrgRelationships as $relationship)
                                            <tr>
                                                <td class="small">
                                                    {{ $relationship->primaryAffiliation?->Organization?->display_name
                                                        ?? $relationship->primaryAffiliation?->Organization?->legal_name
                                                        ?? 'Unknown' }}
                                                    ↔
                                                    {{ $relationship->secondaryAffiliation?->Organization?->display_name
                                                        ?? $relationship->secondaryAffiliation?->Organization?->legal_name
                                                        ?? 'Unknown' }}
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
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Family network --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Family Network</h6>
                    </div>
                    <div class="card-body">
                        @if (empty($familyNetwork))
                            <p class="text-muted mb-0">No family network discovered.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($familyNetwork as $member)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            @if (is_array($member))
                                                {{ $member['name'] ?? ($member['given_name'] ?? '') . ' ' . ($member['family_name'] ?? '') }}
                                                @if (!empty($member['relationship_type']))
                                                    <span class="text-muted small">— {{ $member['relationship_type'] }}</span>
                                                @endif
                                            @else
                                                {{ $member }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
