<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the request only when the signed-in user has one of the given roles.
 *
 * Usage in routes:
 *   ->middleware('role:admin')    admins only
 *   ->middleware('role:renter')   renters only
 *
 * Registered as the 'role' alias in bootstrap/app.php.
 */
class EnsureUserHasRole
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$roles  allowed roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this page.');
        }

        return $next($request);
    }
}
