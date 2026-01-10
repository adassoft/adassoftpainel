<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Monitor404Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $path = '/' . trim($request->path(), '/');
            // Trunca para evitar erro de banco (limite do campo geralmente 255)
            if (strlen($path) > 190) {
                $path = substr($path, 0, 190);
            }

            // Ignora assets, livewire, filament debug, etc
            if ($request->is('livewire/*', 'filament/*', 'admin/*', 'storage/*', 'css/*', 'js/*', 'img/*')) {
                return $response;
            }

            // Ignora se estiver marcado como ignorado no banco
            $ignored = \App\Models\RedirectLog::where('path', $path)->where('is_ignored', true)->exists();
            if ($ignored) {
                return $response;
            }

            // Loga ou Incrementa
            $log = \App\Models\RedirectLog::firstOrNew(['path' => $path]);
            $log->hits = ($log->hits ?? 0) + 1;
            $log->last_accessed_at = now();
            $log->ip = $request->ip();
            $log->user_agent = $request->userAgent();
            $log->is_resolved = false; // Se reapareceu, não está resolvido
            $log->save();
        }

        return $response;
    }
}
