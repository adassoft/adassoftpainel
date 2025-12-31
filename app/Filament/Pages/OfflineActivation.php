<?php

namespace App\Filament\Pages;

use App\Services\LicenseService;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Exception;

class OfflineActivation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $title = 'Ativação Offline';
    protected static ?string $navigationGroup = 'Licenciamento';
    protected static string $view = 'filament.pages.offline-activation';

    public ?array $data = [];
    public ?string $generatedToken = null;
    public ?array $tokenPayload = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Textarea::make('challenge')
                            ->label('Challenge Offline')
                            ->placeholder('Cole aqui o texto do challenge recebido do cliente...')
                            ->rows(5)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(LicenseService $service)
    {
        $data = $this->form->getState();
        $challengeInput = trim($data['challenge']);

        try {
            $payloadChallenge = $this->decodeChallenge($challengeInput);

            // Validation logic (simplified for now, mimicking legacy)
            $serial = $payloadChallenge['serial'] ?? '';
            $license = \App\Models\License::where('serial_atual', $serial)->first();

            if (!$license) {
                throw new Exception('Serial não encontrado no sistema.');
            }

            if ($license->data_expiracao->isPast()) {
                throw new Exception('A licença associada a este serial expirou.');
            }

            $this->tokenPayload = [
                'serial' => $license->serial_atual,
                'empresa_codigo' => $license->empresa_codigo,
                'software_id' => $license->software_id,
                'software' => $license->software->nome_software,
                'versao_software' => $payloadChallenge['versao_software'] ?? $license->software->versao,
                'instalacao_id' => $payloadChallenge['instalacao_id'] ?? 'unknown',
                'modo' => 'offline',
                'emitido_em' => now()->toIso8601String(),
                'expira_em' => now()->addDays(3)->toIso8601String(),
                'offline_challenge' => $challengeInput
            ];

            $this->generatedToken = $service->generateToken($this->tokenPayload);

            Notification::make()
                ->success()
                ->title('Token Gerado!')
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erro na Ativação')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function decodeChallenge(string $challenge): array
    {
        $partes = explode('.', $challenge);
        if (count($partes) !== 2) {
            throw new Exception('Formato de challenge inválido.');
        }

        [$encoded, $assinatura] = $partes;
        $secret = config('shield.offline_secret', 'defina-um-segredo-offline');

        $esperado = hash_hmac('sha256', $encoded, $secret);
        if (!hash_equals($esperado, $assinatura)) {
            throw new Exception('Assinatura do challenge inválida.');
        }

        $json = $this->base64url_decode($encoded);
        return json_decode($json, true) ?: throw new Exception('Falha ao decodificar payload.');
    }

    protected function base64url_decode(string $data): ?string
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
