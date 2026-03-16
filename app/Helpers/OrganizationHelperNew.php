<?php

namespace App\Helpers;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

class OrganizationHelperNew
{
    /**
     * Get the current organization for the authenticated user
     */
    public static function getCurrentOrganization(): ?Organization
    {
        // First try to get from session
        $OrganizationId = session('current_organization_id');

        if (!$OrganizationId && Auth::check()) {
            // Check PersonAffiliation table for organization details
            $person = Auth::user()->person;
            if ($person) {
                $affiliation = $person->affiliations()->first();
                if ($affiliation) {
                    $OrganizationId = $affiliation->organization_id;
                }
            }

            if (!$OrganizationId) {
                logger()->warning('No organization found in PersonAffiliation for user: ' . Auth::id());
            }
        }

        if ($OrganizationId) {
            $organization = Organization::find($OrganizationId);

            if (!$organization) {
                logger()->error('Organization not found for ID: ' . $OrganizationId);
            }

            return $organization;
        }

        logger()->error('Unable to determine current organization for user: ' . (Auth::id() ?? 'guest'));
        return null;
    }

    /**
     * Get current Organization ID from session or user
     */
    public static function getCurrentOrganizationId(): ?int
    {
        return session('current_organization_id', Auth::user()->organization_id ?? null);
    }

    /**
     * Get current Organization name
     */
    public static function getCurrentOrganizationName(): string
    {
        $org = self::getCurrentOrganization();
        return $org ? $org->name : 'No Organization';
    }

    /**
     * Set the current organization in session
     */
    public static function setCurrentOrganization(int $OrganizationId): void
    {
        $Organization = Organization::find($OrganizationId);

        if ($Organization && self::canAccessOrganization($OrganizationId)) {
            session([
                'current_organization_id' => $OrganizationId,
                'current_Organization_name' => $Organization->name
            ]);
        }
    }

    /**
     * Check if current user can access an Organization
     */
    public static function canAccessOrganization(int $OrganizationId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Check if it's the user's default organization
        if ($user->organization_id === $OrganizationId) {
            return true;
        }

        // Check if user has access through affiliations or other relationships
        // For now, just return true if user exists - you can expand this logic
        return true;
    }

    /**
     * Get all Organizations current user can access
     */
    public static function getUserAccessibleOrganizations()
    {
        if (!Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        // Get user's primary organization
        $organizations = collect();

        if ($user->organization_id) {
            $primaryOrg = Organization::find($user->organization_id);
            if ($primaryOrg) {
                $organizations->push($primaryOrg);
            }
        }

        // Add any additional organizations the user has access to
        // This depends on your business logic and relationships

        return $organizations;
    }

    /**
     * Switch to a different organization
     */
    public static function switchOrganization(int $OrganizationId): bool
    {
        if (self::canAccessOrganization($OrganizationId)) {
            self::setCurrentOrganization($OrganizationId);
            return true;
        }

        return false;
    }

    /**
     * Clear current organization from session
     */
    public static function clearCurrentOrganization(): void
    {
        session()->forget(['current_organization_id', 'current_Organization_name']);
    }
}
