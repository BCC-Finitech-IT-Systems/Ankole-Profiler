<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Workplan extends Model
{
    use HasFactory, SoftDeletes;

    private const LOCKED_STATUSES = ['approved', 'in_progress', 'completed', 'deferred', 'cancelled'];

    private const IMMUTABLE_ALLOWLIST = [
        'status', 'review_comment', 'decision_comment',
        'submitted_at', 'submitted_by', 'approved_at', 'approved_by',
        'updated_at',
    ];

    /**
     * Activity statuses that represent unfinished work — the set carried
     * forward into a new year by carryForward().
     */
    private const CARRY_FORWARD_ACTIVITY_STATUSES = ['not_started', 'in_progress', 'deferred'];

    protected $fillable = [
        'department_id',
        'organization_id',
        'year',
        'version_number',
        'title',
        'status',
        'review_comment',
        'decision_comment',
        'supersedes_workplan_id',
        'carried_forward_from_id',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Workplan $workplan) {
            if (!$workplan->exists) {
                return;
            }

            $originalStatus = $workplan->getOriginal('status');

            if (!in_array($originalStatus, self::LOCKED_STATUSES, true)) {
                return;
            }

            $disallowedChanges = array_diff(
                array_keys($workplan->getDirty()),
                self::IMMUTABLE_ALLOWLIST
            );

            if (!empty($disallowedChanges)) {
                throw new \DomainException(
                    'Approved workplans are immutable; create a revision instead.'
                );
            }
        });

        static::deleting(function (Workplan $workplan) {
            if (in_array($workplan->getOriginal('status'), self::LOCKED_STATUSES, true)) {
                throw new \DomainException('Approved workplans cannot be deleted.');
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkplanActivity::class)->orderBy('id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Workplan::class, 'supersedes_workplan_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(Workplan::class, 'supersedes_workplan_id');
    }

    public function carriedForwardFrom(): BelongsTo
    {
        return $this->belongsTo(Workplan::class, 'carried_forward_from_id');
    }

    public function carriedForwardTo(): HasMany
    {
        return $this->hasMany(Workplan::class, 'carried_forward_from_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    public function scopeForDepartments($query, $departmentIds)
    {
        return $query->whereIn('department_id', $departmentIds);
    }

    /**
     * Copy unfinished activities forward into a new draft workplan for
     * $year + 1. The source workplan and its activities are never mutated —
     * this is a copy, not a move, preserving the original plan and history.
     */
    public function carryForward(): Workplan
    {
        return DB::transaction(function () {
            $newWorkplan = Workplan::create([
                'department_id' => $this->department_id,
                'organization_id' => $this->organization_id,
                'year' => $this->year + 1,
                'version_number' => 1,
                'title' => 'FY' . ($this->year + 1) . ' Workplan',
                'status' => 'draft',
                'carried_forward_from_id' => $this->id,
                'created_by' => Auth::id(),
            ]);

            $this->activities()
                ->whereIn('status', self::CARRY_FORWARD_ACTIVITY_STATUSES)
                ->get()
                ->each(function (WorkplanActivity $activity) use ($newWorkplan) {
                    WorkplanActivity::create([
                        'workplan_id' => $newWorkplan->id,
                        'strategic_objective' => $activity->strategic_objective,
                        'activity' => $activity->activity,
                        'expected_output' => $activity->expected_output,
                        'performance_indicator' => $activity->performance_indicator,
                        'baseline' => $activity->baseline,
                        'target' => $activity->target,
                        'start_date' => $activity->start_date,
                        'end_date' => $activity->end_date,
                        'priority' => $activity->priority,
                        'budget_estimate' => $activity->budget_estimate,
                        'funding_source' => $activity->funding_source,
                        'responsible_person_id' => $activity->responsible_person_id,
                        'responsible_team' => $activity->responsible_team,
                        'dependencies' => $activity->dependencies,
                        'status' => 'not_started',
                        'carried_forward_from_activity_id' => $activity->id,
                        'created_by' => Auth::id(),
                    ]);
                });

            return $newWorkplan;
        });
    }
}
