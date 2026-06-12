<?php
// app/Http/Middleware/SetOrganizationContext.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Organization;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // If no Organization in session, set user's primary org
            if (!session()->has('current_organization_id')) {
                $this->setDefaultOrganization($user);
            }
            
            // Verify user still has access to current Organization
            $currentOrgId = session('current_organization_id');
            if ($currentOrgId && !$user->canAccessOrganization($currentOrgId)) {
                // User lost access, reset to primary Organization
                $this->setDefaultOrganization($user);
            }
            
            // Store Organization details for easy access
            if (session('current_organization_id')) {
                $org = Organization::find(session('current_organization_id'));
                if ($org) {
                    session([
                        'current_Organization_name' => $org->display_name,
                        'current_Organization_code' => $org->code,
                        'current_Organization_logo' => $org->logo_path,
                    ]);
                    
                    // Share with all views
                    view()->share('currentOrganization', $org);
                }
            }
        }
        
        return $next($request);
    }

    /**
     * Set user's default/primary Organization
     */
    private function setDefaultOrganization($user)
    {
        $defaultOrg = $user->accessibleOrganizations()->first()?->id;

        if ($defaultOrg) {
            session(['current_organization_id' => $defaultOrg]);
        }
    }
}
