<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PersonAffiliation extends Model
{
    use HasFactory;
    protected $fillable = [
        'affiliation_id',
        'person_id',
        'organization_id',
        'user_id',

        'site',
        'role_type',
        'role_title',
        'start_date',
        'end_date',
        'status',
        'domain_record_type',
        'domain_record_id',
        'permissions',
        'can_view_cross_org_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'permissions' => 'array',
        'can_view_cross_org_data' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($affiliation) {
            if (empty($affiliation->affiliation_id)) {
                $maxAttempts = 5;
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $affiliation->affiliation_id = self::generateAffiliationId();

                    // Double-check that this ID doesn't exist before proceeding
                    if (!self::where('affiliation_id', $affiliation->affiliation_id)->exists()) {
                        break;
                    }

                    if ($attempt === $maxAttempts) {
                        throw new \Exception('Could not generate unique affiliation ID after ' . $maxAttempts . ' attempts');
                    }
                }
            }
        });
    }

    /**
     * Get the person for this affiliation.
     */
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id');
    }

    /**
     * Get the organization unit for this affiliation.
     */
    public function organizationUnit()
    {
        return $this->belongsTo(\App\Models\OrganizationUnit::class, 'domain_record_id');
    }


    /**
     * Generate unique affiliation ID
     */
    public static function generateAffiliationId(): string
    {
        $prefix = 'AFF-';
        $maxAttempts = 10;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Get the highest numeric part by extracting numbers and finding max
            $highestId = self::where('affiliation_id', 'like', $prefix . '%')
                ->get()
                ->map(function ($affiliation) use ($prefix) {
                    $idPart = substr($affiliation->affiliation_id, strlen($prefix));
                    // Only consider numeric IDs (ignore random string fallbacks)
                    return is_numeric($idPart) ? (int) $idPart : 0;
                })
                ->max();

            $newNumber = ($highestId ?? 0) + 1;
            $newId = $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

            // Check if this ID already exists (race condition protection)
            if (!self::where('affiliation_id', $newId)->exists()) {
                return $newId;
            }

            // If ID exists, wait a bit and try again
            if ($attempt < $maxAttempts) {
                usleep(rand(10000, 50000)); // Wait 10-50ms
            }
        }

        // Fallback: generate a unique ID with timestamp
        return $prefix . time() . '-' . rand(1000, 9999);
    }


    public function Organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if affiliation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            ($this->end_date === null || $this->end_date->isFuture());
    }

    /**
     * Get duration of affiliation
     */
    public function getDurationAttribute(): string
    {
        $endDate = $this->end_date ?? now();
        $duration = $this->start_date->diffForHumans($endDate, true);

        return $duration;
    }

    /**
     * Scope for active affiliations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    /**
     * Scope for specific role type
     */
    public function scopeRole($query, string $roleType)
    {
        return $query->where('role_type', $roleType);
    }

    /**
     * Scope for specific organization
     */
    public function scopeForOrganization($query, $OrganizationId)
    {
        return $query->where('organization_id', $OrganizationId);
    }


    public function roleType()
    {
        return $this->belongsTo(RoleType::class, 'role_type', 'code');
    }

    public function getDomainRecordAttribute()
    {
        if (!$this->roleType || !$this->roleType->hasDomainTable()) {
            return null;
        }

        $modelClass = $this->roleType->domain_model;

        if (!$modelClass) {
            return null;
        }

        return $modelClass::where('affiliation_id', $this->id)->first();
    }
}
