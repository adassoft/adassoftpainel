<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class EnsureCompanyExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is logged in
        if (!$user) {
            return $next($request);
        }

        // Only enforce for 'app' panel (Customer)
        // Adjust logic if you want to enforce for Resellers too, but usually it's App
        if (Filament::getCurrentPanel()->getId() !== 'app') {
            return $next($request);
        }

        // List of routes to exclude from check (to avoid loop)
        $excludedRoutes = [
            'filament.app.pages.minha-empresa',
            'filament.app.auth.logout',
            'filament.app.auth.login', // Just in case
        ];

        if (in_array(\Illuminate\Support\Facades\Route::currentRouteName(), $excludedRoutes)) {
            return $next($request);
        }

        // Check Logic: Missing Link OR Missing/Invalid CNPJ
        $needsUpdate = false;

        if (empty($user->empresa_id)) {
            \Illuminate\Support\Facades\Log::info('Middleware Redirect: empresa_id is empty for user ' . $user->id);
            $needsUpdate = true;
        } else {
            // Check if linked company has valid data
            $empresa = $user->empresa;
            if (!$empresa) {
                \Illuminate\Support\Facades\Log::info('Middleware Redirect: Relationship failed. empresa_id=' . $user->empresa_id . ' but model is null.');
                $needsUpdate = true; // Link exists (ID) but record deleted?
            } else {
                $cnpj = preg_replace('/\D/', '', $empresa->cnpj ?? '');
                $len = strlen($cnpj);
                if (!in_array($len, [11, 14])) {
                    \Illuminate\Support\Facades\Log::info('Middleware Redirect: Invalid CNPJ length (' . $len . ') for company ' . $empresa->codigo);
                    $needsUpdate = true;
                }
            }
        }

        if ($needsUpdate) {
            // Store return_to if they were trying to go somewhere specific (like Checkout)
            // But middleware runs on every request, so checking referring is tricky.
            // If we rely on session 'return_to_checkout' set by Checkout, it's fine.
            // If they just logged in and hit Dashboard, they get redirected.

            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Atualização Necessária')
                ->body('Por favor, complete os dados da sua empresa para continuar.')
                ->send();

            return redirect()->route('filament.app.pages.minha-empresa');
        }

        return $next($request);
    }
}
