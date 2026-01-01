<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\SerialHistoryResource\Pages;
use App\Models\SerialHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SerialHistoryResource extends Resource
{
    protected static ?string $model = SerialHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Rel. Movimentações';

    protected static ?string $modelLabel = 'Histórico de Serial';
    protected static ?string $pluralModelLabel = 'Histórico de Serials';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only view mainly
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) =>
                $query->whereHas('company', function ($q) {
                    $q->where('cnpj_representante', Auth::user()->cnpj);
                })
            )
            ->columns([
                Tables\Columns\TextColumn::make('data_geracao')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->width('1%')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('observacoes')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $obs = json_decode($state, true);
                        if (isset($obs['renovacao_revenda']) && $obs['renovacao_revenda']) {
                            return 'Renovação';
                        }
                        return 'Emissão';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Renovação' => 'success',
                        default => 'gray', // Emissão
                    })
                    ->width('1%'),

                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Cliente')
                    ->description(fn(SerialHistory $record) => $record->company?->cnpj ?? '-')
                    ->searchable(['razao', 'cnpj'])
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->description(fn(SerialHistory $record) => $record->software ? 'v' . $record->software->versao : '')
                    ->sortable()
                    ->wrap()
                    ->width('15%'),

                Tables\Columns\TextColumn::make('serial_gerado')
                    ->label('Serial')
                    ->copyable()
                    ->copyMessage('Serial copiado')
                    // ->listWithLineBreaks() // Not valid for TextColumn
                    ->limit(10)
                    ->fontFamily('mono')
                    ->tooltip(fn(Model $record) => $record->serial_gerado),

                Tables\Columns\TextColumn::make('validade_licenca')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->width('1%'),

                Tables\Columns\TextColumn::make('observacoes_valor')
                    ->label('Valor')
                    ->state(function (SerialHistory $record) {
                        $obs = json_decode($record->observacoes, true);
                        if (isset($obs['valor_pago'])) {
                            return 'R$ ' . number_format((float) $obs['valor_pago'], 2, ',', '.');
                        }
                        return '-';
                    })
                    ->color(fn(string $state): string => $state !== '-' ? 'success' : 'gray')
                    ->width('1%'),

            ])
            ->defaultSort('data_geracao', 'desc')
            ->filters([
                Tables\Filters\Filter::make('data_geracao')
                    ->form([
                        Forms\Components\DatePicker::make('data_inicio')->label('De'),
                        Forms\Components\DatePicker::make('data_fim')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicio'],
                                fn(Builder $query, $date) => $query->whereDate('data_geracao', '>=', $date),
                            )
                            ->when(
                                $data['data_fim'],
                                fn(Builder $query, $date) => $query->whereDate('data_geracao', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nome_software')
            ])
            ->actions([
                // No specific actions needed for history log
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSerialHistories::route('/'),
        ];
    }
}
