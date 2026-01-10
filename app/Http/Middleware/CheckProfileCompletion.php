<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckProfileCompletion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Rotas isentas de verificação para evitar loop infinito
            $exemptRoutes = [
                'filament.admin.pages.complete-profile', // A rota que criaremos
                'filament.admin.auth.logout',
                'livewire.message', // Necessário para funcionamento do Livewire/Filament
                'filament.admin.assets', // Assets
            ];

            // Se o cadastro estiver pendente e não estiver numa rota isenta
            if ($user->pending_profile_completion && !in_array($request->route()->getName(), $exemptRoutes)) {
                // Verifica se não é asset/livewire update
                if (!$request->is('livewire/*') && !$request->is('filament/*')) {
                    // return redirect()->route('filament.admin.pages.complete-profile');
                }

                // Redirecionamento Filament-way
                if (!str_contains($request->url(), '/complete-profile')) {
                    return redirect()->to('/admin/complete-profile');
                }
            }
        }

        return $next($request);
    }
}
