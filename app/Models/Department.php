<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'sub_category',
        'code',
        'description',
        'admin_user_id',
        'legacy_organization_unit_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Units that belong to this department (via the new direct FK on organization_units).
     */
    public function units()
    {
        return $this->hasMany(OrganizationUnit::class, 'department_id');
    }

    /**
     * Kept for backward compatibility with pre-migration data.
     * Prefer units() for new code.
     */
    public function legacyUnit()
    {
        return $this->belongsTo(OrganizationUnit::class, 'legacy_organization_unit_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function subCategories()
    {
        return $this->hasMany(DepartmentSubCategory::class);
    }

    public function roleTypes()
    {
        return $this->hasMany(RoleType::class);
    }

    public function personAffiliations()
    {
        return $this->hasMany(PersonAffiliation::class);
    }

    public function projectAffiliations()
    {
        return $this->hasManyThrough(ProjectAffiliation::class, Project::class, 'department_id', 'project_id');
    }
}
