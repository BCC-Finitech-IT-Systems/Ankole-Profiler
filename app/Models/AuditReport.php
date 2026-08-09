<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'department_id',
        'audited_institution_name',
        'title',
        'audit_type',
        'period_start',
        'period_end',
        'issuing_body',
        'issue_date',
        'status',
        'overall_rating',
        'summary',
        'responsible_follow_up_owner_id',
        'restricted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'restricted' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function followUpOwner(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'responsible_follow_up_owner_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AuditDocument::class);
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(AuditReportAudience::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeForDiocese($query, $organizationIds)
    {
        return $query->whereIn('organization_id', $organizationIds);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('issuing_body', 'like', "%{$term}%");
        });
    }

    /**
     * Same logic shape as LandDocument::isVisibleTo(): full manager scope
     * always grants access; otherwise a restricted report requires an
     * explicit audience match. Audit reports have no "published to
     * institutions" concept, so restriction is the only extra gate beyond
     * manager scope — extended with a person_id branch beyond the
     * org/department/role shape used by Policy/LandParcel audiences.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $inManagerScope = $user->managedOrganizationIds()->contains($this->organization_id)
            || ($this->department_id && $user->managedDepartmentIds()->contains($this->department_id));

        if ($inManagerScope) {
            return true;
        }

        if (!$this->restricted) {
            return false;
        }

        $audiences = $this->relationLoaded('audiences') ? $this->audiences : $this->audiences()->get();

        if ($audiences->isEmpty()) {
            return false;
        }

        $affiliatedOrgIds = $user->activeAffiliations()->pluck('person_affiliations.organization_id')->unique();
        $userDepartmentIds = $user->managedDepartmentIds();
        $userRoleNames = $user->getRoleNames();
        $personId = $user->personId();

        return $audiences->contains(function (AuditReportAudience $audience) use ($affiliatedOrgIds, $userDepartmentIds, $userRoleNames, $personId) {
            if ($audience->organization_id) {
                return $affiliatedOrgIds->contains($audience->organization_id);
            }
            if ($audience->department_id) {
                return $userDepartmentIds->contains($audience->department_id);
            }
            if ($audience->role_name) {
                return $userRoleNames->contains($audience->role_name);
            }
            if ($audience->person_id) {
                return $personId !== null && $audience->person_id === $personId;
            }
            return false;
        });
    }
}
