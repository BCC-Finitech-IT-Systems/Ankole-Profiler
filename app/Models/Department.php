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

    public function legacyOrganizationUnit()
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

    public function projectAffiliations()
    {
        return $this->hasManyThrough(ProjectAffiliation::class, Project::class, 'department_id', 'project_id');
    }
}
