<?php

namespace App\Filament\Reseller\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Order;
use App\Models\CreditHistory;
use App\Traits\LegacyLicenseGenerator;
use Filament\Actions\Action;

class GenerateLicense extends Page implements HasForms
{
    use InteractsWithForms;
    use LegacyLicenseGenerator;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?string $navigationLabel = 'Gerar Licença';
    protected static ?string $title = 'Gerar Licença Manual';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.reseller.pages.generate-license';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Emissão Direta (Revenda)')
                    ->description('Selecione o plano desejado. O valor total será descontado do seu saldo.')
                    ->schema([
                        Forms\Components\Select::make('company_id') // We need Company Code, but select returns ID/Key. Company PK is 'codigo'.
                            ->label('Cliente')
                            ->options(function () {
                                return Company::where('cnpj_representante', Auth::user()->cnpj)
                                    ->orderBy('razao')
                                    ->pluck('razao', 'codigo'); // Value is 'codigo'
                            })
                            ->searchable()
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plano / Software')
                            ->options(function () {
                                return Plan::join('softwares', 'planos.software_id', '=', 'softwares.id')
                                    ->where('planos.status', 1)
                                    ->where('softwares.status', 1)
                                    ->select(
                                        'planos.id',
                                        'planos.nome_plano',
                                        'planos.valor',
                                        'softwares.nome_software',
                                        'planos.recorrencia'
                                    )
                                    ->orderBy('softwares.nome_software')
                                    ->orderBy('planos.valor')
                                    ->get()
                                    ->mapWithKeys(function ($plan) {
                                        $meses = ($plan->recorrencia == 1) ? 'Mensal' : ($plan->recorrencia . ' Meses');
                                        $valor = number_format($plan->valor, 2, ',', '.');
                                        return [$plan->id => "{$plan->nome_software} - {$plan->nome_plano} ({$meses}) - R$ {$valor}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->helperText('O preço e a duração são definidos pelo plano escolhido.'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();
        $revendaCnpj = $user->cnpj;

        try {
            DB::beginTransaction();

            // 1. Validar e Buscar Dados
            $plan = Plan::with('software')->findOrFail($data['plan_id']);
            $client = Company::findOrFail($data['company_id']); // company_id here is 'codigo'
            $resellerCompany = Company::where('cnpj', $revendaCnpj)->lockForUpdate()->firstOrFail();

            $valor = (float) $plan->valor;
            $meses = (int) $plan->recorrencia;

            // 2. Verificar Saldo
            if ($resellerCompany->saldo < $valor) {
                throw new \Exception("Saldo insuficiente. Seu saldo: R$ " . number_format($resellerCompany->saldo, 2, ',', '.') . ". Valor do plano: R$ " . number_format($valor, 2, ',', '.'));
            }

            // 3. Criar Pedido
            $codTransacao = 'MAN-' . strtoupper(uniqid());
            $pedido = Order::create([
                'cnpj' => $client->cnpj,
                'software_id' => $plan->software_id,
                'plano_id' => $plan->id,
                'valor' => $valor,
                'data' => now(),
                'situacao' => 'pago', // Já nasce pago pois é saldo
                'condicao' => 'A Vista',
                'forma_pagamento' => 'Saldo Revenda',
                'recorrencia' => $meses,
                'cod_transacao' => $codTransacao,
                'status_entrega' => 'pendente',
                'cnpj_revenda' => $revendaCnpj,
            ]);

            // 4. Debitar Saldo
            $resellerCompany->decrement('saldo', $valor);

            CreditHistory::create([
                'empresa_cnpj' => $revendaCnpj,
                'usuario_id' => $user->id,
                'tipo' => 'saida',
                'valor' => $valor,
                'descricao' => "Licença Pedido #{$pedido->id} - {$plan->software->nome_software} ({$client->razao})",
                'data_movimento' => now()
            ]);

            // 5. Gerar Licença (Via Trait)
            // Need company 'codigo' which IS data['company_id'] and software ID
            $resultado = $this->gerarLicencaCompleta($client->codigo, $plan->software_id, ($meses * 30), 1);

            // 6. Atualizar Pedido
            $pedido->update([
                'status_entrega' => 'entregue',
                'serial_gerado' => $resultado['serial']
            ]);

            DB::commit();

            Notification::make()
                ->title('Licença emitida com sucesso!')
                ->body("Serial: {$resultado['serial']}")
                ->success()
                ->persistent()
                ->send();

            $this->form->fill(); // Limpa o formulário

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Erro ao emitir licença')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Emitir Agora')
                ->color('success')
                ->icon('heroicon-m-check')
                ->submit('create'),
        ];
    }
}
