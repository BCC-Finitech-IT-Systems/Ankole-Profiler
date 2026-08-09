<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AssignmentProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'reported_on',
        'percent_complete',
        'notes',
        'blockers',
        'next_steps',
        'time_spent_minutes',
        'revised_due_date',
        'reported_by',
    ];

    protected $casts = [
        'reported_on' => 'date',
        'revised_due_date' => 'date',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function evidence(): MorphMany
    {
        return $this->morphMany(AssignmentDocument::class, 'documentable')->where('kind', 'evidence');
    }
}
