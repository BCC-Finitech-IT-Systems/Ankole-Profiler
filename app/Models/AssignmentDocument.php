<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssignmentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'kind',
        'title',
        'path',
        'original_name',
        'mime',
        'size',
        'hash',
        'uploaded_by',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
