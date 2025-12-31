<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLicenses extends ListRecords
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LicenseResource\Widgets\LicenseOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Gerar Nova Licença')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('empresa_codigo')
                        ->label('Empresa')
                        ->relationship('company', 'razao')
                        ->searchable()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->razao} ({$record->cnpj})"),

                    \Filament\Forms\Components\Select::make('software_id')
                        ->label('Software')
                        ->relationship('software', 'nome_software')
                        ->required(),

                    \Filament\Forms\Components\Grid::make(2)
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('validade_dias')
                                ->label('Validade (Dias)')
                                ->numeric()
                                ->default(30)
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('n_terminais')
                                ->label('Nº Terminais')
                                ->numeric()
                                ->default(1)
                                ->required(),
                        ]),
                ])
                ->action(function (array $data, \App\Services\LicenseService $service) {
                    $company = \App\Models\Company::where('codigo', $data['empresa_codigo'])->firstOrFail();
                    $software = \App\Models\Software::findOrFail($data['software_id']);

                    $result = $service->createLicense($company, $software, $data['validade_dias'], $data['n_terminais']);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Licença Gerada!')
                        ->body("Serial: {$result['serial']}\nExpira em: " . date('d/m/Y', strtotime($result['expiry_date'])))
                        ->persistent()
                        ->send();
                }),

            Actions\Action::make('validate')
                ->label('Validar Serial')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('serial')
                        ->label('Serial')
                        ->required()
                        ->placeholder('SER-XXX-...'),

                    \Filament\Forms\Components\Textarea::make('token')
                        ->label('Token Assinado (Opcional)')
                        ->placeholder('Cole o token para validação de assinatura...'),
                ])
                ->action(function (array $data, \App\Services\LicenseService $service) {
                    $serial = trim($data['serial']);
                    $token = trim($data['token'] ?? '');

                    // Validation Logic
                    $license = \App\Models\License::where('serial_atual', $serial)->first();

                    if (!$license) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Serial Inválido')
                            ->body('Este serial não foi encontrado no sistema.')
                            ->send();
                        return;
                    }

                    $status = $license->status;
                    $isExpired = \Carbon\Carbon::parse($license->data_expiracao)->isPast();

                    if ($isExpired) {
                        $license->update(['status' => 'expirado']);
                        $status = 'expirado';
                    }

                    $type = match ($status) {
                        'ativo' => 'success',
                        'suspenso' => 'warning',
                        'expirado' => 'danger',
                        default => 'danger'
                    };

                    \Filament\Notifications\Notification::make()
                        ->status($type)
                        ->title('Resultado da Validação')
                        ->body("
                            **Status:** " . ucfirst($status) . "
                            **Cliente:** {$license->company->razao}
                            **Software:** {$license->software->nome_software}
                            **Expira em:** " . \Carbon\Carbon::parse($license->data_expiracao)->format('d/m/Y') . "
                            **Terminais:** {$license->terminais_utilizados} / {$license->terminais_permitidos}
                        ")
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
