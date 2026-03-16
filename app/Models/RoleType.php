<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasPermissions;

class RoleType extends Model
{
    use HasFactory, HasPermissions;

    /**
     * The guard name for Spatie permissions
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'department_id',
        'organization_id', // Keep for backward compatibility if needed
        'code',
        'name',
        'description',
        'active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the department that owns the role type.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the organization through the department.
     * This provides backward compatibility and easy access to organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get affiliations for this role type.
     * Adjust the model name based on your actual affiliations setup.
     */
    public function affiliations(): HasMany
    {
        return $this->hasMany(Affiliation::class);
    }

    /**
     * Check if role type has active affiliations.
     */
    public function hasActiveAffiliations(): bool
    {
        return $this->affiliations()
            ->where('active', true)
            ->exists();
    }

    /**
     * Get count of active affiliations.
     */
    public function activeAffiliationsCount(): int
    {
        return $this->affiliations()
            ->where('active', true)
            ->count();
    }

    /**
     * Scope to filter active role types.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter by department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by organization (through department).
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->whereHas('department', function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        });
    }
}
