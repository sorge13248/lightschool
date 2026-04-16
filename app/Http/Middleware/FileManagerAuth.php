<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mixed-auth middleware for the file-manager controller.
 *
 * - `provide-file` is permitted unauthenticated (the controller validates
 *   ownership, sharing, or a short-lived bypass token internally).
 * - Every other type requires an authenticated session; unauthenticated
 *   requests receive a 401 JSON response.
 */
class FileManagerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query('type') !== 'provide-file' && !auth()->check()) {
            return response()->json(['response' => 'error', 'text' => 'Authentication required.'], 401);
        }

        return $next($request);
    }
}
