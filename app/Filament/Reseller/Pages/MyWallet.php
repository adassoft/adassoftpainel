<?php

namespace App\Filament\Reseller\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\CreditHistory;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class MyWallet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Minha Carteira';
    protected static ?string $title = 'Minha Carteira';

    protected static string $view = 'filament.reseller.pages.my-wallet';

    public float $saldo = 0.00;
    public float $minRecarga = 5.00;

    // Novas propriedades para lógica de PIX
    public ?string $valorRecargaInput = null;
    public ?string $pixQrCode = null;
    public ?string $pixCopyPaste = null;
    public bool $showPixModal = false;
    public bool $paymentConfirmed = false;

    public function mount(): void
    {
        $user = Auth::user();
        $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
        $cnpjFormatado = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpjLimpo);

        // Tenta buscar saldo pelo CNPJ limpo, original ou formatado
        $this->saldo = (float) (Company::where(function ($q) use ($user, $cnpjLimpo, $cnpjFormatado) {
            $q->where('cnpj', $cnpjLimpo)
                ->orWhere('cnpj', $user->cnpj)
                ->orWhere('cnpj', $cnpjFormatado);
        })->sum('saldo') ?? 0.00); // Usa sum() para somar caso existam duplicidades (com e sem ponto)

        try {
            $min = DB::table('gateways')
                ->where('gateway_name', 'Asaas')
                ->value('min_recharge');

            if ($min) {
                $this->minRecarga = (float) $min;
            }
        } catch (\Exception $e) {
            // Silencioso se tabela não existir ou erro de DB
            $this->minRecarga = 5.00;
        }
    }

    public function generatePix(): void
    {
        $this->paymentConfirmed = false;
        $valorStr = str_replace(['.', ','], ['', '.'], $this->valorRecargaInput);
        $valor = (float) $valorStr;

        if ($valor < $this->minRecarga) {
            Notification::make()
                ->title('Valor inválido')
                ->body('O valor mínimo para recarga é R$ ' . number_format($this->minRecarga, 2, ',', '.'))
                ->danger()
                ->send();
            return;
        }

        try {
            // 1. Configuração do Gateway
            $gateway = DB::table('gateways')
                ->where('gateway_name', 'Asaas')
                ->where('active', 1)
                ->first();

            if (!$gateway) {
                throw new \Exception('Gateway de pagamento não configurado.');
            }

            $apiKey = $gateway->access_token;
            // $walletId = $gateway->wallet_id; // V3 usa access_token para definir conta
            $isProd = $gateway->producao === 's';
            $baseUrl = $isProd ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';

            $user = Auth::user();
            $empresa = Company::where('cnpj', $user->cnpj)->firstOrFail();
            $cnpjLimpo = preg_replace('/\D/', '', $empresa->cnpj);

            // 2. Buscar/Criar Cliente
            $headers = [
                'access_token' => $apiKey,
                'User-Agent' => 'Adassoft-Panel/1.0',
                'Content-Type' => 'application/json'
            ];

            // Busca cliente
            $response = Http::withHeaders($headers)
                ->get("$baseUrl/customers", ['cpfCnpj' => $cnpjLimpo]);

            if ($response->failed())
                throw new \Exception('Erro ao conectar com Asaas (Customer Search): ' . $response->body());

            $customers = $response->json('data');
            $customerId = null;

            if (!empty($customers)) {
                $customerId = $customers[0]['id'];
            } else {
                // Criar Cliente
                $newCustomer = [
                    'name' => substr($empresa->razao, 0, 100),
                    'cpfCnpj' => $cnpjLimpo,
                    'email' => $empresa->email ?? 'noreply@adassoft.com',
                ];

                $createResp = Http::withHeaders($headers)
                    ->post("$baseUrl/customers", $newCustomer);

                if ($createResp->failed())
                    throw new \Exception('Erro ao criar cliente no Asaas.');
                $customerId = $createResp->json('id');
            }

            // 3. Criar Cobrança (Pedido)
            $codTransacao = 'REC-' . date('YmdHis') . '-' . rand(1000, 9999);

            // Criar Pedido no Banco LOCAL primeiro
            // Ajustado para estrutura nova da tabela orders
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'cnpj_revenda' => preg_replace('/\D/', '', $empresa->cnpj), // Salvar apenas números para consistência
                'valor' => $valor,
                'total' => $valor,
                'status' => 'pending',
                // Campos legados para compatibilidade se necessário
                'situacao' => 'AGUARDANDO',
                'external_reference' => $codTransacao,
                'recorrencia' => 'CREDITO',
                // Valor temporário único para ambientes onde a migration nullable não rodou
                'asaas_payment_id' => 'TEMP-' . $codTransacao
            ]);

            // 4. Criar Pagamento no Asaas
            $paymentData = [
                'customer' => $customerId,
                'billingType' => 'PIX',
                'value' => $valor,
                'dueDate' => now()->addDays(1)->format('Y-m-d'),
                'description' => "Recarga Créditos Ref: $codTransacao",
                'externalReference' => $codTransacao,
            ];

            $payResp = Http::withHeaders($headers)
                ->post("$baseUrl/payments", $paymentData);

            if ($payResp->failed()) {
                throw new \Exception('Erro ao criar pagamento no Asaas: ' . $payResp->body());
            }

            $paymentId = $payResp->json('id');

            // Atualiza o pedido com o ID do pagamento gerado
            $order->update(['asaas_payment_id' => $paymentId]);

            // 5. Obter QR Code e Payload PIX
            $qrResp = Http::withHeaders($headers)
                ->get("$baseUrl/payments/$paymentId/pixQrCode");

            if ($qrResp->failed())
                throw new \Exception('Erro ao obter QR Code PIX.');

            $this->pixQrCode = $qrResp->json('encodedImage'); // Base64
            $this->pixCopyPaste = $qrResp->json('payload');
            $this->showPixModal = true;

            Notification::make()
                ->title('PIX Gerado com Sucesso!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro na Recarga')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Polling hook para atualizar saldo automaticamente
    public function refreshSaldo()
    {
        $user = Auth::user();
        $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
        $cnpjFormatado = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpjLimpo);

        $novoSaldo = (float) (Company::where(function ($q) use ($user, $cnpjLimpo, $cnpjFormatado) {
            $q->where('cnpj', $cnpjLimpo)
                ->orWhere('cnpj', $user->cnpj)
                ->orWhere('cnpj', $cnpjFormatado);
        })->sum('saldo') ?? 0.00); // Usa sum() para somar caso existam duplicidades

        if ($novoSaldo != $this->saldo) {
            $this->saldo = $novoSaldo;
            // Se houve recarga (aumento), mostramos confrmação visual
            if ($novoSaldo > $this->saldo) {
                $this->paymentConfirmed = true;
                $this->dispatch('payment-confirmed');

                Notification::make()
                    ->title('Pagamento Recebido!')
                    ->body('Seu saldo foi atualizado.')
                    ->success()
                    ->send();
            }
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CreditHistory::query()
                    ->where(function ($query) {
                        $user = Auth::user();
                        $cleaned = preg_replace('/\D/', '', $user->cnpj);
                        $query->where('empresa_cnpj', $cleaned)
                            ->orWhere('empresa_cnpj', $user->cnpj);
                    })
                    ->orderBy('data_movimento', 'desc')
            )
            ->heading('Extrato de Movimentações')
            ->poll('5s') // Auto-refresh da tabela
            ->columns([
                Tables\Columns\TextColumn::make('data_movimento')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'entrada' => 'Crédito',
                        'saida' => 'Débito',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor (R$)')
                    ->money('BRL')
                    ->color(fn(CreditHistory $record) => $record->tipo === 'entrada' ? 'success' : 'danger')
                    ->prefix(fn(CreditHistory $record) => $record->tipo === 'entrada' ? '+ ' : '- ')
                    ->weight('bold'),
            ])
            ->paginated([10, 25, 50]);
    }
}
