<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToNonWww
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with(strtolower($host), 'www.')) {
            $hostWithoutWww = substr($host, 4);
            $scheme = $request->isSecure() ? 'https' : 'http';

            // Força HTTPS se estiver em produção (recomendado)
            if (app()->environment('production')) {
                $scheme = 'https';
            }

            return redirect()->to(
                $scheme . '://' . $hostWithoutWww . $request->getRequestUri(),
                301
            );
        }

        return $next($request);
    }
}
