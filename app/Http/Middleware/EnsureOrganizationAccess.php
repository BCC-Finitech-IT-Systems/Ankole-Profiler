<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $currentOrgId = session('current_organization_id');
        
        if (!$currentOrgId) {
            session()->flash('error', 'No Organization selected.');
            return redirect()->route('dashboard');
        }

        // Verify user has access to current Organization
        if (!auth()->user()->canAccessOrganization($currentOrgId)) {
            session()->flash('error', 'You do not have access to this Organization.');

            // Drop the stale selection; SetOrganizationContext will pick a
            // valid default organization on the next request.
            session()->forget('current_organization_id');

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
