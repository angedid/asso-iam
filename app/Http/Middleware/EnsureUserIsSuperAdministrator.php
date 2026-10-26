<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdministrator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Log::info("Ensure user is super administrator");
        if (Auth::guard('super_admin')->check() && (!Auth::guard('super_admin')->user()->active || !Auth::guard('super_admin')->user()->is_super_admin)) {
            Auth::guard('super_admin')->logout();
            return redirect('/super-admin-required')->with('error', __("Accès refusé"));
        }
        //dd(Auth::guard('super_admin')->user());
        return $next($request);
    }
}
