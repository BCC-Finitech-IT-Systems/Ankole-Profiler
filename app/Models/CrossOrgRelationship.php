<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CrossOrgRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'cross_relationship_id',
        'person_id',
        'primary_affiliation_id',
        'secondary_affiliation_id',
        'relationship_context',
        'relationship_strength',
        'discovered_date',
        'discovery_method',
        'verified',
        'verified_at',
        'verified_by',
        'status',
        'impact_score',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'discovered_date' => 'datetime',
        'verified_at' => 'datetime',
        'verified' => 'boolean',
        'impact_score' => 'decimal:2',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->cross_relationship_id)) {
                $model->cross_relationship_id = 'COR-' . strtoupper(Str::random(10));
            }

            if (empty($model->discovered_date)) {
                $model->discovered_date = now();
            }
        });
    }

    // Relationships
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function primaryAffiliation(): BelongsTo
    {
        return $this->belongsTo(PersonAffiliation::class, 'primary_affiliation_id');
    }

    public function secondaryAffiliation(): BelongsTo
    {
        return $this->belongsTo(PersonAffiliation::class, 'secondary_affiliation_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('verified', false);
    }

    public function scopeHighImpact(Builder $query, float $threshold = 0.7): Builder
    {
        return $query->where('impact_score', '>=', $threshold);
    }

    public function scopeByStrength(Builder $query, string $strength): Builder
    {
        return $query->where('relationship_strength', $strength);
    }

    public function scopeForPerson(Builder $query, int $personId): Builder
    {
        return $query->where('person_id', $personId);
    }

    public function scopeForOrganization(Builder $query, int $orgId): Builder
    {
        return $query->where(function ($q) use ($orgId) {
            $q->whereHas('primaryAffiliation.Organization', fn($r) => $r->where('id', $orgId))
              ->orWhereHas('secondaryAffiliation.Organization', fn($r) => $r->where('id', $orgId));
        });
    }

    // Helper methods
    public function markAsVerified(int $userId): bool
    {
        return $this->update([
            'verified' => true,
            'verified_at' => now(),
            'verified_by' => $userId
        ]);
    }

    public function calculateImpactScore(): float
    {
        $score = 0.5; // Base score

        // Increase score based on relationship strength
        switch ($this->relationship_strength) {
            case 'strong':
                $score += 0.3;
                break;
            case 'moderate':
                $score += 0.2;
                break;
            case 'weak':
                $score += 0.1;
                break;
        }

        // Increase score for certain role combinations
        $primaryRole = $this->primaryAffiliation->role_type ?? '';
        $secondaryRole = $this->secondaryAffiliation->role_type ?? '';

        // High impact combinations
        $highImpactCombos = [
            ['STAFF', 'PATIENT'],
            ['TEACHER', 'STUDENT'],
            ['ADMIN', 'MEMBER'],
            ['DOCTOR', 'PATIENT']
        ];

        foreach ($highImpactCombos as $combo) {
            if (($primaryRole === $combo[0] && $secondaryRole === $combo[1]) ||
                ($primaryRole === $combo[1] && $secondaryRole === $combo[0])) {
                $score += 0.2;
                break;
            }
        }

        return min(1.0, $score);
    }

    public function updateImpactScore(): bool
    {
        return $this->update(['impact_score' => $this->calculateImpactScore()]);
    }

    public function getRelationshipSummary(): string
    {
        $primary = $this->primaryAffiliation;
        $secondary = $this->secondaryAffiliation;

        if (!$primary || !$secondary) {
            return 'Unknown relationship';
        }

        $primaryOrg = $primary->Organization->legal_name ?? 'Unknown Org';
        $secondaryOrg = $secondary->Organization->legal_name ?? 'Unknown Org';

        return sprintf(
            '%s at %s and %s at %s',
            $primary->role_type,
            $primaryOrg,
            $secondary->role_type,
            $secondaryOrg
        );
    }

    // Static helper methods
    public static function getStrengthOptions(): array
    {
        return [
            'weak' => 'Weak Connection',
            'moderate' => 'Moderate Connection',
            'strong' => 'Strong Connection'
        ];
    }

    public static function getDiscoveryMethods(): array
    {
        return [
            'automatic' => 'Automatic Detection',
            'manual' => 'Manual Entry',
            'import' => 'Data Import',
            'temporal_analysis' => 'Temporal Analysis'
        ];
    }

    public static function createCrossOrgRelationship(
        int $personId,
        int $primaryAffiliationId,
        int $secondaryAffiliationId,
        array $options = []
    ): self {
        $relationship = self::create(array_merge([
            'person_id' => $personId,
            'primary_affiliation_id' => $primaryAffiliationId,
            'secondary_affiliation_id' => $secondaryAffiliationId,
            'relationship_strength' => 'moderate',
            'discovery_method' => 'automatic',
            'verified' => false,
            'status' => 'active',
            'impact_score' => 0.5
        ], $options));

        // Calculate and update impact score
        $relationship->updateImpactScore();

        // Generate relationship context if not provided
        if (empty($relationship->relationship_context)) {
            $relationship->update([
                'relationship_context' => $relationship->generateRelationshipContext()
            ]);
        }

        return $relationship;
    }

    public function generateRelationshipContext(): string
    {
        $primary = $this->primaryAffiliation;
        $secondary = $this->secondaryAffiliation;

        if (!$primary || !$secondary) {
            return 'unknown_context';
        }

        $primaryRole = strtolower($primary->role_type);
        $secondaryRole = strtolower($secondary->role_type);
        $primaryOrgType = $primary->Organization->category ?? 'unknown';
        $secondaryOrgType = $secondary->Organization->category ?? 'unknown';

        return sprintf(
            '%s_at_%s_%s_at_%s',
            $primaryRole,
            $primaryOrgType,
            $secondaryRole,
            $secondaryOrgType
        );
    }

    // Analytics helpers
    public static function getTopConnections(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->verified()
            ->orderByDesc('impact_score')
            ->limit($limit)
            ->with(['person', 'primaryAffiliation.Organization', 'secondaryAffiliation.Organization'])
            ->get();
    }

    public static function getOrganizationConnectionStats(int $orgId): array
    {
        $query = self::active()
            ->where(function ($q) use ($orgId) {
                $q->whereHas('primaryAffiliation', fn($r) => $r->where('organization_id', $orgId))
                  ->orWhereHas('secondaryAffiliation', fn($r) => $r->where('organization_id', $orgId));
            });

        return [
            'total_connections' => $query->count(),
            'verified_connections' => $query->clone()->verified()->count(),
            'high_impact_connections' => $query->clone()->highImpact()->count(),
            'strong_connections' => $query->clone()->byStrength('strong')->count(),
            'avg_impact_score' => $query->clone()->avg('impact_score'),
            'unique_persons' => $query->clone()->distinct('person_id')->count()
        ];
    }
}
