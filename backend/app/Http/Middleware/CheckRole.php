<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorize the current user against the roles listed on a route.
 *
 * Usage: Route::middleware(['auth', 'role:admin,tenant'])
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            abort(401);
        }

        if (!in_array($user->role, $roles, true)) {
            $message = 'Forbidden. This endpoint requires one of these roles: ' . implode(', ', $roles) . '.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'your_role' => $user->role,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
