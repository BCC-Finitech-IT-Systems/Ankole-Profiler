<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkplanActivity extends Model
{
    use HasFactory;

    private const LOCKED_WORKPLAN_STATUSES = ['approved', 'in_progress', 'completed', 'deferred', 'cancelled'];

    /**
     * Fields that may still change once the parent workplan is locked —
     * lifecycle fields only, always set together via recordProgress(),
     * never a bare edit of the planned content.
     */
    private const IMMUTABLE_ALLOWLIST = ['status', 'percent_complete', 'updated_by', 'updated_at'];

    private const OPEN_STATUSES = ['not_started', 'in_progress', 'deferred'];

    protected $fillable = [
        'workplan_id',
        'strategic_objective',
        'activity',
        'expected_output',
        'performance_indicator',
        'baseline',
        'target',
        'start_date',
        'end_date',
        'priority',
        'budget_estimate',
        'funding_source',
        'responsible_person_id',
        'responsible_team',
        'dependencies',
        'status',
        'percent_complete',
        'carried_forward_from_activity_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_estimate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (WorkplanActivity $activity) {
            if (!$activity->exists) {
                return;
            }

            $workplan = $activity->relationLoaded('workplan') ? $activity->workplan : $activity->workplan()->first();

            if (!in_array($workplan?->status, self::LOCKED_WORKPLAN_STATUSES, true)) {
                return;
            }

            $disallowedChanges = array_diff(
                array_keys($activity->getDirty()),
                self::IMMUTABLE_ALLOWLIST
            );

            if (!empty($disallowedChanges)) {
                throw new \DomainException(
                    'This activity belongs to an approved workplan; planned content is locked. Report progress instead.'
                );
            }
        });
    }

    public function workplan(): BelongsTo
    {
        return $this->belongsTo(Workplan::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(WorkplanProgressUpdate::class)->orderBy('reported_on');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(WorkplanDocument::class, 'documentable')->where('kind', 'supporting');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isOverdue(): bool
    {
        return $this->end_date !== null
            && $this->end_date->isPast()
            && in_array($this->status, self::OPEN_STATUSES, true);
    }
}
