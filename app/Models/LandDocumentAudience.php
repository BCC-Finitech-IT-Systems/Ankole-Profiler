<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandDocumentAudience extends Model
{
    protected $fillable = [
        'land_document_id',
        'organization_id',
        'department_id',
        'role_name',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (LandDocumentAudience $audience) {
            $targets = array_filter([
                $audience->organization_id,
                $audience->department_id,
                $audience->role_name,
            ], fn ($v) => !is_null($v) && $v !== '');

            if (count($targets) !== 1) {
                throw new \DomainException(
                    'A land document audience row must set exactly one of organization, department, or role.'
                );
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(LandDocument::class, 'land_document_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
