<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
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
        // Check if the user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user status is inactive (0) or if user is soft-deleted
            if ((isset($user->status) && $user->status == 0) || !is_null($user->deleted_at)) {
                // Log the user out
                Auth::guard(Auth::getDefaultDriver())->logout();
                
                // For session-based auth (web), clear the session
                if (!$request->expectsJson() && !$request->is('api/*')) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                
                // For API requests, return a JSON response with 403 Forbidden
                if ($request->expectsJson() || $request->is('api/*') || $request->bearerToken()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Your account has been disabled. Please contact support.',
                        'code' => 403
                    ], Response::HTTP_FORBIDDEN); // 403 Forbidden
                }
                
                // For web requests, abort with 403 Forbidden
                abort(Response::HTTP_FORBIDDEN, 'Your account has been disabled. Please contact support.');
            }
        }

        return $next($request);
    }
} 