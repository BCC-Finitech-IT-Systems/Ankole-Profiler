<?php

namespace App\Services;

use App\Models\CommunicationFilterProfile;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use App\Helpers\OrganizationHelper;

class FilterProfileIntegrationService
{
    /**
     * Convert PersonSearch component filters to CommunicationFilterProfile format
     */
    public static function convertPersonSearchFilters(array $searchFilters): array
    {
        $converted = [];
        
        // Direct mappings
        $directMappings = [
            'search' => 'search',
            'gender' => 'gender', 
            'status' => 'status',
            'city' => 'city',
            'district' => 'district',
            'country' => 'country',
            'classification' => 'classification',
        ];
        
        foreach ($directMappings as $searchKey => $profileKey) {
            if (!empty($searchFilters[$searchKey])) {
                $converted[$profileKey] = $searchFilters[$searchKey];
            }
        }
        
        // Age range conversion
        if (!empty($searchFilters['ageFrom']) || !empty($searchFilters['ageTo'])) {
            $ageFrom = $searchFilters['ageFrom'] ?? '0';
            $ageTo = $searchFilters['ageTo'] ?? '120';
            $converted['age_range'] = $ageFrom . '-' . $ageTo;
        }
        
        // Organization and role type
        if (!empty($searchFilters['OrganizationId'])) {
            $converted['organization_id'] = $searchFilters['OrganizationId'];
        }
        
        if (!empty($searchFilters['roleType'])) {
            $converted['role_type'] = $searchFilters['roleType'];
        }
        
        // Search type
        if (!empty($searchFilters['searchBy']) && $searchFilters['searchBy'] !== 'global') {
            $converted['search_type'] = $searchFilters['searchBy'];
        }
        
        return $converted;
    }
    
    /**
     * Convert CommunicationFilterProfile criteria to PersonSearch format
     */
    public static function convertToPersonSearchFilters(array $profileCriteria): array
    {
        $converted = [
            'search' => '',
            'searchBy' => 'global',
            'classification' => '',
            'gender' => '',
            'OrganizationId' => '',
            'roleType' => '',
            'status' => 'active',
            'city' => '',
            'district' => '',
            'country' => '',
            'ageFrom' => '',
            'ageTo' => '',
        ];
        
        // Direct mappings
        $directMappings = [
            'search' => 'search',
            'gender' => 'gender',
            'status' => 'status', 
            'city' => 'city',
            'district' => 'district',
            'country' => 'country',
            'classification' => 'classification',
            'organization_id' => 'OrganizationId',
            'role_type' => 'roleType',
            'search_type' => 'searchBy',
        ];
        
        foreach ($directMappings as $profileKey => $searchKey) {
            if (!empty($profileCriteria[$profileKey])) {
                $converted[$searchKey] = $profileCriteria[$profileKey];
            }
        }
        
        // Age range conversion
        if (!empty($profileCriteria['age_range'])) {
            if (preg_match('/(\d+)-(\d+)/', $profileCriteria['age_range'], $matches)) {
                $converted['ageFrom'] = $matches[1];
                $converted['ageTo'] = $matches[2];
            }
        }
        
        // Handle legacy age_from and age_to
        if (!empty($profileCriteria['age_from'])) {
            $converted['ageFrom'] = $profileCriteria['age_from'];
        }
        if (!empty($profileCriteria['age_to'])) {
            $converted['ageTo'] = $profileCriteria['age_to'];
        }
        
        return $converted;
    }
    
    /**
     * Apply filter profile criteria to a Person query
     */
    public static function applyFiltersToQuery($query, array $criteria)
    {
        foreach ($criteria as $field => $value) {
            if (empty($value)) continue;
            
            switch ($field) {
                case 'search':
                    $query->where(function($q) use ($value) {
                        $q->where('given_name', 'like', "%{$value}%")
                          ->orWhere('middle_name', 'like', "%{$value}%")
                          ->orWhere('family_name', 'like', "%{$value}%")
                          ->orWhere('person_id', 'like', "%{$value}%");
                    });
                    break;
                    
                case 'gender':
                case 'status':
                case 'city':
                case 'district':
                case 'county':
                case 'subcounty':
                case 'parish':
                case 'village':
                case 'country':
                    $query->where($field, $value);
                    break;
                    
                case 'classification':
                    if (is_array($value)) {
                        $query->where(function($q) use ($value) {
                            foreach ($value as $classification) {
                                $q->orWhereJsonContains('classification', $classification);
                            }
                        });
                    } else {
                        $query->whereJsonContains('classification', $value);
                    }
                    break;
                    
                case 'age_range':
                    if (preg_match('/(\d+)-(\d+)/', $value, $matches)) {
                        $minAge = $matches[1];
                        $maxAge = $matches[2];
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$minAge, $maxAge]);
                    }
                    break;
                    
                case 'role_type':
                    $query->whereHas('affiliations', function($q) use ($value) {
                        $q->where('role_type', $value);
                    });
                    break;
                    
                case 'organization_id':
                    $query->whereHas('affiliations', function($q) use ($value) {
                        $q->where('organization_id', $value);
                    });
                    break;
            }
        }
        
        return $query;
    }
    
    /**
     * Get count of persons matching filter criteria
     */
    public static function getFilteredPersonsCount(array $criteria, $organizationId = null): int
    {
        try {
            if ($organizationId) {
                $query = Person::whereHas('affiliations', function ($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                });
            } else {
                $query = Person::query();
            }
            
            return self::applyFiltersToQuery($query, $criteria)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Validate filter criteria
     */
    public static function validateFilterCriteria(array $criteria): array
    {
        $errors = [];
        
        // Check if at least one filter is provided
        if (empty(array_filter($criteria))) {
            $errors[] = 'At least one filter criterion must be provided.';
        }
        
        // Validate age range format
        if (!empty($criteria['age_range'])) {
            if (!preg_match('/^\d+-\d+$/', $criteria['age_range'])) {
                $errors[] = 'Age range must be in format "min-max" (e.g., "18-65").';
            }
        }
        
        // Validate organization exists if provided
        if (!empty($criteria['organization_id'])) {
            if (!\App\Models\Organization::find($criteria['organization_id'])) {
                $errors[] = 'Selected organization does not exist.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Create a quick filter profile from current search state
     */
    public static function createQuickProfile(string $name, array $searchFilters, string $description = null): ?CommunicationFilterProfile
    {
        $organization = OrganizationHelper::getCurrentOrganization();
        
        if (!$organization) {
            return null;
        }
        
        $convertedCriteria = self::convertPersonSearchFilters($searchFilters);
        
        if (empty($convertedCriteria)) {
            return null;
        }
        
        try {
            return CommunicationFilterProfile::create([
                'name' => $name,
                'description' => $description,
                'user_id' => Auth::id(),
                'organization_id' => $organization->id,
                'filter_criteria' => $convertedCriteria,
                'is_shared' => false,
                'is_active' => true,
                'last_used_at' => now(),
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get popular filter combinations for suggestions
     */
    public static function getPopularFilterCombinations($organizationId = null, int $limit = 5): array
    {
        $query = CommunicationFilterProfile::where('is_active', true);
        
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        
        return $query->select('filter_criteria')
            ->orderBy('last_used_at', 'desc')
            ->limit($limit * 3) // Get more to analyze
            ->get()
            ->groupBy(function($profile) {
                // Group by similar filter combinations
                $criteria = $profile->filter_criteria;
                ksort($criteria);
                return md5(json_encode(array_keys($criteria)));
            })
            ->map(function($group) {
                return $group->first()->filter_criteria;
            })
            ->take($limit)
            ->values()
            ->toArray();
    }
    
    /**
     * Suggest filter profile name based on criteria
     */
    public static function suggestProfileName(array $criteria): string
    {
        $nameParts = [];
        
        if (!empty($criteria['status'])) {
            $nameParts[] = ucfirst($criteria['status']);
        }
        
        if (!empty($criteria['gender'])) {
            $nameParts[] = ucfirst($criteria['gender']);
        }
        
        if (!empty($criteria['classification'])) {
            if (is_array($criteria['classification'])) {
                $nameParts[] = implode(' & ', array_map('ucfirst', $criteria['classification']));
            } else {
                $nameParts[] = ucfirst($criteria['classification']);
            }
        }
        
        if (!empty($criteria['role_type'])) {
            $nameParts[] = ucfirst(str_replace('_', ' ', $criteria['role_type']));
        }
        
        if (!empty($criteria['city'])) {
            $nameParts[] = "in " . $criteria['city'];
        }
        
        if (!empty($criteria['age_range'])) {
            $nameParts[] = "Age " . $criteria['age_range'];
        }
        
        if (empty($nameParts)) {
            return "Custom Filter " . date('M j');
        }
        
        return implode(' ', $nameParts);
    }
}