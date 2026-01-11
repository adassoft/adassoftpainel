<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\CreditHistory;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\DB;

class ManageCredits extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Revenda';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Gerência de Créditos';

    protected static ?string $title = 'Gerência de Créditos (Revenda)';

    protected static string $view = 'filament.pages.manage-credits';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()
                    ->where('status', 'Ativo')
                    ->where(fn($q) => $q->whereHas('users', fn($u) => $u->where('acesso', 2))
                        ->orWhere('revenda_padrao', true))
            )
            ->columns([
                Tables\Columns\TextColumn::make('razao')
                    ->label('Revenda')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo Atual')
                    ->money('BRL')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\Action::make('lancamento')
                    ->label('Lançar Movimento')
                    ->icon('heroicon-m-plus-circle')
                    ->color('primary')
                    ->size(\Filament\Support\Enums\ActionSize::Medium)
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->slideOver()
                    ->modalWidth('md')
                    ->form([
                        Select::make('tipo')
                            ->label('Tipo de Movimento')
                            ->options([
                                'entrada' => 'Entrada (Adicionar Crédito)',
                                'saida' => 'Saída (Remover/Estornar)',
                            ])
                            ->required()
                            ->default('entrada'),

                        TextInput::make('valor')
                            ->label('Valor (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        TextInput::make('descricao')
                            ->label('Descrição/Motivo')
                            ->placeholder('Ex: Pagamento PIX Comprovante #123')
                            ->required(),
                    ])
                    ->action(function (Company $record, array $data): void {
                        try {
                            DB::beginTransaction();

                            $valor = (float) $data['valor'];
                            $novoSaldo = $data['tipo'] === 'entrada'
                                ? $record->saldo + $valor
                                : $record->saldo - $valor;

                            $record->update(['saldo' => $novoSaldo]);

                            CreditHistory::create([
                                'empresa_cnpj' => $record->cnpj,
                                'usuario_id' => auth()->id(),
                                'tipo' => $data['tipo'],
                                'valor' => $valor,
                                'descricao' => $data['descricao'],
                                'data_movimento' => now(),
                            ]);

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Lançamento Realizado')
                                ->body("Movimento de R$ " . number_format($valor, 2, ',', '.') . " registrado para {$record->razao}.")
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->danger()
                                ->title('Erro')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('novo_lancamento')
                ->label('Novo Lançamento Manual')
                ->icon('heroicon-m-pencil-square')
                ->slideOver()
                ->modalWidth('md')
                ->form([
                    Select::make('cnpj_revenda')
                        ->label('Revenda')
                        ->getSearchResultsUsing(fn(string $search) => Company::where('status', 'Ativo')
                            ->where(fn($q) => $q->whereHas('users', fn($u) => $u->where('acesso', 2))
                                ->orWhere('revenda_padrao', true))
                            ->where(fn($q) => $q->where('razao', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%"))
                            ->limit(50)
                            ->pluck('razao', 'cnpj'))
                        ->getOptionLabelUsing(fn($value) => Company::where('cnpj', $value)->first()?->razao)
                        ->searchable()
                        ->required(),

                    Select::make('tipo')
                        ->label('Tipo de Movimento')
                        ->options([
                            'entrada' => 'Entrada (Adicionar Crédito)',
                            'saida' => 'Saída (Remover/Estornar)',
                        ])
                        ->required()
                        ->default('entrada'),

                    TextInput::make('valor')
                        ->label('Valor (R$)')
                        ->numeric()
                        ->prefix('R$')
                        ->required(),

                    TextInput::make('descricao')
                        ->label('Descrição/Motivo')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $record = Company::where('cnpj', $data['cnpj_revenda'])->first();
                    if (!$record)
                        return;

                    try {
                        DB::beginTransaction();

                        $valor = (float) $data['valor'];
                        $novoSaldo = $data['tipo'] === 'entrada'
                            ? $record->saldo + $valor
                            : $record->saldo - $valor;

                        $record->update(['saldo' => $novoSaldo]);

                        CreditHistory::create([
                            'empresa_cnpj' => $record->cnpj,
                            'usuario_id' => auth()->id(),
                            'tipo' => $data['tipo'],
                            'valor' => $valor,
                            'descricao' => $data['descricao'],
                            'data_movimento' => now(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Lançamento Realizado')
                            ->body("Movimento de R$ " . number_format($valor, 2, ',', '.') . " registrado para {$record->razao}.")
                            ->send();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Erro')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
        ];
    }
}
