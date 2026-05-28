<?php

// App\Services\PersonSearchService.php

namespace App\Services;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonSearchService
{
    /**
     * Perform advanced person search
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildQuery($criteria);
        
        return $query->with([
            'phones' => function ($query) {
                $query->where('is_primary', true);
            },
            'emailAddresses' => function ($query) {
                $query->where('is_primary', true);
            },
            'identifiers',
            'Organizations' => function ($query) {
                $query->wherePivot('status', 'active');
            }
        ])->paginate($perPage);
    }

    /**
     * Get search suggestions
     */
    public function getSuggestions(string $term, int $limit = 10): array
    {
        if (strlen($term) < 2) {
            return [];
        }

        $persons = Person::globalSearch($term)
            ->active()
            ->limit($limit)
            ->get(['id', 'person_id', 'given_name', 'family_name']);

        return $persons->map(function ($person) {
            return [
                'id' => $person->id,
                'person_id' => $person->person_id,
                'name' => $person->full_name,
                'label' => "{$person->full_name} ({$person->person_id})",
                'value' => $person->person_id
            ];
        })->toArray();
    }

    /**
     * Export search results
     */
    public function export(array $criteria): \Illuminate\Support\Collection
    {
        return $this->buildQuery($criteria)
            ->with(['phones', 'emailAddresses', 'identifiers', 'Organizations'])
            ->get();
    }

    /**
     * Get filter statistics
     */
    public function getFilterStats(array $criteria): array
    {
        $baseQuery = $this->buildQuery($criteria);
        
        return [
            'total_found' => $baseQuery->count(),
            'by_gender' => $this->getGenderStats($baseQuery),
            'by_status' => $this->getStatusStats($baseQuery),
            'by_classification' => $this->getClassificationStats($baseQuery),
            'by_age_group' => $this->getAgeGroupStats($baseQuery),
        ];
    }

    /**
     * Build search query
     */
    private function buildQuery(array $criteria): Builder
    {
        $query = Person::query();

        // Text search
        if (!empty($criteria['search'])) {
            $searchBy = $criteria['searchBy'] ?? 'global';
            $this->applyTextSearch($query, $criteria['search'], $searchBy);
        }

        // Filters
        $this->applyFilters($query, $criteria);

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Apply text search based on search type
     */
    private function applyTextSearch(Builder $query, string $search, string $searchBy): void
    {
        switch ($searchBy) {
            case 'name':
                $query->searchByName($search);
                break;
            case 'person_id':
                $query->where('person_id', 'like', "%{$search}%");
                break;
            case 'phone':
                $query->searchByPhone($search);
                break;
            case 'email':
                $query->searchByEmail($search);
                break;
            case 'identifier':
                $query->searchByIdentifier($search);
                break;
            case 'global':
            default:
                $query->globalSearch($search);
                break;
        }
    }

    /**
     * Apply various filters to the query
     */
    private function applyFilters(Builder $query, array $criteria): void
    {
        // Classification filter
        if (!empty($criteria['classification'])) {
            $query->byClassification($criteria['classification']);
        }

        // Gender filter
        if (!empty($criteria['gender'])) {
            $query->where('gender', $criteria['gender']);
        }

        // Status filter
        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        // Location filters
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

        // Age range filter
        if (!empty($criteria['ageFrom']) || !empty($criteria['ageTo'])) {
            $query->byAgeRange($criteria['ageFrom'], $criteria['ageTo']);
        }

        // Date range filters
        if (!empty($criteria['createdFrom'])) {
            $query->whereDate('created_at', '>=', $criteria['createdFrom']);
        }

        if (!empty($criteria['createdTo'])) {
            $query->whereDate('created_at', '<=', $criteria['createdTo']);
        }
    }

    /**
     * Get gender statistics
     */
    private function getGenderStats(Builder $baseQuery): array
    {
        return $baseQuery->clone()
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Get status statistics
     */
    private function getStatusStats(Builder $baseQuery): array
    {
        return $baseQuery->clone()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get classification statistics
     */
    private function getClassificationStats(Builder $baseQuery): array
    {
        $persons = $baseQuery->clone()
            ->whereNotNull('classification')
            ->get(['classification']);

        $stats = [];
        foreach ($persons as $person) {
            if ($person->classification) {
                foreach ($person->classification as $classification) {
                    $stats[$classification] = ($stats[$classification] ?? 0) + 1;
                }
            }
        }

        return $stats;
    }

    /**
     * Get age group statistics
     */
    private function getAgeGroupStats(Builder $baseQuery): array
    {
        $persons = $baseQuery->clone()
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth']);

        $ageGroups = [
            '0-17' => 0,
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56-65' => 0,
            '65+' => 0,
        ];

        foreach ($persons as $person) {
            $age = $person->date_of_birth->age;
            
            if ($age < 18) {
                $ageGroups['0-17']++;
            } elseif ($age <= 25) {
                $ageGroups['18-25']++;
            } elseif ($age <= 35) {
                $ageGroups['26-35']++;
            } elseif ($age <= 45) {
                $ageGroups['36-45']++;
            } elseif ($age <= 55) {
                $ageGroups['46-55']++;
            } elseif ($age <= 65) {
                $ageGroups['56-65']++;
            } else {
                $ageGroups['65+']++;
            }
        }

        return $ageGroups;
    }
}

// App\Http\Requests\PersonSearchRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'searchBy' => 'nullable|string|in:name,person_id,phone,email,identifier,global',
            'classification' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:male,female,other',
            'OrganizationId' => 'nullable|exists:organizations,id',
            'roleType' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'ageFrom' => 'nullable|integer|min:0|max:120',
            'ageTo' => 'nullable|integer|min:0|max:120|gte:ageFrom',
            'createdFrom' => 'nullable|date',
            'createdTo' => 'nullable|date|after_or_equal:createdFrom',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
            'selectedPersons' => 'nullable|array',
            'selectedPersons.*' => 'exists:persons,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ageTo.gte' => 'The age to must be greater than or equal to age from.',
            'createdTo.after_or_equal' => 'The created to date must be after or equal to created from date.',
            'OrganizationId.exists' => 'The selected Organization does not exist.',
            'selectedPersons.*.exists' => 'One or more selected persons do not exist.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'searchBy' => 'search type',
            'OrganizationId' => 'Organization',
            'roleType' => 'role type',
            'ageFrom' => 'age from',
            'ageTo' => 'age to',
            'createdFrom' => 'created from',
            'createdTo' => 'created to',
            'perPage' => 'results per page',
            'selectedPersons' => 'selected persons',
        ];
    }
}