<?php

namespace App\Filament\Reseller\Pages;

use App\Models\Plano;
use App\Models\PlanoRevenda;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Support\RawJs;

class ManagePlansPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Gerenciar Preços e Planos';
    protected static ?string $title = 'Gerenciar Preços e Planos';
    protected static string $view = 'filament.reseller.pages.manage-plans-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(Plano::query()->with(['software', 'minhaConfig'])->where('status', 1)) // Apenas planos ativos do sistema
            ->groups([
                Group::make('software.nome_software')
                    ->label('Software')
                    ->collapsible(false),
            ])
            ->defaultGroup('software.nome_software')
            ->columns([
                ToggleColumn::make('ativo_revenda')
                    ->label('Ativo')
                    ->state(fn(Plano $record) => $record->minhaConfig?->ativo ?? false)
                    ->updateStateUsing(function (Plano $record, $state) {
                        $this->saveConfig($record, ['ativo' => $state]);
                        return $state;
                    })
                    ->onColor('success')
                    ->offColor('danger'),

                TextColumn::make('nome_plano')
                    ->label('Plano')
                    ->description(fn(Plano $record) => 'Recorrência: ' . $record->recorrencia . ' meses')
                    ->sortable(),

                TextColumn::make('valor')
                    ->label('Custo Admin (Ref.)')
                    ->money('BRL')
                    ->color('gray'),

                TextInputColumn::make('valor_venda')
                    ->label('Seu Preço de Venda (R$)')
                    ->state(fn(Plano $record) => $record->minhaConfig?->valor_venda ?? $record->valor) // Default: Valor Base
                    ->updateStateUsing(function (Plano $record, $state) {
                        // Limpa máscara se vier (TextInputColumn geralmente manda raw se type number, mas vamos garantir)
                        $val = str_replace(',', '.', str_replace('.', '', $state)); // Se vier formatado PT-BR
                        // Se state vier direto do input type number html5, já vem limpo. Filament TextInputColumn é text por padrão.
                        // Vamos usar Mask do Filament se possível, mas TextInputColumn é limitado.
                        // Melhor assumir que o usuário digite '10.00' ou '10,00'
            
                        // Tratamento simples: troca virgula por ponto
                        $val = (float) str_replace(',', '.', $state);

                        $this->saveConfig($record, ['valor_venda' => $val]);
                        return $state;
                    })
                    ->type('text')
                    ->extraAttributes(['class' => 'w-32']),
            ])
            ->striped();
    }

    protected function saveConfig(Plano $plano, array $dados)
    {
        $cnpj = Auth::user()->cnpj;
        if (!$cnpj)
            return;

        // Busca ou cria o registro de configuração
        // Usando updateOrInsert ou lógica manual para garantir persistência correta
        // Eloquent updateOrCreate precisa das chaves.

        PlanoRevenda::updateOrCreate(
            [
                'cnpj_revenda' => $cnpj,
                'plano_id' => $plano->id
            ],
            $dados // Apenas o campo alterado será atualizado aqui, cuidado.
            // O updateOrCreate fará merge dos $dados com as keys. Mas se eu mandar só 'ativo', o 'valor_venda' permanece? Sim.
        );

        // Refresh na relação para UI (embora o Filament cuide bem do state local)
        $plano->refresh();
    }
}
