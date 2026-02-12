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
        'temporary_password', 
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
        );
    }

    public function Organization()
    {
        return $this->hasOneThrough(
            Organization::class,
            PersonAffiliation::class,
            'person_id', // Foreign key on person_affiliations table
            'id', // Foreign key on organizations table
            'id', // Local key on users table
            'organization_id' // Local key on person_affiliations table
        );
    }

    /**
     * Person relationship (links user to person record)
     */
    public function person()
    {
        return $this->hasOne(\App\Models\Person::class, 'user_id');
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

        // Regular users: get organizations from their affiliations
        if (!$this->person_id) {
            // No person linked - only their primary organization
            return collect([$this->organization]);
        }

        return Organization::whereHas('affiliations', function($query) {
            $query->where('person_id', $this->person_id)
                  ->where('status', 'ACTIVE');
        })
    ->where('is_active', true)
        ->orderBy('display_name')
        ->get();
    }

    /**
     * Check if the user can access a specific organization.
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

        // Check if the user has an active affiliation with the organization
        return PersonAffiliation::where('person_id', $this->person_id)
            ->where('organization_id', $organizationId)
            ->active()
            ->exists();
    }

    /**
     * Get user's role in a specific organization
     */
    public function getRoleInOrganization($organizationId)
    {
        if (!$this->person_id) {
            return null;
        }

        $affiliation = PersonAffiliation::where('person_id', $this->person_id)
                                       ->where('organization_id', $organizationId)
                                       ->where('status', 'ACTIVE')
                                       ->with('roleType')
                                       ->first();

        return $affiliation ? $affiliation->roleType : null;
    }

    public function sendEmailVerificationNotification($temporaryPassword = null)
    {
        $this->notify(new CustomVerifyEmail($temporaryPassword));
    }

    /**
     * Get the user's temporary password.
     */
    public function getTemporaryPassword(): ?string
    {
        return $this->temporary_password;
    }
}
