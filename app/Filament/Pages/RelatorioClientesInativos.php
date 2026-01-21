<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class RelatorioClientesInativos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';
    protected static ?string $navigationLabel = 'Clientes Inativos (> 1 Ano)';
    protected static ?string $title = 'Clientes Inativos (> 1 Ano)';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?string $slug = 'relatorios/clientes-inativos';

    protected static string $view = 'filament.pages.relatorio-clientes-inativos';

    public static function canAccess(): bool
    {
        // Apenas Admin (acesso 1)
        return auth()->check() && (auth()->user()->acesso == 1 || auth()->user()->email === 'admin@adassoft.com');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()
                    ->whereHas('licenses') // Apenas empresas que já tiveram licença
                    // A lógica: NÃO deve ter nenhuma licença cuja data de expiração seja "Maior ou Igual a Hoje - 1 Ano".
                    // Ou seja, se tiver expiração mês passado, não entra. Se tiver expiração futura, não entra.
                    // Só entra se TODAS as expirações forem ANTERIORES a 1 ano atrás.
                    ->whereDoesntHave('licenses', function (Builder $query) {
                        $query->where('data_expiracao', '>=', now()->subYear());
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->sortable()->label('Cód.'),
                Tables\Columns\TextColumn::make('razao')
                    ->label('Razão Social')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Empresa')
                    ->icon('heroicon-m-envelope')
                    ->copyable(),
                Tables\Columns\TextColumn::make('usuarios.email')
                    ->label('Emails Usuários')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->searchable(),
                Tables\Columns\TextColumn::make('primeira_ativacao')
                    ->label('Início (1ª Licença)')
                    ->state(fn(Company $record) => $record->licenses()->min('data_criacao'))
                    ->date('d/m/Y')
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderByRaw("(SELECT MIN(data_criacao) FROM licencas_ativas WHERE licencas_ativas.empresa_codigo = empresa.codigo) $direction");
                    }),
                Tables\Columns\TextColumn::make('ultima_validade')
                    ->label('Última Expiração')
                    ->state(fn(Company $record) => $record->licenses()->max('data_expiracao'))
                    ->date('d/m/Y')
                    ->sortable(query: function (Builder $query, string $direction) {
                        // Sort pela subquery da maior data
                        return $query->orderByRaw("(SELECT MAX(data_expiracao) FROM licencas_ativas WHERE licencas_ativas.empresa_codigo = empresa.codigo) $direction");
                    }),
                Tables\Columns\TextColumn::make('classificacao')
                    ->label('Perfil Histórico')
                    ->badge()
                    ->state(function (Company $record) {
                        $teveRenovacaoOuLonga = $record->licenses()
                            ->where(function ($q) {
                                $q->whereNotNull('data_ultima_renovacao')
                                    ->orWhereRaw('DATEDIFF(data_expiracao, data_criacao) > 45');
                            })->exists();

                        return $teveRenovacaoOuLonga ? 'Ex-Cliente' : 'Avaliação';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Ex-Cliente' => 'warning',
                        'Avaliação' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('perfil')
                    ->label('Filtrar por Perfil')
                    ->options([
                        'avaliacao' => 'Apenas Testes/Avaliação',
                        'ex_cliente' => 'Ex-Clientes (Já pagaram)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'avaliacao') {
                            $query->whereDoesntHave('licenses', function ($q) {
                                $q->whereNotNull('data_ultima_renovacao')
                                    ->orWhereRaw('DATEDIFF(data_expiracao, data_criacao) > 45');
                            });
                        } elseif ($data['value'] === 'ex_cliente') {
                            $query->whereHas('licenses', function ($q) {
                                $q->whereNotNull('data_ultima_renovacao')
                                    ->orWhereRaw('DATEDIFF(data_expiracao, data_criacao) > 45');
                            });
                        }
                    }),
            ])
            ->actions([
                // Link para abrir o cadastro da empresa se necessário
            ])
            ->bulkActions([
                // Permite exportar se tiver o pacote de export instalado, senão padrão
            ])
            ->defaultSort('ultima_validade', 'desc'); // Ordenar pelos mais "recentes" dentre os antigos
    }
}
