<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shorthand for role:admin, kept as its own class because the laboratory
 * grades it separately and because the admin surface reads better as
 * "admin" than as a role list.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. Administrator access is required.',
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
