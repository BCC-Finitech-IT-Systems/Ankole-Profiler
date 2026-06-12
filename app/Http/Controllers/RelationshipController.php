<?php

namespace App\Http\Controllers;

use App\Models\PersonRelationship;
use App\Models\CrossOrgRelationship;
use App\Models\Person;
use App\Services\RelationshipDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RelationshipController extends Controller
{
    private RelationshipDiscoveryService $discoveryService;

    public function __construct(RelationshipDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Display relationship management dashboard
     */
    public function index(): View
    {
        $stats = $this->getRelationshipStats();
        $pendingVerifications = $this->getPendingVerifications();
        $recentDiscoveries = $this->getRecentDiscoveries();

        return view('relationships.index', compact('stats', 'pendingVerifications', 'recentDiscoveries'));
    }

    /**
     * Display personal relationships
     */
    public function personalRelationships(Request $request): View
    {
        $query = PersonRelationship::query()
            ->with(['personA', 'personB', 'verifiedBy'])
            ->where('status', 'active');

        // Apply filters
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('relationship_type')) {
            $query->where('relationship_type', $request->relationship_type);
        }

        if ($request->filled('confidence_min')) {
            $query->where('confidence_score', '>=', $request->confidence_min);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('personA', function($subQ) use ($search) {
                    $subQ->where('given_name', 'like', "%{$search}%")
                         ->orWhere('family_name', 'like', "%{$search}%");
                })->orWhereHas('personB', function($subQ) use ($search) {
                    $subQ->where('given_name', 'like', "%{$search}%")
                         ->orWhere('family_name', 'like', "%{$search}%");
                });
            });
        }

        $relationships = $query->orderByDesc('confidence_score')
                              ->paginate(20)
                              ->withQueryString();

        return view('relationships.personal', compact('relationships'));
    }

    /**
     * Display cross-organizational relationships
     */
    public function crossOrgRelationships(Request $request): View
    {
        $query = CrossOrgRelationship::query()
            ->with([
                'person',
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization',
                'verifiedBy'
            ])
            ->where('status', 'active');

        // Apply filters
        if ($request->filled('verified')) {
            $query->where('verified', $request->boolean('verified'));
        }

        if ($request->filled('relationship_strength')) {
            $query->where('relationship_strength', $request->relationship_strength);
        }

        if ($request->filled('impact_min')) {
            $query->where('impact_score', '>=', $request->impact_min);
        }

        if ($request->filled('organization')) {
            $orgId = $request->organization;
            $query->where(function($q) use ($orgId) {
                $q->whereHas('primaryAffiliation', function($subQ) use ($orgId) {
                    $subQ->where('organization_id', $orgId);
                })->orWhereHas('secondaryAffiliation', function($subQ) use ($orgId) {
                    $subQ->where('organization_id', $orgId);
                });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('person', function($q) use ($search) {
                $q->where('given_name', 'like', "%{$search}%")
                  ->orWhere('family_name', 'like', "%{$search}%");
            });
        }

        $relationships = $query->orderByDesc('impact_score')
                              ->paginate(20)
                              ->withQueryString();

        return view('relationships.cross-org', compact('relationships'));
    }

    /**
     * Show person's relationship network
     */
    public function personNetwork(Person $person): View
    {
        // Get personal relationships
        $personalRelationships = PersonRelationship::forPerson($person->id)
            ->active()
            ->with(['personA', 'personB'])
            ->get();

        // Get cross-org relationships
        $crossOrgRelationships = CrossOrgRelationship::forPerson($person->id)
            ->active()
            ->with([
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization'
            ])
            ->get();

        // Get family network
        $familyNetwork = PersonRelationship::findFamilyNetwork($person->id, 3);

        // Get network statistics
        $networkStats = [
            'total_personal_relationships' => $personalRelationships->count(),
            'total_cross_org_relationships' => $crossOrgRelationships->count(),
            'verified_relationships' => $personalRelationships->where('verification_status', 'verified')->count(),
            'family_connections' => collect($familyNetwork)->count(),
            'organization_count' => $person->affiliations()->count(),
        ];

        return view('relationships.person-network', compact(
            'person',
            'personalRelationships',
            'crossOrgRelationships',
            'familyNetwork',
            'networkStats'
        ));
    }

    /**
     * Verify a personal relationship
     */
    public function verifyPersonalRelationship(PersonRelationship $relationship): JsonResponse
    {
        try {
            $relationship->markAsVerified(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Relationship verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify relationship: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a personal relationship
     */
    public function rejectPersonalRelationship(PersonRelationship $relationship): JsonResponse
    {
        try {
            $relationship->markAsRejected(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Relationship rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject relationship: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a cross-org relationship
     */
    public function verifyCrossOrgRelationship(CrossOrgRelationship $relationship): JsonResponse
    {
        try {
            $relationship->markAsVerified(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Cross-org relationship verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify relationship: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run relationship discovery
     */
    public function runDiscovery(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:all,personal,cross-org'
        ]);

        try {
            $results = match($request->type) {
                'personal' => ['personal_relationships' => $this->discoveryService->discoverPersonalRelationships()],
                'cross-org' => ['cross_org_relationships' => $this->discoveryService->discoverCrossOrgRelationships()],
                'all' => $this->discoveryService->discoverAllRelationships(),
            };

            Log::info('Manual relationship discovery completed', $results);

            return response()->json([
                'success' => true,
                'message' => 'Discovery completed successfully',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Manual relationship discovery failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Discovery failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * JSON endpoint: relationship statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getRelationshipStats());
    }

    /**
     * JSON endpoint: pending verifications.
     */
    public function pending(): JsonResponse
    {
        return response()->json($this->getPendingVerifications());
    }

    /**
     * JSON endpoint: a person's relationship network (graph data).
     */
    public function personNetworkData(Person $person): JsonResponse
    {
        $personalRelationships = PersonRelationship::forPerson($person->id)
            ->active()
            ->with(['personA', 'personB'])
            ->get();

        $crossOrgRelationships = CrossOrgRelationship::forPerson($person->id)
            ->active()
            ->with([
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization'
            ])
            ->get();

        return response()->json([
            'person' => $person->only(['id', 'person_id', 'given_name', 'family_name']),
            'personal_relationships' => $personalRelationships,
            'cross_org_relationships' => $crossOrgRelationships,
            'family_network' => PersonRelationship::findFamilyNetwork($person->id, 3),
        ]);
    }

    /**
     * Get relationship statistics for dashboard
     */
    private function getRelationshipStats(): array
    {
        return [
            'total_personal_relationships' => PersonRelationship::active()->count(),
            'verified_personal_relationships' => PersonRelationship::active()->verified()->count(),
            'pending_personal_verifications' => PersonRelationship::active()
                ->where('verification_status', 'unverified')
                ->where('confidence_score', '>=', 0.8)
                ->count(),

            'total_cross_org_relationships' => CrossOrgRelationship::active()->count(),
            'verified_cross_org_relationships' => CrossOrgRelationship::active()->verified()->count(),
            'pending_cross_org_verifications' => CrossOrgRelationship::active()
                ->unverified()
                ->where('impact_score', '>=', 0.7)
                ->count(),

            'high_confidence_discoveries' => PersonRelationship::active()
                ->where('confidence_score', '>=', 0.9)
                ->count(),
            'high_impact_connections' => CrossOrgRelationship::active()
                ->where('impact_score', '>=', 0.8)
                ->count(),
        ];
    }

    // Duplicate getPendingVerifications removed; consolidated implementation exists later in this class.

    /**
     * Get network analysis data for charts
     */
    public function getNetworkAnalysis(): JsonResponse
    {
        // Relationship type distribution
        $relationshipTypes = DB::table('person_relationships')
            ->select('relationship_type', DB::raw('COUNT(*) as count'))
            ->where('status', 'active')
            ->groupBy('relationship_type')
            ->get();

        // Cross-org strength distribution
        $strengthDistribution = DB::table('cross_org_relationships')
            ->select('relationship_strength', DB::raw('COUNT(*) as count'))
            ->where('status', 'active')
            ->groupBy('relationship_strength')
            ->get();

        // Top connected organizations
        $topOrganizations = DB::select("
            SELECT
                o.legal_name,
                COUNT(DISTINCT cor.id) as connection_count,
                AVG(cor.impact_score) as avg_impact
            FROM Organizations o
            JOIN person_affiliations pa ON o.id = pa.organization_id
            JOIN cross_org_relationships cor ON (
                pa.id = cor.primary_affiliation_id OR
                pa.id = cor.secondary_affiliation_id
            )
            WHERE cor.status = 'active'
            GROUP BY o.id, o.legal_name
            ORDER BY connection_count DESC
            LIMIT 10
        ");

        // Discovery method effectiveness
        $discoveryMethods = DB::table('person_relationships')
            ->select(
                'discovery_method',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN verification_status = "verified" THEN 1 ELSE 0 END) as verified'),
                DB::raw('AVG(confidence_score) as avg_confidence')
            )
            ->where('status', 'active')
            ->groupBy('discovery_method')
            ->get();

        return response()->json([
            'relationship_types' => $relationshipTypes,
            'strength_distribution' => $strengthDistribution,
            'top_organizations' => $topOrganizations,
            'discovery_methods' => $discoveryMethods
        ]);
    }

    /**
     * Export relationships data
     */
    public function exportRelationships(Request $request)
    {
        $request->validate([
            'type' => 'required|in:personal,cross-org,all',
            'format' => 'required|in:csv,excel'
        ]);

        try {
            $filename = 'relationships_' . $request->type . '_' . now()->format('Y-m-d_H-i-s');

            if ($request->type === 'personal' || $request->type === 'all') {
                $personalData = $this->getPersonalRelationshipsForExport();
            }

            if ($request->type === 'cross-org' || $request->type === 'all') {
                $crossOrgData = $this->getCrossOrgRelationshipsForExport();
            }

            // Here you would implement the actual export logic
            // For now, returning the data structure
            return response()->json([
                'success' => true,
                'message' => 'Export prepared successfully',
                'filename' => $filename,
                'data' => [
                    'personal' => $personalData ?? [],
                    'cross_org' => $crossOrgData ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create manual relationship
     */
    public function createManualRelationship(Request $request): JsonResponse
    {
        $request->validate([
            'person_a_id' => 'required|exists:persons,id',
            'person_b_id' => 'required|exists:persons,id|different:person_a_id',
            'relationship_type' => 'required|in:' . implode(',', array_keys(PersonRelationship::getRelationshipTypes())),
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            // Check if relationship already exists
            if ($this->relationshipExists($request->person_a_id, $request->person_b_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Relationship already exists between these persons'
                ], 422);
            }

            $relationship = PersonRelationship::createRelationship(
                $request->person_a_id,
                $request->person_b_id,
                $request->relationship_type,
                [
                    'discovery_method' => 'manual',
                    'confidence_score' => 1.0,
                    'verification_status' => 'verified',
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                    'created_by' => auth()->id(),
                    'notes' => $request->notes ? ['manual_note' => $request->notes] : null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Relationship created successfully',
                'relationship' => $relationship->load(['personA', 'personB'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create relationship: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getPersonalRelationshipsForExport(): array
    {
        return PersonRelationship::query()
            ->with(['personA', 'personB', 'verifiedBy'])
            ->where('status', 'active')
            ->get()
            ->map(function ($relationship) {
                return [
                    'relationship_id' => $relationship->relationship_id,
                    'person_a_name' => $relationship->personA->given_name . ' ' . $relationship->personA->family_name,
                    'person_b_name' => $relationship->personB->given_name . ' ' . $relationship->personB->family_name,
                    'relationship_type' => $relationship->relationship_type,
                    'confidence_score' => $relationship->confidence_score,
                    'verification_status' => $relationship->verification_status,
                    'discovery_method' => $relationship->discovery_method,
                    'created_at' => $relationship->created_at->format('Y-m-d H:i:s'),
                    'verified_at' => $relationship->verified_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    private function getCrossOrgRelationshipsForExport(): array
    {
        return CrossOrgRelationship::query()
            ->with([
                'person',
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization',
                'verifiedBy'
            ])
            ->where('status', 'active')
            ->get()
            ->map(function ($relationship) {
                return [
                    'cross_relationship_id' => $relationship->cross_relationship_id,
                    'person_name' => $relationship->person->given_name . ' ' . $relationship->person->family_name,
                    'primary_organization' => $relationship->primaryAffiliation->Organization->legal_name,
                    'primary_role' => $relationship->primaryAffiliation->role_type,
                    'secondary_organization' => $relationship->secondaryAffiliation->Organization->legal_name,
                    'secondary_role' => $relationship->secondaryAffiliation->role_type,
                    'relationship_strength' => $relationship->relationship_strength,
                    'impact_score' => $relationship->impact_score,
                    'verified' => $relationship->verified ? 'Yes' : 'No',
                    'discovery_method' => $relationship->discovery_method,
                    'created_at' => $relationship->created_at->format('Y-m-d H:i:s'),
                    'verified_at' => $relationship->verified_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    private function relationshipExists(int $personId1, int $personId2): bool
    {
        return PersonRelationship::query()
            ->where(function ($query) use ($personId1, $personId2) {
                $query->where('person_a_id', min($personId1, $personId2))
                      ->where('person_b_id', max($personId1, $personId2));
            })
            ->exists();
    }

    private function getPendingVerifications(): array
    {
        $personalPending = PersonRelationship::query()
            ->where('verification_status', 'unverified')
            ->where('status', 'active')
            ->where('confidence_score', '>=', 0.8)
            ->with(['personA', 'personB'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $crossOrgPending = CrossOrgRelationship::query()
            ->where('verified', false)
            ->where('status', 'active')
            ->where('impact_score', '>=', 0.7)
            ->with([
                'person',
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization'
            ])
            ->orderByDesc('impact_score')
            ->limit(10)
            ->get();

        return [
            'personal' => $personalPending,
            'cross_org' => $crossOrgPending
        ];
    }

    /**
     * Get recent discoveries for dashboard
     */
    private function getRecentDiscoveries(): array
    {
        $recentPersonal = PersonRelationship::query()
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['personA', 'personB'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentCrossOrg = CrossOrgRelationship::query()
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subDays(7))
            ->with([
                'person',
                'primaryAffiliation.Organization',
                'secondaryAffiliation.Organization'
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'personal' => $recentPersonal,
            'cross_org' => $recentCrossOrg
        ];
    }
}
