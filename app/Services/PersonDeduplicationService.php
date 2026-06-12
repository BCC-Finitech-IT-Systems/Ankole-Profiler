<?php
namespace App\Services;

use App\Models\Person;
use App\Models\Phone;
use App\Models\EmailAddress;
use App\Models\PersonIdentifier;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PersonDeduplicationService
{
    /**
     * Find potential duplicates based on matching criteria with multi-level confidence
     */
    public function findPotentialDuplicates($personData): Collection
    {
        $matches = collect();

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // LEVEL 1: HIGH CONFIDENCE (100% match)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        // 1.1: Exact Phone Match (across ALL organizations)
        if (!empty($personData['phone'])) {
            $phoneMatches = Person::whereHas('phones', function($query) use ($personData) {
                $query->where('number', $personData['phone'])
                      ->where('status', 'active');
            })
            ->with(['affiliations.Organization', 'phones', 'emailAddresses', 'identifiers'])
            ->get();

            foreach ($phoneMatches as $person) {
                $matches->push([
                    'person' => $person,
                    'similarity' => 100,
                    'match_type' => 'phone_exact',
                    'confidence' => 'high',
                    'reason' => 'Exact phone number match'
                ]);
            }
        }

        // 1.2: Exact Email Match (across ALL organizations)
        if (!empty($personData['email'])) {
            $emailMatches = Person::whereHas('emailAddresses', function($query) use ($personData) {
                $query->where('email', strtolower($personData['email']))
                      ->where('status', 'active');
            })
            ->with(['affiliations.Organization', 'phones', 'emailAddresses', 'identifiers'])
            ->get();

            foreach ($emailMatches as $person) {
                $matches->push([
                    'person' => $person,
                    'similarity' => 100,
                    'match_type' => 'email_exact',
                    'confidence' => 'high',
                    'reason' => 'Exact email address match'
                ]);
            }
        }

        // 1.3: Exact National ID Match
        if (!empty($personData['national_id'])) {
            $idMatches = Person::whereHas('identifiers', function($query) use ($personData) {
                $query->where('type', 'national_id')
                      ->where('identifier', $personData['national_id'])
                      ->where('status', 'active');
            })
            ->with(['affiliations.Organization', 'phones', 'emailAddresses', 'identifiers'])
            ->get();

            foreach ($idMatches as $person) {
                $matches->push([
                    'person' => $person,
                    'similarity' => 100,
                    'match_type' => 'national_id_exact',
                    'confidence' => 'high',
                    'reason' => 'Exact National ID match'
                ]);
            }
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // LEVEL 2: MEDIUM CONFIDENCE (Fuzzy matching)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        // 2.1: Name + Date of Birth (fuzzy name match)
        if (!empty($personData['given_name']) && 
            !empty($personData['family_name']) && 
            !empty($personData['date_of_birth'])) {
            
            $nameMatches = Person::where('date_of_birth', $personData['date_of_birth'])
                ->where('status', 'active')
                ->where(function($query) use ($personData) {
                    $query->where('given_name', 'LIKE', '%' . $personData['given_name'] . '%')
                          ->orWhere('family_name', 'LIKE', '%' . $personData['family_name'] . '%');
                })
                ->with(['affiliations.Organization', 'phones', 'emailAddresses', 'identifiers'])
                ->get();

            foreach ($nameMatches as $person) {
                $similarity = $this->calculateNameSimilarity(
                    $personData['given_name'] . ' ' . $personData['family_name'],
                    $person->given_name . ' ' . $person->family_name
                );

                // Only include if similarity > 70%
                if ($similarity > 70) {
                    $matches->push([
                        'person' => $person,
                        'similarity' => $similarity,
                        'match_type' => 'name_dob_fuzzy',
                        'confidence' => $similarity > 90 ? 'high' : 'medium',
                        'reason' => "Similar name ({$similarity}%) with same date of birth"
                    ]);
                }
            }
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // LEVEL 3: LOW CONFIDENCE (Possible matches)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        // 3.1: Similar Name + Similar DOB (within 3 days - typo tolerance)
        if (!empty($personData['date_of_birth']) && 
            !empty($personData['given_name']) && 
            !empty($personData['family_name'])) {
            
            $dob = Carbon::parse($personData['date_of_birth']);

            $closeMatches = Person::whereBetween('date_of_birth', [
                    $dob->copy()->subDays(3),
                    $dob->copy()->addDays(3)
                ])
                ->where('status', 'active')
                ->with(['affiliations.Organization', 'phones', 'emailAddresses', 'identifiers'])
                ->get();

            foreach ($closeMatches as $person) {
                $similarity = $this->calculateNameSimilarity(
                    $personData['given_name'] . ' ' . $personData['family_name'],
                    $person->given_name . ' ' . $person->family_name
                );

                // Only include if high similarity (likely typo in DOB)
                if ($similarity > 85) {
                    $matches->push([
                        'person' => $person,
                        'similarity' => $similarity,
                        'match_type' => 'name_dob_close',
                        'confidence' => 'low',
                        'reason' => "Very similar name with close date of birth (possible typo)"
                    ]);
                }
            }
        }

        // Remove duplicates (same person matched multiple ways)
        $matches = $matches->unique(function($match) {
            return $match['person']->id;
        });

        // Sort by confidence and similarity
        return $matches->sortByDesc(function($match) {
            $confidenceScore = [
                'high' => 1000,
                'medium' => 100,
                'low' => 10
            ];
            return ($confidenceScore[$match['confidence']] ?? 0) + $match['similarity'];
        })->values();
    }

    /**
     * Calculate similarity between two names using enhanced algorithm
     */
    private function calculateNameSimilarity($name1, $name2): float
    {
        // Normalize names
        $name1 = $this->normalizeName($name1);
        $name2 = $this->normalizeName($name2);

        // Calculate Levenshtein distance
        $distance = levenshtein($name1, $name2);
        $maxLength = max(strlen($name1), strlen($name2));

        // Avoid division by zero
        if ($maxLength == 0) {
            return 100;
        }

        // Convert to similarity percentage
        $similarity = round((1 - ($distance / $maxLength)) * 100, 2);

        return $similarity;
    }

    /**
     * Normalize name for comparison
     */
    private function normalizeName(string $name): string
    {
        // Convert to lowercase and trim
        $name = strtolower(trim($name));

        // Remove common prefixes/suffixes
        $name = preg_replace('/\b(mr|mrs|ms|dr|prof|jr|sr|iii|ii)\b\.?\s*/i', '', $name);

        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Check if person data matches existing person with high confidence
     */
    public function hasHighConfidenceMatch($personData): bool
    {
        $duplicates = $this->findPotentialDuplicates($personData);

        return $duplicates->filter(function($match) {
            return $match['confidence'] === 'high' && $match['similarity'] >= 95;
        })->isNotEmpty();
    }

    /**
     * Get the best match for person data
     */
    public function getBestMatch($personData): ?array
    {
        $duplicates = $this->findPotentialDuplicates($personData);

        if ($duplicates->isEmpty()) {
            return null;
        }

        return $duplicates->first();
    }

    /**
     * Link a new affiliation to an existing person
     */
    public function linkToExisting(Person $existingPerson, array $affiliationData): Person
    {
        // Update classification if new role type
        if (!empty($affiliationData['role_type'])) {
            $existingPerson->addClassification($affiliationData['role_type']);
        }

        // Log the linkage (activity logging would require spatie/laravel-activitylog package)
        // For now, we'll use Laravel's default logging
        Log::info('Person linked across organizations', [
            'person_id' => $existingPerson->id,
            'user_id' => Auth::id(),
            'action' => 'cross_org_link',
            'new_organization' => $affiliationData['organization_id'] ?? null,
            'role_type' => $affiliationData['role_type'] ?? null
        ]);

        return $existingPerson;
    }

    /**
     * Create person with comprehensive duplicate check
     */
    public function createWithDuplicateCheck(array $data): array
    {
        // Find potential duplicates
        $duplicates = $this->findPotentialDuplicates($data);

        // Check for high-confidence matches
        $highConfidenceMatches = $duplicates->filter(function($match) {
            return $match['confidence'] === 'high';
        });

        if ($highConfidenceMatches->isNotEmpty()) {
            return [
                'status' => 'duplicate_found',
                'duplicates' => $duplicates,
                'high_confidence' => true,
                'message' => 'High confidence duplicate found. Please review before creating.'
            ];
        }

        // Check for medium confidence matches
        $mediumConfidenceMatches = $duplicates->filter(function($match) {
            return $match['confidence'] === 'medium' && $match['similarity'] > 85;
        });

        if ($mediumConfidenceMatches->isNotEmpty()) {
            return [
                'status' => 'potential_duplicate',
                'duplicates' => $duplicates,
                'high_confidence' => false,
                'message' => 'Potential duplicate found. Please review before creating.'
            ];
        }

        // No significant duplicates, create new person
        $person = Person::create([
            'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
            'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier('GID'),
            'given_name' => $data['given_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'family_name' => $data['family_name'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'country' => $data['country'] ?? 'Uganda',
            'classification' => isset($data['role_type']) ? [$data['role_type']] : [],
            'created_by' => Auth::id(),
        ]);

        return [
            'status' => 'created',
            'person' => $person,
            'message' => 'New person created successfully.'
        ];
    }

    /**
     * Suggest merge candidates for admin review
     */
    public function suggestMergeCandidates(): Collection
    {
        // Find persons with very similar details but different global_identifiers
        // This would be for admin cleanup of potential duplicates that slipped through
        
        return collect(); // Implementation for admin tools
    }
}
