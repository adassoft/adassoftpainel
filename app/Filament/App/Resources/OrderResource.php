<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OrderResource\Pages;
use App\Filament\App\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Meus Pedidos';
    protected static ?string $pluralModelLabel = 'Meus Pedidos';
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $slug = 'meus-pedidos';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        // Filtra pedidos do usuário logado pelo ID, já que a tabela orders usa user_id
        return parent::getEloquentQuery()->where('user_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\Layout\View::make('filament.app.resources.order-resource.pages.order-card'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('pagar')
                    ->label('Pagar PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn(Order $record) => in_array(strtoupper($record->situacao), ['PENDENTE', 'AGUARDANDO']))
                    ->modalHeading('Efetuar Pagamento')
                    ->modalContent(function (Order $record) {
                        try {
                            $apiKey = null;
                            $walletId = null;
                            $baseUrl = 'https://api.asaas.com/v3';
                            $userAgent = 'Adassoft/1.0';

                            // 1. Tentar pegar Credenciais da Revenda (Prioridade)
                            if (!empty($record->cnpj_revenda)) {
                                $revenda = \App\Models\Company::where('cnpj', preg_replace('/\D/', '', $record->cnpj_revenda))->first();
                                if ($revenda && !empty($revenda->asaas_access_token)) {
                                    $apiKey = $revenda->asaas_access_token;
                                    // Revendas assumimos PROD por padrão
                                    $baseUrl = 'https://api.asaas.com/v3';
                                }
                            }

                            // 2. Fallback somente se não tiver revenda definida (Venda Direta Admin)
                            if (!$apiKey && empty($record->cnpj_revenda)) {
                                $gateway = \Illuminate\Support\Facades\DB::table('gateways')->where('gateway_name', 'Asaas')->where('active', true)->first();
                                if ($gateway) {
                                    $apiKey = $gateway->access_token;
                                    $baseUrl = ($gateway->producao === 's') ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
                                }
                            }

                            if (!$apiKey) {
                                throw new \Exception('O recebedor (Revenda ou Admin) não configurou o Asaas para receber este pagamento.');
                            }

                            $headers = ['access_token' => $apiKey, 'Content-Type' => 'application/json', 'User-Agent' => $userAgent];

                            // Buscar/Criar Cliente
                            $cnpjCliente = preg_replace('/\D/', '', $record->cnpj);
                            $custResp = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/customers", ['cpfCnpj' => $cnpjCliente]);
                            $customerId = $custResp->json('data.0.id');

                            if (!$customerId) {
                                // Pega dados do cliente no banco local
                                $dadosCliente = \App\Models\Company::where('cnpj', $cnpjCliente)->first();
                                $nomeCliente = $dadosCliente->razao ?? 'Cliente AdasSoft';
                                $emailCliente = $dadosCliente->email ?? 'financeiro@adassoft.com';

                                $newC = \Illuminate\Support\Facades\Http::withHeaders($headers)->post("$baseUrl/customers", [
                                    'name' => $nomeCliente,
                                    'cpfCnpj' => $cnpjCliente,
                                    'email' => $emailCliente
                                ]);

                                if ($newC->failed())
                                    throw new \Exception('Erro ao cadastrar cliente no Asaas: ' . ($newC->json('errors.0.description') ?? $newC->body()));
                                $customerId = $newC->json('id');
                            }

                            // Verifica ou Cria Cobrança
                            $payResp = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/payments", ['externalReference' => $record->cod_transacao]);
                            $paymentId = $payResp->json('data.0.id');

                            if (!$paymentId) {
                                $newP = \Illuminate\Support\Facades\Http::withHeaders($headers)->post("$baseUrl/payments", [
                                    'customer' => $customerId,
                                    'billingType' => 'PIX',
                                    'value' => $record->valor,
                                    'dueDate' => \Carbon\Carbon::parse($record->data)->addDays(1)->format('Y-m-d'),
                                    'externalReference' => $record->cod_transacao,
                                    'description' => "Pedido #{$record->id} - Licença de Software"
                                ]);

                                if ($newP->failed())
                                    throw new \Exception('Erro ao criar PIX: ' . ($newP->json('errors.0.description') ?? $newP->body()));
                                $paymentId = $newP->json('id');
                            }

                            // Pega QR Code
                            $qrResp = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/payments/$paymentId/pixQrCode");

                            if ($qrResp->failed())
                                throw new \Exception('Erro ao obter QR Code.');

                            $qrImage = $qrResp->json('encodedImage');
                            $copyPaste = $qrResp->json('payload');

                            return view('filament.app.pages.actions.pix-modal', [
                                'qrCode' => $qrImage,
                                'copyPaste' => $copyPaste,
                                'valor' => $record->valor
                            ]);

                        } catch (\Exception $e) {
                            return view('filament.app.pages.actions.error-modal', ['message' => $e->getMessage()]);
                        }
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Order $record) => in_array(strtoupper($record->situacao), ['PENDENTE', 'AGUARDANDO']))
                    ->action(fn(Order $record) => $record->update(['situacao' => 'cancelado'])),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
