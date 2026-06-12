<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PersonsExportAdvanced;

class PersonSearchController extends Controller
{
    public function index2()
    {
        return view('persons.search');
    }

    public function suggestions(Request $request): JsonResponse
    {
        $term = $request->get('term', '');
        $limit = $request->get('limit', 10);

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $suggestions = Person::getSearchSuggestions($term, $limit, $this->scopedOrganizationId($request));

        return response()->json($suggestions);
    }

    /**
     * Export search results
     */
    public function export(Request $request)
    {
        $criteria = $request->validate([
            'search' => 'nullable|string',
            'searchBy' => 'nullable|string|in:name,person_id,phone,email,identifier,global',
            'classification' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,other',
            'OrganizationId' => 'nullable|exists:organizations,id',
            'roleType' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'country' => 'nullable|string',
            'ageFrom' => 'nullable|integer|min:0|max:120',
            'ageTo' => 'nullable|integer|min:0|max:120',
            'selectedPersons' => 'nullable|array',
            'selectedPersons.*' => 'exists:persons,id',
        ]);

        // If specific persons are selected, export only those
        if (!empty($criteria['selectedPersons'])) {
            $query = Person::whereIn('id', $criteria['selectedPersons'])
                ->with(['phones', 'emailAddresses', 'identifiers', 'Organizations']);
            $this->applyOrganizationScope($query, $request);
            $persons = $query->get();
        } else {
            // Export all persons matching the search criteria
            $persons = $this->buildSearchQuery($criteria, $request)->get();
        }

        if ($persons->isEmpty()) {
            return back()->with('error', 'No persons found to export.');
        }

        $filename = 'persons_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new PersonsExportAdvanced($persons), $filename);
    }

    /**
     * API endpoint for advanced search
     */
    public function search(Request $request): JsonResponse
    {
        $criteria = $request->validate([
            'search' => 'nullable|string',
            'searchBy' => 'nullable|string|in:name,person_id,phone,email,identifier,global',
            'classification' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,other',
            'OrganizationId' => 'nullable|exists:organizations,id',
            'roleType' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'country' => 'nullable|string',
            'ageFrom' => 'nullable|integer|min:0|max:120',
            'ageTo' => 'nullable|integer|min:0|max:120',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $criteria['perPage'] ?? 15;

        $persons = $this->buildSearchQuery($criteria, $request)
            ->with(['phones', 'emailAddresses', 'identifiers', 'Organizations'])
            ->paginate($perPage);

        return response()->json([
            'data' => $persons->items(),
            'pagination' => [
                'current_page' => $persons->currentPage(),
                'last_page' => $persons->lastPage(),
                'per_page' => $persons->perPage(),
                'total' => $persons->total(),
                'from' => $persons->firstItem(),
                'to' => $persons->lastItem(),
            ]
        ]);
    }

    /**
     * Get filter options for search form
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'Organizations' => Organization::where('is_active', true)
                ->orderBy('display_name')
                ->get(['id', 'display_name', 'legal_name']),

            'classifications' => Person::whereNotNull('classification')
                ->pluck('classification')
                ->flatten()
                ->unique()
                ->sort()
                ->values(),

            'cities' => Person::whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),

            'districts' => Person::whereNotNull('district')
                ->distinct()
                ->orderBy('district')
                ->pluck('district'),

            'countries' => Person::whereNotNull('country')
                ->distinct()
                ->orderBy('country')
                ->pluck('country'),
        ]);
    }

    /**
     * The organization id the current user is limited to, or null for
     * Super Admins who may search across all organizations.
     */
    private function scopedOrganizationId(Request $request): ?int
    {
        $user = $request->user();

        if ($user && $user->hasRole('Super Admin')) {
            return null;
        }

        return (int) session('current_organization_id');
    }

    /**
     * Restrict a person query to the user's current organization. The
     * org.access middleware guarantees the session organization is one the
     * user has an active affiliation with.
     */
    private function applyOrganizationScope($query, Request $request)
    {
        $organizationId = $this->scopedOrganizationId($request);

        if ($organizationId) {
            $query->byOrganization($organizationId);
        }

        return $query;
    }

    /**
     * Build search query based on criteria
     */
    private function buildSearchQuery(array $criteria, Request $request)
    {
        $query = Person::query();

        $this->applyOrganizationScope($query, $request);

        // Apply search based on search type
        if (!empty($criteria['search'])) {
            $searchBy = $criteria['searchBy'] ?? 'name';
            
            switch ($searchBy) {
                case 'name':
                    $query->searchByName($criteria['search']);
                    break;
                
                case 'person_id':
                    $query->where('person_id', 'like', "%{$criteria['search']}%");
                    break;
                
                case 'phone':
                    $query->searchByPhone($criteria['search']);
                    break;
                
                case 'email':
                    $query->searchByEmail($criteria['search']);
                    break;
                
                case 'identifier':
                    $query->searchByIdentifier($criteria['search']);
                    break;
                
                case 'global':
                default:
                    $query->globalSearch($criteria['search']);
                    break;
            }
        }

        // Apply filters
        if (!empty($criteria['classification'])) {
            $query->byClassification($criteria['classification']);
        }

        if (!empty($criteria['gender'])) {
            $query->where('gender', $criteria['gender']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['city'])) {
            $query->where('city', 'like', "%{$criteria['city']}%");
        }

        if (!empty($criteria['district'])) {
            $query->where('district', 'like', "%{$criteria['district']}%");
        }

        if (!empty($criteria['country'])) {
            $query->where('country', 'like', "%{$criteria['country']}%");
        }

        // Organization filter
        if (!empty($criteria['OrganizationId'])) {
            $roleType = $criteria['roleType'] ?? null;
            $query->byOrganization($criteria['OrganizationId'], $roleType);
        }

        // Age filters
        if (!empty($criteria['ageFrom']) || !empty($criteria['ageTo'])) {
            $query->byAgeRange($criteria['ageFrom'], $criteria['ageTo']);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
