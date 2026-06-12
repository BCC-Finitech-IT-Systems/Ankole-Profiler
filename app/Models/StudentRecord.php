<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StudentRecord extends Model
{
    use HasUuids;

    protected $table = 'student_records';

    protected $fillable = [
        'affiliation_id',
        'student_number',
        'enrollment_date',
        'graduation_date',
        'current_class',
        'guardian_contact',
        'academic_notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'graduation_date' => 'date',
        'guardian_contact' => 'array',
        'academic_notes' => 'array',
    ];

    public function affiliation()
    {
        return $this->belongsTo(PersonAffiliation::class, 'affiliation_id');
    }

    public function person()
    {
        return $this->hasOneThrough(
            Person::class,
            PersonAffiliation::class,
            'id',
            'id',
            'affiliation_id',
            'person_id'
        );
    }

    /**
     * Check if student is currently enrolled
     */
    public function isCurrentlyEnrolled()
    {
        return $this->affiliation->status === 'active';
    }

    /**
     * Get primary guardian
     */
    public function getPrimaryGuardianAttribute()
    {
        return $this->guardian_contact['primary_guardian'] ?? null;
    }
}
