<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAffiliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'person_id',
        'affiliation_type',
        'role_title',
        'occupation',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function department()
    {
        return $this->project?->department;
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($innerQuery) {
                $innerQuery->whereNull('end_date')->orWhere('end_date', '>', now());
            });
    }
}
