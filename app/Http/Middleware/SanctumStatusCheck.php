<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SanctumStatusCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only run this check if the user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if the user has been disabled (status=0) or soft-deleted
            if ((isset($user->status) && (int)$user->status === 0) || !is_null($user->deleted_at)) {
                // Return a 403 Forbidden response
                return response()->json([
                    'error' => true,
                    'message' => 'Your account has been disabled. Please contact support.',
                    'code' => 403
                ], Response::HTTP_FORBIDDEN);
            }
        }
        
        return $next($request);
    }
} 