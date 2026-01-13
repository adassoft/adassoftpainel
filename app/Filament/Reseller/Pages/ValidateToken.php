<?php

namespace App\Filament\Reseller\Pages;

use Filament\Pages\Page;
use App\Traits\LegacyLicenseGenerator;
use Filament\Notifications\Notification;

class ValidateToken extends Page
{
    use LegacyLicenseGenerator;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Validar Token';
    protected static ?string $navigationGroup = 'Ferramentas';
    protected static ?string $title = 'Validador de Tokens';
    protected static ?int $navigationSort = 10;

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
            // Trim whitespace/newlines
            $cleanToken = trim($this->tokenToValidate);

            $dados = $this->validarToken($cleanToken);

            // Enrich Data
            $empresaId = $dados['empresa_codigo'] ?? null;
            $softwareId = $dados['software_id'] ?? null;

            if ($empresaId) {
                $dados['empresa_razao'] = \App\Models\Company::where('codigo', $empresaId)->value('razao') ?? "ID {$empresaId} (Não encontrada)";
            }

            if ($softwareId) {
                $soft = \App\Models\Software::find($softwareId);
                $dados['software_nome'] = $soft ? "{$soft->nome_software} (v{$soft->versao})" : "ID {$softwareId}";
            }

            // Mask Serial for Resellers
            if (!empty($dados['serial'])) {
                $parts = explode('-', $dados['serial']);
                if (count($parts) >= 4) {
                    // SER-PREFIX-PART1-PART2-PART3...
                    // Mascarar partes do meio
                    $dados['serial'] = $parts[0] . '-' . $parts[1] . '-****-****-' . end($parts);
                } else {
                    $dados['serial'] = substr($dados['serial'], 0, 8) . '-****';
                }
            }

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
