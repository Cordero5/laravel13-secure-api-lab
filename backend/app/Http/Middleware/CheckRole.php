<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorization middleware: after Sanctum has proven *who* the caller is,
 * this decides *what* that caller is allowed to reach.
 *
 * Usage: Route::middleware(['auth:sanctum', 'role:admin,tenant'])
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Defensive: auth:sanctum already stops anonymous callers, but the
        // middleware must not assume it was paired with it.
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden. This endpoint requires one of these roles: ' . implode(', ', $roles) . '.',
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
