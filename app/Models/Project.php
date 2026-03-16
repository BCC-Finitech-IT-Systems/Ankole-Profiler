<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'department_sub_category_id',
        'name',
        'sub_category',
        'code',
        'description',
        'admin_user_id',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentSubCategory()
    {
        return $this->belongsTo(DepartmentSubCategory::class, 'department_sub_category_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function affiliations()
    {
        return $this->hasMany(ProjectAffiliation::class);
    }

    public function projectDepartments()
    {
        return $this->hasMany(ProjectDepartment::class);
    }

    public function persons()
    {
        return $this->belongsToMany(Person::class, 'project_affiliations')
            ->withPivot(['affiliation_type', 'role_title', 'occupation', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }

    public function getOrganizationAttribute()
    {
        return $this->department?->organization;
    }
}
