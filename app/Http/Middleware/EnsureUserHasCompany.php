<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Só aplica se estiver autenticado
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        // Admin bypass: Admin não precisa ter empresa para acessar o painel de revenda
        if ($user->acesso === 1) {
            return $next($request);
        }

        // Só aplica no painel de revenda
        if ($panel && $panel->getId() === 'reseller') {

            // Verifica se está tentando acessar a própria página de configuração (loop infinito)
            // A rota do Filament geralmente é 'filament.reseller.pages.company-profile'
            // Ou checamos o path da URL
            if ($request->routeIs('filament.reseller.pages.company-profile')) {
                return $next($request);
            }

            // Verifica se é logout (para não prender o usuário pra sempre)
            if ($request->routeIs('filament.reseller.auth.logout')) {
                return $next($request);
            }

            // A lógica principal: Checa se tem empresa
            // Como a relação no User é hasOne(Empresa::class, 'cnpj', 'cnpj')
            // Se o usuário não tiver CNPJ ou a empresa não existir, trava.
            if (empty($user->cnpj) || !$user->empresa) {

                // Redireciona para a página de criação
                // Usamos o nome da rota do Filament para ser seguro
                return redirect()->route('filament.reseller.pages.company-profile');
            }
        }

        return $next($request);
    }
}
