<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Level 1 = Admin, or hardcoded super admin email
        if ($user->acesso == 1 || $user->email === 'admin@adassoft.com') {
            return $next($request);
        }

        abort(403, 'Acesso restrito a administradores.');
    }
}
