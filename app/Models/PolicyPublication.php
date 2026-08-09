<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PolicyPublication extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_version_id',
        'policy_id',
        'organization_id',
        'status',
        'due_date',
        'sent_at',
        'acknowledged_at',
        'acknowledged_by',
        'adoption_date',
        'responsible_person_id',
        'implementation_notes',
        'exception_reason',
        'exception_status',
        'exception_decided_by',
        'exception_decided_at',
        'last_reminder_sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'adoption_date' => 'date',
        'exception_decided_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (PolicyPublication $publication) {
            if (!$publication->exists || !$publication->isDirty('status')) {
                return;
            }

            $from = $publication->getOriginal('status');
            $to = $publication->status;

            $terminalAdoption = ['adopted', 'partially_adopted'];
            $regressive = ['not_sent', 'sent'];

            if (in_array($from, $terminalAdoption, true) && in_array($to, $regressive, true)) {
                throw new \DomainException("Cannot move adoption status from {$from} back to {$to}.");
            }
        });
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function exceptionDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exception_decided_by');
    }

    public function evidence(): MorphMany
    {
        return $this->morphMany(PolicyDocument::class, 'documentable')->where('kind', 'evidence');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['adopted', 'partially_adopted'])
            ->where('exception_status', '!=', 'approved');
    }

    public function scopeForInstitutions($query, $organizationIds)
    {
        return $query->whereIn('organization_id', $organizationIds);
    }
}
