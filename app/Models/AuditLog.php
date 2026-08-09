<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'auditable_type',
        'auditable_id',
        'event',
        'actor_user_id',
        'subject_organization_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new \DomainException('Audit log entries are immutable.');
        });

        static::deleting(function () {
            throw new \DomainException('Audit log entries cannot be deleted.');
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subjectOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'subject_organization_id');
    }
}
