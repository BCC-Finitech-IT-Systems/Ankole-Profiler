<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandParcel extends Model
{
    use HasFactory, SoftDeletes;

    private const OPEN_STAGES_EXCLUDING_CLOSED = ['title_issued', 'closed'];

    /**
     * Fields whose changes get an individual audit_logs row — same pattern
     * as Assignment::logFieldChanges(), no separate version table.
     */
    private const TRACKED_FIELDS = [
        'stage', 'responsible_person_id', 'expected_completion_date', 'title_verification_status',
    ];

    protected $fillable = [
        'organization_id',
        'department_id',
        'reference_number',
        'property_name',
        'location',
        'district',
        'sub_county',
        'parish',
        'village',
        'acreage',
        'latitude',
        'longitude',
        'tenure_type',
        'current_use',
        'acquisition_date',
        'acquisition_details',
        'stage',
        'application_reference',
        'land_office',
        'submitted_at',
        'expected_completion_date',
        'responsible_person_id',
        'external_advocate',
        'external_surveyor',
        'next_action',
        'blockers',
        'last_reminder_sent_at',
        'title_number',
        'title_volume_folio',
        'title_issue_date',
        'registered_proprietor',
        'encumbrances',
        'lease_expiry_date',
        'title_verification_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'submitted_at' => 'date',
        'expected_completion_date' => 'date',
        'title_issue_date' => 'date',
        'lease_expiry_date' => 'date',
        'last_reminder_sent_at' => 'datetime',
    ];

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

    public function updates(): HasMany
    {
        return $this->hasMany(LandParcelUpdate::class)->orderBy('reported_on');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LandParcelPayment::class)->orderBy('paid_on');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LandDocument::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isStalled(): bool
    {
        return $this->expected_completion_date !== null
            && $this->expected_completion_date->isPast()
            && !in_array($this->stage, self::OPEN_STAGES_EXCLUDING_CLOSED, true);
    }

    public function isLeaseExpiringSoon(int $days = 90): bool
    {
        return $this->lease_expiry_date !== null
            && !$this->lease_expiry_date->isPast()
            && $this->lease_expiry_date->lte(now()->addDays($days));
    }

    /**
     * The latest is_current row per document_type, keyed by type.
     */
    public function currentDocumentsByType()
    {
        return $this->documents()->where('is_current', true)->get()->keyBy('document_type');
    }

    public function logFieldChanges(array $before): void
    {
        foreach (self::TRACKED_FIELDS as $field) {
            $old = $before[$field] ?? null;
            $new = $this->{$field};

            if ((string) $old === (string) $new) {
                continue;
            }

            AuditLogger::record($this, 'land_parcel.field_changed', [
                'field' => $field,
                'old' => $old,
                'new' => $new,
            ], $this->organization_id);
        }
    }

    public static function trackedFieldSnapshot(self $parcel): array
    {
        return collect(self::TRACKED_FIELDS)->mapWithKeys(fn ($field) => [$field => $parcel->{$field}])->all();
    }
}
