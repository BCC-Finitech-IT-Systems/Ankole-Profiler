<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_team_id',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * PersonAffiliation relationship (links user to their organization via person affiliation)
     */
    public function personAffiliation()
    {
        return $this->hasOneThrough(
            \App\Models\PersonAffiliation::class,
            \App\Models\Person::class,
            'user_id', // Foreign key on the persons table
            'person_id', // Foreign key on the person_affiliations table
            'id', // Local key on the users table
            'id'  // Local key on the persons table
        )->where('person_affiliations.status', 'active')->latest('person_affiliations.created_at');
    }

    // Resolved through user → person → active affiliation → organization.
    // Not an Eloquent relation; use eager loading on personAffiliation instead.
    public function getOrganizationAttribute(): ?Organization
    {
        return $this->personAffiliation?->organization;
    }

    /**
     * Person relationship (links user to person record)
     */
    public function person()
    {
        return $this->hasOne(\App\Models\Person::class, 'user_id');
    }

    /**
     * The id of the linked Person record, or null when the user has no
     * Person (e.g. seeded system accounts). Users link to persons via
     * persons.user_id — there is no person_id column on users.
     */
    public function personId(): ?int
    {
        return $this->person?->id;
    }

    /**
     * Active affiliations of the linked person.
     */
    public function activeAffiliations()
    {
        return $this->hasManyThrough(
            PersonAffiliation::class,
            Person::class,
            'user_id',   // Foreign key on persons
            'person_id', // Foreign key on person_affiliations
            'id',
            'id'
        )->where('person_affiliations.status', 'active');
    }

    /**
     * Ids of organizations this user is directly affiliated with (active
     * affiliations only) — no tree expansion. Tenancy in this app is
     * hierarchical (diocese -> member organizations/institutions), so most
     * callers want organizationSubtreeIds() below instead of this.
     */
    private function directlyAffiliatedOrganizationIds(): \Illuminate\Support\Collection
    {
        return $this->activeAffiliations()
            ->pluck('person_affiliations.organization_id')
            ->unique()
            ->values();
    }

    /**
     * Ids of organizations this user is affiliated with, plus every
     * descendant of each (via Organization::subtreeIds()). A diocese is
     * itself an Organization row, so affiliating with the diocese must
     * also grant its whole tree of member organizations/institutions —
     * tenancy starts at the diocese, not at one row in the org table.
     */
    private function organizationSubtreeIds(): \Illuminate\Support\Collection
    {
        return $this->directlyAffiliatedOrganizationIds()
            ->flatMap(fn ($orgId) => Organization::find($orgId)?->subtreeIds() ?? collect())
            ->unique()
            ->values();
    }

    /**
     * Ids of organizations this user administers: organizations where the
     * user holds an admin-capable role AND has an active affiliation,
     * expanded to include every organization under them. Super Admin
     * manages all active organizations.
     */
    public function managedOrganizationIds(): \Illuminate\Support\Collection
    {
        return $this->memoize(__FUNCTION__, function () {
            if ($this->hasRole('Super Admin')) {
                return Organization::where('is_active', true)->pluck('id');
            }

            // Organization-wide scope is reserved for org-level admins;
            // Department Managers act through managedDepartmentIds().
            if (!$this->hasRole('Organization Admin')) {
                return collect();
            }

            return $this->organizationSubtreeIds();
        });
    }

    /**
     * Ids of departments this user administers: every department inside an
     * organization they administer (Organization Admin), departments from
     * direct affiliations (Department Manager), and departments they head
     * directly.
     */
    public function managedDepartmentIds(): \Illuminate\Support\Collection
    {
        return $this->memoize(__FUNCTION__, function () {
            if ($this->hasRole('Super Admin')) {
                return Department::pluck('id');
            }

            $headed = $this->headedDepartments()->pluck('id');

            if (!$this->hasAnyRole(['Organization Admin', 'Department Manager'])) {
                return $headed->unique()->values();
            }

            $viaOrganizations = $this->hasRole('Organization Admin')
                ? Department::whereIn('organization_id', $this->managedOrganizationIds())->pluck('id')
                : collect();

            return $this->activeAffiliations()
                ->whereNotNull('person_affiliations.department_id')
                ->pluck('person_affiliations.department_id')
                ->merge($headed)
                ->merge($viaOrganizations)
                ->unique()
                ->values();
        });
    }

    /**
     * Per-request memoization for scope lookups used in policies/loops.
     */
    private array $memoized = [];

    private function memoize(string $key, \Closure $resolver)
    {
        return $this->memoized[$key] ??= $resolver();
    }

    /**
     * Get all organizations this user can access
     * Based on their person's affiliations
     */
    public function accessibleOrganizations()
    {
        // Super Admin can access ALL organizations
        if ($this->hasRole('Super Admin')) {
            return Organization::where('is_active', true)
                               ->orderBy('display_name')
                               ->get();
        }

        $ids = $this->organizationSubtreeIds();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Organization::whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Check if the user can access a specific organization — either
     * directly affiliated, or a descendant of an organization they're
     * affiliated with (tenancy flows down from the diocese).
     *
     * @param int $organizationId
     * @return bool
     */
    public function canAccessOrganization(int $organizationId): bool
    {
        // Super Admin can access all organizations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->organizationSubtreeIds()->contains($organizationId);
    }

    /**
     * Get user's role in a specific organization
     */
    public function getRoleInOrganization($organizationId)
    {
        $personId = $this->personId();
        if (!$personId) {
            return null;
        }

        $affiliation = PersonAffiliation::where('person_id', $personId)
                                       ->where('organization_id', $organizationId)
                                       ->active()
                                       ->with('roleType')
                                       ->first();

        return $affiliation ? $affiliation->roleType : null;
    }

    public function sendEmailVerificationNotification($temporaryPassword = null)
    {
        $this->notify(new CustomVerifyEmail($temporaryPassword));
    }

    public function headedDepartments()
    {
        return $this->hasMany(Department::class, 'admin_user_id');
    }

    public function headedProjects()
    {
        return $this->hasMany(Project::class, 'admin_user_id');
    }
}
