<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('login');
        }
        
        if (!in_array(Auth::user()->rol, $roles)) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
        
        return $next($request);
    }
}