<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\License;
use App\Models\Company;
use Illuminate\Support\Str;

class ExpiringLicensesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Licenças Expirando em Breve';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                License::query()
                    ->where('data_expiracao', '>=', now())
                    ->orderBy('data_expiracao', 'asc')
            )
            ->filters([
                Tables\Filters\SelectFilter::make('periodo')
                    ->label('Período')
                    ->options([
                        '7' => 'Próximos 7 dias',
                        '15' => 'Próximos 15 dias',
                        '30' => 'Próximos 30 dias',
                        '60' => 'Próximos 60 dias',
                        '90' => 'Próximos 3 meses',
                    ])
                    ->default('7')
                    ->query(function ($query, array $data) {
                        return $query->where('data_expiracao', '<=', now()->addDays((int) ($data['value'] ?? 7)));
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('empresa_codigo')
                    ->label('Cliente')
                    ->weight('bold')
                    ->formatStateUsing(function ($state) {
                        $razao = Company::where('codigo', $state)->value('razao');
                        return $razao ? Str::limit($razao, 30) : "CD: $state";
                    }),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->badge(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->color('danger')
                    ->description(fn($record) => $record->data_expiracao->diffForHumans()),

                Tables\Columns\TextColumn::make('terminais_utilizados')
                    ->label('Terminais')
                    ->formatStateUsing(fn($record) => $record->terminais_utilizados . '/' . $record->terminais_permitidos),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('renovar')
                    ->label('Gerenciar')
                    ->tooltip('Ver detalhes da licença')
                    ->url(fn($record) => "/admin/manage-serials") // Ajustar se tiver rota direta para edição
                    ->icon('heroicon-m-cog-6-tooth')
                    ->color('gray'),

                Tables\Actions\Action::make('notificar')
                    ->label('Cobrar')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar Alerta de Vencimento')
                    ->modalDescription(fn($record) => "Deseja enviar um e-mail/notificação automática para o cliente (Código: {$record->empresa_codigo}) alertando sobre o vencimento em " . $record->data_expiracao->format('d/m/Y') . "?")
                    ->modalSubmitActionLabel('Enviar Alerta')
                    ->action(function ($record) {
                        // Lógica de envio de e-mail ou WhatsApp aqui
                        // Exemplo: Mail::to($record->company->email)->send(new LicenseExpiring($record));
            
                        \Filament\Notifications\Notification::make()
                            ->title('Alerta enviado com sucesso')
                            ->body("O cliente foi notificado sobre o vencimento em {$record->data_expiracao->format('d/m/Y')}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
