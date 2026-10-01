<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the person directory, exports, relationship data and messaging to
 * users who hold a staff or admin role. A plain member (only the "Person"
 * role) or a pending applicant (no role) manages their own profile only.
 */
class EnsureStaffMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->getRoleNames()->reject(fn ($role) => $role === 'Person')->isNotEmpty(),
            403
        );

        return $next($request);
    }
}
