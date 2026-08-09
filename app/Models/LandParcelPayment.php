<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandParcelPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_parcel_id',
        'amount',
        'paid_on',
        'payee',
        'purpose',
        'receipt_reference',
        'recorded_by',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
