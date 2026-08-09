<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandParcelUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_parcel_id',
        'reported_on',
        'notes',
        'blockers',
        'next_action',
        'revised_expected_completion_date',
        'reported_by',
    ];

    protected $casts = [
        'reported_on' => 'date',
        'revised_expected_completion_date' => 'date',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
