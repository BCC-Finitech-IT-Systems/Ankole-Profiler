<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    private const OPEN_STATUSES = ['not_started', 'in_progress', 'blocked', 'awaiting_review'];

    /**
     * Fields whose changes get an individual audit_logs row (old -> new) —
     * satisfies "maintain assignment history when ownership, scope,
     * priority, deadline, or status changes" without a separate history
     * table. Called explicitly from the component after ->update(), not a
     * model boot hook, so callers control when a change is "official".
     */
    private const TRACKED_FIELDS = [
        'responsible_person_id', 'assignable_type', 'assignable_id',
        'department_id', 'priority', 'due_date', 'status',
    ];

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'organization_id',
        'department_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'start_date',
        'due_date',
        'revised_due_date',
        'expected_result',
        'dependencies',
        'percent_complete',
        'responsible_person_id',
        'review_comment',
        'last_reminder_sent_at',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'revised_due_date' => 'date',
        'last_reminder_sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    public function supportPeople(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'assignment_people')
            ->wherePivot('role', 'support')
            ->withTimestamps();
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'assignment_people')
            ->wherePivot('role', 'watcher')
            ->withTimestamps();
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(AssignmentProgressUpdate::class)->orderBy('reported_on');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(AssignmentDocument::class, 'documentable')->where('kind', 'supporting');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isOverdue(): bool
    {
        $effectiveDueDate = $this->revised_due_date ?? $this->due_date;

        return $effectiveDueDate !== null
            && $effectiveDueDate->isPast()
            && in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isEditable(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled'], true);
    }

    /**
     * Diff $before (a snapshot of tracked fields taken prior to ->update())
     * against the current values and write one audit row per changed field.
     */
    public function logFieldChanges(array $before): void
    {
        foreach (self::TRACKED_FIELDS as $field) {
            $old = $before[$field] ?? null;
            $new = $this->{$field};

            if ((string) $old === (string) $new) {
                continue;
            }

            AuditLogger::record($this, 'assignment.field_changed', [
                'field' => $field,
                'old' => $old,
                'new' => $new,
            ], $this->organization_id);
        }
    }

    public static function trackedFieldSnapshot(self $assignment): array
    {
        return collect(self::TRACKED_FIELDS)->mapWithKeys(fn ($field) => [$field => $assignment->{$field}])->all();
    }
}
