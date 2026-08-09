<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_parcel_id',
        'document_type',
        'version_number',
        'is_current',
        'restricted',
        'custody_location',
        'path',
        'original_name',
        'mime',
        'size',
        'hash',
        'uploaded_by',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'restricted' => 'boolean',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(LandDocumentAudience::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Same logic shape as PolicyVersion::isVisibleTo(): full manager scope
     * always grants access; otherwise a restricted document requires an
     * explicit audience match (organization = active affiliation, since
     * land documents have no "published to institutions" concept to lean
     * on the way Policy versions do).
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $parcel = $this->relationLoaded('parcel') ? $this->parcel : $this->parcel()->first();

        $inManagerScope = $user->managedOrganizationIds()->contains($parcel->organization_id)
            || ($parcel->department_id && $user->managedDepartmentIds()->contains($parcel->department_id));

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

        return $audiences->contains(function (LandDocumentAudience $audience) use ($affiliatedOrgIds, $userDepartmentIds, $userRoleNames) {
            if ($audience->organization_id) {
                return $affiliatedOrgIds->contains($audience->organization_id);
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
