<?php

namespace App\Filament\Reseller\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\License;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class ResellerExpiringLicensesWidget extends BaseWidget
{
    protected static ?string $heading = 'Renovações Pendentes (Meus Clientes)';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                License::query()
                    ->where('cnpj_revenda', Auth::user()?->cnpj)
                    ->where('data_expiracao', '>=', now())
                    ->orderBy('data_expiracao', 'asc')
            )
            ->filters([
                Tables\Filters\SelectFilter::make('periodo')
                    ->label('Prazo')
                    ->options([
                        '7' => 'Próximos 7 dias',
                        '15' => 'Próximos 15 dias',
                        '30' => 'Próximos 30 dias',
                    ])
                    ->default('15')
                    ->query(fn($query, array $data) => $query->where('data_expiracao', '<=', now()->addDays((int) ($data['value'] ?? 15))))
            ])
            ->columns([
                Tables\Columns\TextColumn::make('empresa_codigo')
                    ->label('Cliente')
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(function ($state) {
                        $c = Company::where('codigo', $state)->first();
                        return $c ? $c->razao : "ID: $state";
                    })
                    ->description(fn($record) => Company::where('codigo', $record->empresa_codigo)->value('cnpj')),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->badge(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->color('danger')
                    ->weight('bold')
                    ->description(fn($record) => $record->data_expiracao->diffForHumans()),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('Cobrar no WhatsApp')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(function ($record) {
                        $company = Company::where('codigo', $record->empresa_codigo)->first();
                        $fone = $company ? preg_replace('/[^0-9]/', '', $company->fone) : '';

                        // Fallback ou validação básica de número BR
                        if (strlen($fone) < 10)
                            return null;

                        $softwareName = $record->software->nome_software ?? 'sistema';
                        $date = $record->data_expiracao->format('d/m/Y');

                        $text = "Olá *{$company->razao}*, tudo bem?\nPassando para lembrar que sua licença do *{$softwareName}* vence dia *{$date}*.\nPodemos proceder com a renovação?";

                        return "https://wa.me/55{$fone}?text=" . urlencode($text);
                    }, true) // Nova aba
            ]);
    }
}
