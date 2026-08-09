<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PolicyVersion extends Model
{
    use HasFactory;

    /**
     * Once a version reaches one of these statuses it is immutable except
     * for the fields in IMMUTABLE_ALLOWLIST — see boot() below.
     */
    private const LOCKED_STATUSES = ['approved', 'published', 'superseded', 'archived'];

    private const IMMUTABLE_ALLOWLIST = [
        'status', 'archived_at', 'published_at', 'published_by',
        'supersedes_version_id', 'updated_at',
    ];

    protected $fillable = [
        'policy_id',
        'version_number',
        'version_label',
        'summary',
        'status',
        'effective_date',
        'review_date',
        'adoption_due_date',
        'issuing_authority',
        'synod_approval_date',
        'synod_approval_reference',
        'visibility',
        'document_path',
        'document_original_name',
        'document_mime',
        'document_size',
        'document_hash',
        'supersedes_version_id',
        'created_by',
        'approved_by',
        'published_by',
        'approved_at',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_date' => 'date',
        'adoption_due_date' => 'date',
        'synod_approval_date' => 'date',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (PolicyVersion $version) {
            if (!$version->exists) {
                return;
            }

            $originalStatus = $version->getOriginal('status');

            if (!in_array($originalStatus, self::LOCKED_STATUSES, true)) {
                return;
            }

            $disallowedChanges = array_diff(
                array_keys($version->getDirty()),
                self::IMMUTABLE_ALLOWLIST
            );

            if (!empty($disallowedChanges)) {
                throw new \DomainException(
                    'Approved policy versions are immutable; create a revision instead.'
                );
            }
        });

        static::deleting(function (PolicyVersion $version) {
            if (in_array($version->getOriginal('status'), self::LOCKED_STATUSES, true)) {
                throw new \DomainException(
                    'Approved policy versions cannot be deleted.'
                );
            }
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'supersedes_version_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(PolicyVersion::class, 'supersedes_version_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(PolicyDocument::class, 'documentable')->where('kind', 'supporting');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(PolicyPublication::class);
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(PolicyVersionAudience::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function hasSynodApproval(): bool
    {
        return !empty($this->synod_approval_date) && !empty($this->synod_approval_reference);
    }

    public function isPublishable(): bool
    {
        return $this->status === 'approved'
            && $this->hasSynodApproval()
            && !empty($this->document_path);
    }

    /**
     * Whether $user may view this version's metadata/document, accounting
     * for diocese/department management scope, institution-side publication
     * access, and restricted-audience rules.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $policy = $this->relationLoaded('policy') ? $this->policy : $this->policy()->first();

        if ($user->managedOrganizationIds()->contains($policy->organization_id)) {
            return true;
        }

        if ($policy->department_id && $user->managedDepartmentIds()->contains($policy->department_id)) {
            return true;
        }

        $institutionIds = $user->activeAffiliations()->pluck('person_affiliations.organization_id')->unique();

        $publishedToUser = $this->publications()->whereIn('organization_id', $institutionIds)->exists();

        if (!$publishedToUser) {
            return false;
        }

        if ($this->visibility === 'diocese_wide') {
            return true;
        }

        $audiences = $this->relationLoaded('audiences') ? $this->audiences : $this->audiences()->get();

        if ($audiences->isEmpty()) {
            // Restricted with no explicit audience rows: publication is the
            // only gate, already satisfied above.
            return true;
        }

        $userDepartmentIds = $user->managedDepartmentIds();
        $userRoleNames = $user->getRoleNames();

        return $audiences->contains(function (PolicyVersionAudience $audience) use ($institutionIds, $userDepartmentIds, $userRoleNames) {
            if ($audience->organization_id) {
                return $institutionIds->contains($audience->organization_id);
            }
            if ($audience->department_id) {
                return $userDepartmentIds->contains($audience->department_id);
            }
            if ($audience->role_name) {
                return $userRoleNames->contains($audience->role_name);
            }
            return false;
        });
    }
}
