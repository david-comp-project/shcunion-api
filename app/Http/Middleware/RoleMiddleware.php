<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles): Response
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil semua role user
        $userRoles = $user->getRoleNames()->toArray();

    
        // Pisahkan string role menjadi array
        $rolesArray = explode('|', $roles);
        // Log::info('Roles :', [$roles]);
        // Log::info('Roles yang dibutuhkan:', [$rolesArray]);
        // Log::info('Roles yang dimiliki user:', [$userRoles]);

        // Cek apakah user memiliki salah satu role yang dibutuhkan
        foreach ($rolesArray as $role) {
            if (in_array(trim($role), $userRoles)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
