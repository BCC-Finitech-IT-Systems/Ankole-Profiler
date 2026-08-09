<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditDocument extends Model
{
    protected $fillable = [
        'audit_report_id',
        'document_type',
        'version_number',
        'is_current',
        'path',
        'original_name',
        'mime',
        'size',
        'hash',
        'uploaded_by',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AuditReport::class, 'audit_report_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
