<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks renters whose registration is still pending or was rejected.
 *
 * They are redirected to the verification page, which only offers a logout button.
 * Admins always pass. Registered as the 'approved' alias in bootstrap/app.php.
 */
class EnsureRenterIsApproved
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            return redirect()->route('account.verification');
        }

        return $next($request);
    }
}
