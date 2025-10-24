<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API routes, return null to prevent redirect
        if ($request->is('api/*')) {
            return null;
        }
        
        return route('login');
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            if ($this->authenticate($request, $guards)) {
                return $next($request);
            }
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            // For API routes, return JSON response
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Authentication required'
                ], 401);
            }
            
            throw $e;
        }

        // For API routes, return JSON response
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }

        return $this->redirectTo($request);
    }
}
