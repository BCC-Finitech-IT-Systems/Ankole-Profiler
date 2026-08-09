<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReportAudience extends Model
{
    protected $fillable = [
        'audit_report_id',
        'organization_id',
        'department_id',
        'role_name',
        'person_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (AuditReportAudience $audience) {
            $targets = array_filter([
                $audience->organization_id,
                $audience->department_id,
                $audience->role_name,
                $audience->person_id,
            ], fn ($v) => !is_null($v) && $v !== '');

            if (count($targets) !== 1) {
                throw new \DomainException(
                    'An audit report audience row must set exactly one of organization, department, role, or person.'
                );
            }
        });
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(AuditReport::class, 'audit_report_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
