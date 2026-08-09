<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkplanProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'workplan_activity_id',
        'reported_on',
        'percent_complete',
        'work_completed',
        'pending_work',
        'challenges',
        'corrective_action',
        'expenditure',
        'reported_by',
    ];

    protected $casts = [
        'reported_on' => 'date',
        'expenditure' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(WorkplanActivity::class, 'workplan_activity_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function evidence(): MorphMany
    {
        return $this->morphMany(WorkplanDocument::class, 'documentable')->where('kind', 'evidence');
    }
}
