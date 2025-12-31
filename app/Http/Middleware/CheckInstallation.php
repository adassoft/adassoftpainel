<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class CheckInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se a rota for o instalador
        if ($request->is('install*')) {
            // Se já existe arquivo 'installed', bloqueia acesso e manda pro admin
            if (file_exists(storage_path('installed'))) {
                return redirect('/admin/login');
            }
            return $next($request);
        }

        // Se for qualquer outra rota e NÃO existe arquivo 'installed'
        if (!file_exists(storage_path('installed'))) {
            // Ignora assets e debugbar
            if ($request->is('livewire*') || $request->is('filament*')) {
                return $next($request);
            }
            return redirect('/install');
        }

        return $next($request);
    }
}
