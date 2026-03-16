<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class Person extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'persons';
    protected $fillable = [
        'person_id',
        'global_identifier',
        'user_id',

        'given_name',
        'middle_name',
        'family_name',
        'date_of_birth',
        'gender',
        'classification',
        'address',
        'country',
        'city',
        'district',

        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Route notifications for the mail channel.
     *
     * @return string|null
     */
    public function routeNotificationForMail()
    {
        $primary = $this->primaryEmail();
        return $primary ? $primary->email : null;
    }

    /**
     * Generate unique person ID
     */
    public static function generatePersonId(): string
    {
        $prefix = 'PRS-';
        $lastPerson = self::where('person_id', 'like', $prefix . '%')
            ->orderBy('person_id', 'desc')
            ->first();

        if ($lastPerson) {
            $lastNumber = (int) substr($lastPerson->person_id, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->given_name,
            $this->middle_name,
            $this->family_name
        ]);

        return implode(' ', $parts);
    }

    /**
     * Relationships
     */
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function emailAddresses(): HasMany
    {
        return $this->hasMany(EmailAddress::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    public function affiliations(): HasMany
    {
        return $this->hasMany(PersonAffiliation::class);
    }

    public function Organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'person_affiliations')
            ->withPivot([
                'affiliation_id',
                'site',
                'role_type',
                'role_title',
                'start_date',
                'end_date',
                'status',
                'domain_record_type',
                'domain_record_id',
                'permissions',
                'can_view_cross_org_data'
            ])
            ->withTimestamps();
    }

    /**
     * Domain-specific record relationships
     */
    public function patientRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            PatientRecord::class,
            PersonAffiliation::class,
            'person_id',
            'affiliation_id',
            'id',
            'id'
        );
    }

    public function staffRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            StaffRecord::class,
            PersonAffiliation::class,
            'person_id',
            'affiliation_id',
            'id',
            'id'
        );
    }

    public function studentRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            StudentRecord::class,
            PersonAffiliation::class,
            'person_id',
            'affiliation_id',
            'id',
            'id'
        );
    }

    public function saccoMemberRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            SaccoMemberRecord::class,
            PersonAffiliation::class,
            'person_id',
            'affiliation_id',
            'id',
            'id'
        );
    }

    public function parishMemberRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            ParishMemberRecord::class,
            PersonAffiliation::class,
            'person_id',
            'affiliation_id',
            'id',
            'id'
        );
    }

    /**
     * Get primary phone
     */
    public function primaryPhone()
    {
        return $this->phones()->where('is_primary', true)->first();
    }

    /**
     * Get primary email
     */
    public function primaryEmail()
    {
        return $this->emailAddresses()->where('is_primary', true)->first();
    }

    /**
     * Get national ID
     */
    public function nationalId()
    {
        return $this->identifiers()->where('type', 'national_id')->first();
    }

    /**
     * Get active affiliations
     */
    public function activeAffiliations()
    {
        return $this->affiliations()->where('status', 'active');
    }

    /**
     * Check if person has affiliation with organization
     */
    public function hasAffiliationWith($OrganizationId, $roleType = null): bool
    {
        $query = $this->affiliations()
            ->where('organization_id', $OrganizationId)
            ->where('status', 'active');

        if ($roleType) {
            $query->where('role_type', $roleType);
        }

        return $query->exists();
    }

    /**
     * Add classification to person
     */
    public function addClassification(string $classification): void
    {
        $classifications = $this->classification ?? [];

        if (!in_array($classification, $classifications)) {
            $classifications[] = $classification;
            $this->classification = $classifications;
            $this->save();
        }
    }

    /**
     * Remove classification from person
     */
    public function removeClassification(string $classification): void
    {
        $classifications = $this->classification ?? [];
        $classifications = array_diff($classifications, [$classification]);
        $this->classification = array_values($classifications);
        $this->save();
    }

    /**
     * Scope for searching by name
     */
    public function scopeSearchByName($query, string $name)
    {
        $searchTerms = explode(' ', trim($name));

        return $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->where(function ($subQ) use ($term) {
                    $subQ->where('given_name', 'like', "%{$term}%")
                        ->orWhere('middle_name', 'like', "%{$term}%")
                        ->orWhere('family_name', 'like', "%{$term}%");
                });
            }
        });
    }

    /**
     * Scope for active persons
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdvancedSearch($query, array $criteria)
    {
        // Name search
        if (!empty($criteria['name'])) {
            $query->searchByName($criteria['name']);
        }

        // Person ID search
        if (!empty($criteria['person_id'])) {
            $query->where('person_id', 'like', "%{$criteria['person_id']}%");
        }

        // Phone search
        if (!empty($criteria['phone'])) {
            $query->whereHas('phones', function ($q) use ($criteria) {
                $q->where('phone_number', 'like', "%{$criteria['phone']}%");
            });
        }

        // Email search
        if (!empty($criteria['email'])) {
            $query->whereHas('emailAddresses', function ($q) use ($criteria) {
                $q->where('email', 'like', "%{$criteria['email']}%");
            });
        }

        // Classification filter
        if (!empty($criteria['classification'])) {
            $query->whereJsonContains('classification', $criteria['classification']);
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

        // Age range filter
        if (!empty($criteria['age_from']) || !empty($criteria['age_to'])) {
            $query->where(function ($q) use ($criteria) {
                if (!empty($criteria['age_from'])) {
                    $dateFrom = now()->subYears($criteria['age_from'])->format('Y-m-d');
                    $q->where('date_of_birth', '<=', $dateFrom);
                }

                if (!empty($criteria['age_to'])) {
                    $dateTo = now()->subYears($criteria['age_to'])->format('Y-m-d');
                    $q->where('date_of_birth', '>=', $dateTo);
                }
            });
        }

        // Date range filters
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }

        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }

        // Organization filter
        if (!empty($criteria['organization_id'])) {
            $query->whereHas('affiliations', function ($q) use ($criteria) {
                $q->where('organization_id', $criteria['organization_id'])
                    ->where('status', 'active');

                if (!empty($criteria['role_type'])) {
                    $q->where('role_type', $criteria['role_type']);
                }
            });
        }

        return $query;
    }

    /**
     * Scope for searching by identifier
     */
    public function scopeSearchByIdentifier($query, string $identifier, string $type = null)
    {
        return $query->whereHas('identifiers', function ($q) use ($identifier, $type) {
            $q->where('identifier_value', 'like', "%{$identifier}%");

            if ($type) {
                $q->where('type', $type);
            }
        });
    }

    /**
     * Scope for searching by phone number
     */
    public function scopeSearchByPhone($query, string $phone)
    {
        return $query->whereHas('phones', function ($q) use ($phone) {
            $q->where('phone_number', 'like', "%{$phone}%");
        });
    }

    /**
     * Scope for searching by email
     */
    public function scopeSearchByEmail($query, string $email)
    {
        return $query->whereHas('emailAddresses', function ($q) use ($email) {
            $q->where('email', 'like', "%{$email}%");
        });
    }

    /**
     * Scope for filtering by Organization
     */
    public function scopeByOrganization($query, int $OrganizationId, string $roleType = null)
    {
        return $query->whereHas('affiliations', function ($q) use ($OrganizationId, $roleType) {
            $q->where('organization_id', $OrganizationId)
                ->where('status', 'active');

            if ($roleType) {
                $q->where('role_type', $roleType);
            }
        });
    }

    /**
     * Scope for filtering by classification
     */
    public function scopeByClassification($query, string $classification)
    {
        return $query->whereJsonContains('classification', $classification);
    }

    /**
     * Scope for filtering by age range
     */
    public function scopeByAgeRange($query, int $ageFrom = null, int $ageTo = null)
    {
        return $query->where(function ($q) use ($ageFrom, $ageTo) {
            if ($ageFrom !== null) {
                $dateFrom = now()->subYears($ageFrom)->format('Y-m-d');
                $q->where('date_of_birth', '<=', $dateFrom);
            }

            if ($ageTo !== null) {
                $dateTo = now()->subYears($ageTo)->format('Y-m-d');
                $q->where('date_of_birth', '>=', $dateTo);
            }
        });
    }

    /**
     * Scope for global search across multiple fields
     */
    public function scopeGlobalSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->searchByName($term)
                ->orWhere('person_id', 'like', "%{$term}%")
                ->orWhereHas('phones', function ($phoneQuery) use ($term) {
                    $phoneQuery->where('phone_number', 'like', "%{$term}%");
                })
                ->orWhereHas('emailAddresses', function ($emailQuery) use ($term) {
                    $emailQuery->where('email', 'like', "%{$term}%");
                })
                ->orWhereHas('identifiers', function ($identifierQuery) use ($term) {
                    $identifierQuery->where('identifier_value', 'like', "%{$term}%");
                })
                ->orWhere('address', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('district', 'like', "%{$term}%");
        });
    }

    /**
     * Get age calculated from date of birth
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->district,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if person has specific classification
     */
    public function hasClassification(string $classification): bool
    {
        $classifications = $this->classification ?? [];
        return in_array($classification, $classifications);
    }

    /**
     * Get active affiliations with Organization details
     */
    public function getActiveAffiliationsWithOrganizationsAttribute()
    {
        return $this->affiliations()
            ->with('Organization')
            ->where('status', 'active')
            ->get();
    }

    /**
     * Search persons with export data
     */
    public static function searchForExport(array $criteria)
    {
        return self::advancedSearch($criteria)
            ->with([
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
            ])
            ->select([
                'id',
                'person_id',
                'given_name',
                'middle_name',
                'family_name',
                'date_of_birth',
                'gender',
                'classification',
                'address',
                'city',
                'district',
                'country',
                'status',
                'created_at'
            ]);
    }

    /**
     * Get search suggestions based on partial input
     */
    public static function getSearchSuggestions(string $term, int $limit = 10)
    {
        return self::globalSearch($term)
            ->active()
            ->limit($limit)
            ->get(['id', 'person_id', 'given_name', 'family_name'])
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'person_id' => $person->person_id,
                    'name' => $person->full_name,
                    'label' => "{$person->full_name} ({$person->person_id})"
                ];
            });
    }

    public function relationships()
    {
        return $this->hasMany(PersonRelationship::class, 'person_id');
    }

    public function relatedPersons()
    {
        return $this->belongsToMany(Person::class, 'person_relationships', 'person_id', 'related_person_id')
            ->withPivot('relationship_type', 'is_primary', 'is_emergency_contact', 'notes', 'status')
            ->withTimestamps();
    }

    public function reverseRelationships()
    {
        return $this->hasMany(PersonRelationship::class, 'related_person_id');
    }

    public function User()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    protected static function booted()
    {
        static::deleting(function ($person) {
            // If a User is linked via user_id, delete that User as well.
            try {
                if ($person->user_id) {
                    $user = \App\Models\User::find($person->user_id);
                    if ($user) {
                        // Delete user via Eloquent to ensure related cleanup
                        $user->delete();
                    }
                }
            } catch (\Exception $e) {
                // Log and continue - do not block person deletion
                \Illuminate\Support\Facades\Log::error('Error deleting linked user for person ' . ($person->id ?? 'unknown') . ': ' . $e->getMessage());
            }
        });
    }

    public function personAffiliation(): HasMany
    {
        return $this->hasMany(PersonAffiliation::class);
    }

    public function projectAffiliations(): HasMany
    {
        return $this->hasMany(ProjectAffiliation::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_affiliations')
            ->withPivot(['affiliation_type', 'role_title', 'occupation', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }
}
