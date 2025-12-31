<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.app.pages.auth.login';

    protected function getViewData(): array
    {
        // Configuração Padrão (Adassoft)
        $gradientStart = '#1a2980';
        $gradientEnd = '#26d0ce';
        $appName = config('app.name', 'Adassoft');
        $slogan = 'Segurança e Gestão de Licenças';
        $logoUrl = asset('favicon.svg');

        // Tenta carregar Branding da Revenda via Domínio
        $branding = \App\Services\ResellerBranding::getCurrent();

        if ($branding) {
            $gradientStart = $branding['gradient_start'] ?? $gradientStart;
            $gradientEnd = $branding['gradient_end'] ?? $gradientEnd;
            $appName = $branding['nome_sistema'] ?? $appName;
            $slogan = $branding['slogan'] ?? $slogan;
            $logoUrl = $branding['logo_url'] ?? $logoUrl;
        }

        return [
            'gradientStart' => $gradientStart,
            'gradientEnd' => $gradientEnd,
            'appName' => $appName,
            'slogan' => $slogan,
            'logoUrl' => $logoUrl,
        ];
    }
}
