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
        'department_id',
        'name',
        'code',
        'description',
        'parent_unit_id',
        'is_active',
        // Step 1: Basic Info
        'unit_type',
        'community',
        'ministry_committee',
        'administrative_office',
        // Step 2: Leadership & Governance
        'unit_head',
        'assistant_leader',
        'leadership_committee',
        'appointment_dates',
        'reporting_line',
        // Step 3: Purpose & Mission
        'mission',
        'objectives',
        'activities',
        'target_audience',
        // Step 4: Contact Information
        'official_email',
        'phone_contact',
        'physical_location',
        'website',
        'social_links',
        // Step 5: Operational Details
        'unit_category',
        'operational_status',
        'date_established',
        'faith_based',
        'socio_economic',
        'support_services',
        // Step 6: Membership Metadata
        'membership_type',
        'membership_eligibility',
        'membership_capacity',
        'join_requests_enabled',
        // Step 7: Events & Programs Metadata
        'recurring_programs',
        'event_schedule',
        'promotion_permissions',
        'resource_access_requirements',
        // Step 8: Showcase & Marketplace Support
        'showcase_permissions',
        'product_categories_allowed',
        'approval_workflow',
        'commission_structure',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_unit_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_unit_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'organization_unit_id');
    }

    public function personAffiliations()
    {
        return $this->hasMany(PersonAffiliation::class, 'organization_unit_id');
    }

    public function personRoles()
    {
        return $this->hasMany(UnitPersonRole::class, 'unit_id');
    }

    public function persons()
    {
        return $this->belongsToMany(Person::class, 'unit_person_roles', 'unit_id', 'person_id')
            ->withPivot(['role', 'can_view', 'can_edit', 'can_approve', 'granted_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function activePersons()
    {
        return $this->persons()->wherePivotNull('revoked_at');
    }

    public function allDescendantUnits()
    {
        return $this->children()->with('allDescendantUnits');
    }
}
