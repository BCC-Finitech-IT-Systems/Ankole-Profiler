<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'department_id',
        'reference_code',
        'title',
        'policy_category',
        'status',
        'current_version_id',
        'created_by',
        'updated_by',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class)->orderBy('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class)->ofMany('version_number', 'max');
    }

    public function publications(): HasManyThrough
    {
        return $this->hasManyThrough(PolicyPublication::class, PolicyVersion::class);
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
              ->orWhere('reference_code', 'like', "%{$term}%");
        });
    }
}
