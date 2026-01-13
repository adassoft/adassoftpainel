<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Traits\LegacyLicenseGenerator;
use Filament\Notifications\Notification;

class ValidateToken extends Page
{
    use LegacyLicenseGenerator;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Validar Token';
    protected static ?string $navigationGroup = 'Gestão de Clientes'; // Or Ferramentas if exists
    protected static ?string $title = 'Validador de Tokens (Admin)';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.validate-token';

    public $tokenToValidate = '';
    public $result = null;

    public function checkToken()
    {
        $this->result = null;

        if (empty($this->tokenToValidate)) {
            Notification::make()->title('Informe o token.')->warning()->send();
            return;
        }

        try {
            $cleanToken = trim($this->tokenToValidate);
            $dados = $this->validarToken($cleanToken);
            $this->result = $dados;

            Notification::make()
                ->title('Token Válido!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Token Inválido')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
