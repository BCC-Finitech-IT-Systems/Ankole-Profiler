<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationUnit extends Model
{
    use HasFactory;

    protected $table = 'organization_units';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'parent_unit_id',
        'is_active',
    ];

    /**
     * Parent unit relationship
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_unit_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Children units relationship
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_unit_id');
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'legacy_organization_unit_id');
    }
}
