<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitPersonRole extends Model
{
    use HasFactory;

    protected $table = 'unit_person_roles';

    protected $fillable = [
        'unit_id',
        'person_id',
        'role',
        'role_type_id',
        'can_view',
        'can_edit',
        'can_approve',
        'granted_by',
        'granted_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_edit'   => 'boolean',
        'can_approve' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(OrganizationUnit::class, 'unit_id');
    }

    public function roleType()
    {
        return $this->belongsTo(RoleType::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function permissionGrants()
    {
        return $this->morphMany(PermissionGrant::class, 'grantable');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeForPerson($query, $personId)
    {
        return $query->where('person_id', $personId);
    }
}
