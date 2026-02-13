<?php
use Illuminate\Support\Facades\Auth;
use App\Models\PersonAffiliation;
use App\Models\User;

// app/Helpers/OrganizationHelper.php

use App\Models\Organization;
use Illuminate\Support\Facades\Log;

if (!function_exists('current_organization_id')) {
    /**
     * Get current Organization ID from session
     */
    function current_organization_id()
    {
        $user = Auth::user();
        if ($user && property_exists($user, 'organization_id')) {
            return session('current_organization_id', $user->organization_id);
        }
        return session('current_organization_id');
    }
}

if (!function_exists('current_Organization')) {
    /**
     * Get current Organization model
     */

    function current_Organization()
    {
        $id = current_organization_id();
        return $id ? Organization::find($id) : null;
    }
}

if (!function_exists('current_Organization_name')) {
    /**
     * Get current Organization name
     */
    function current_Organization_name()
    {
        return session('current_Organization_name', 'No Organization');
    }
}

if (!function_exists('can_access_Organization')) {
    /**
     * Check if current user can access an Organization
     */
    function can_access_Organization($OrganizationId)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->canAccessOrganization($OrganizationId);
    }
}

if (!function_exists('user_accessible_Organizations')) {
    // Get all Organizations current user can access
    function user_accessible_Organizations()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user ? $user->accessibleOrganizations() : collect();
    }
}


if (!function_exists('user_current_organization')) {
    function user_current_organization()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        Log::info('User Details:', ['user' => $user]);

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            Log::info('User is a Super Admin, no organization assigned.');
            return null;
        }

        // Fetch the person affiliation using user_id
        $affiliation = \App\Models\PersonAffiliation::where('user_id', $user->id)
            ->with('organization')
            ->first();

        Log::info('Person Affiliation Debug:', [
            'user_id' => $user->id,
            'affiliation' => $affiliation,
            'organization_id' => $affiliation?->organization_id ?? null,
            'organization_exists' => $affiliation?->organization ? true : false
        ]);

        if ($affiliation && $affiliation->organization) {
            return $affiliation->organization;
        }

        return null;
    }
}

if (!function_exists('user_current_organization_name')) {
    function user_current_organization_name()
    {
        $org = user_current_organization();
        Log::info('Current Organization Name Debug:', ['organization' => $org]);
        return $org?->legal_name ?? 'No organization';
    }
}

if (!function_exists('set_user_current_organization')) {
    function set_user_current_organization($organizationId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return false;
        }

        // Validate that the organization exists
        $organization = \App\Models\Organization::find($organizationId);
        if (!$organization) {
            return false;
        }

        // For Super Admin, just set the session (they can work with any organization)
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            session(['current_organization_id' => $organizationId]);
            session(['current_organization_name' => $organization->display_name ?? $organization->legal_name]);
            return true;
        }

        // For regular users, verify they have access to this organization through affiliations
        if (isset($user->person_id) && $user->person_id) {
            $hasAccess = \App\Models\PersonAffiliation::where('person_id', $user->person_id)
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->exists();

            if ($hasAccess) {
                session(['current_organization_id' => $organizationId]);
                session(['current_organization_name' => $organization->display_name ?? $organization->legal_name]);
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('get_user_accessible_organizations')) {
    function get_user_accessible_organizations()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return collect();
        }

        // Super Admin can access all organizations
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return \App\Models\Organization::where('is_active', true)
                ->orderBy('display_name')
                ->get();
        }

        // For regular users, get organizations through person affiliations
        if (isset($user->person_id) && $user->person_id) {
            return \App\Models\Organization::whereHas('affiliations', function($query) use ($user) {
                $query->where('person_id', $user->person_id)
                      ->where('status', 'active');
            })
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get();
        }

        // Fallback: if user has direct organization relationship
        if (isset($user->organization_id) && $user->organization_id) {
            $org = \App\Models\Organization::find($user->organization_id);
            return $org ? collect([$org]) : collect();
        }

        // No organizations accessible
        return collect();
    }
}



if (!function_exists('get_current_user_organization')) {
    /**
     * Get the organization details for the current user.
     *
     * @return \App\Models\Organization|null
     */
    function get_current_user_organization()
    {
        $user = Auth::user();

        if (!$user || !$user->person_id) {
            return null;
        }

        $affiliation = PersonAffiliation::where('person_id', $user->person_id)
            ->active()
            ->with('organization')
            ->first();

        return $affiliation ? $affiliation->organization : null;
    }
}
