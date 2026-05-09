<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    // Tiempo máximo de inactividad en segundos (30 minutos)
    protected int $timeout = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('_last_activity');

            if ($lastActivity !== null && (time() - $lastActivity) > $this->timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Tu sesión expiró por inactividad (30 min). Inicia sesión nuevamente.',
                ]);
            }

            session(['_last_activity' => time()]);
        }

        return $next($request);
    }
}
