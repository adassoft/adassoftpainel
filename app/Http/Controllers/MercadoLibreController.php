<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\MercadoLibreConfig;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

class MercadoLibreController extends Controller
{
    // Redireciona para o ML
    public function auth(Request $request)
    {
        // Aqui assumimos que estamos configurando para a empresa atual ou parâmetro
        // Para simplificar, vamos passar o company_id na sessão ou URL
        $appId = config('services.mercadolibre.app_id'); // Ou pegar do DB
        $redirectUri = route('ml.callback');

        // Se as configs estiverem no banco, melhor:
        $config = MercadoLibreConfig::first(); // Pega a primeira para teste ou use lógica de tenant

        $siteDomain = 'mercadolivre.com.br'; // Padrão BR

        if ($config) {
            $appId = $config->app_id;
            // Ajuste simples de domínio baseado no site_id
            if ($config->site_id === 'MLA')
                $siteDomain = 'mercadolibre.com.ar';
            // Adicionar outros maps se necessário
        }

        if (!$appId) {
            return redirect()->back()->with('error', 'App ID não configurado.');
        }

        // PKCE
        $codeVerifier = Str::random(128);
        session(['ml_code_verifier' => $codeVerifier]);

        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $url = "https://auth.{$siteDomain}/authorization?response_type=code&client_id={$appId}&redirect_uri={$redirectUri}&code_challenge={$codeChallenge}&code_challenge_method=S256";

        return redirect($url);
    }

    // Recebe o Code e troca por Token
    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect('/admin')->with('error', 'Código de autorização não recebido.');
        }

        // Recupera configs (Assumindo single tenant ou global por enquanto)
        $config = MercadoLibreConfig::first();

        if (!$config) {
            return redirect('/admin')->with('error', 'Configuração não encontrada.');
        }

        $codeVerifier = session('ml_code_verifier');
        session()->forget('ml_code_verifier');

        $response = Http::post('https://api.mercadolibre.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config->app_id,
            'client_secret' => $config->secret_key,
            'code' => $code,
            'redirect_uri' => route('ml.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            Log::error('Erro ML Auth', $response->json());
            return redirect('/admin')->with('error', 'Falha ao autenticar com Mercado Livre.');
        }

        $data = $response->json();

        $accessToken = $data['access_token'];
        $userId = $data['user_id'];
        $nickname = null;

        // Busca informações do usuário (Nickname)
        try {
            $userResponse = Http::withToken($accessToken)->get("https://api.mercadolibre.com/users/{$userId}");
            if ($userResponse->successful()) {
                $userData = $userResponse->json();
                $nickname = $userData['nickname'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar nickname ML: ' . $e->getMessage());
        }

        // Salva tokens e nickname
        $config->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
            'ml_user_id' => $userId,
            'ml_user_nickname' => $nickname,
            'is_active' => true,
        ]);

        return redirect('/admin/mercado-libre-integration')->with('success', 'Conectado com sucesso!');
    }
    // Recebe notificações do ML (Webhooks)
    public function webhook(Request $request)
    {
        // 1. Validação básica e Resposta Rápida (ACK)
        // O ML espera 200 OK rápido, depois processamos.
        // Como o PHP é síncrono, se demorar muito o ML pode reenviar.
        // O ideal seria despachar um Job, mas vamos tentar fazer inline rápido ou despachar Job depois.
        // Para MVP, faremos inline, mas retornando 200 no final. Se der timeout, ML reenvia.

        $data = $request->all();
        Log::info('ML Webhook:', $data);

        // Verifica Tópico
        $topic = $data['topic'] ?? ($data['type'] ?? null); // orders_v2 ou orders
        $resource = $data['resource'] ?? null;
        $userId = $data['user_id'] ?? null; // ID do Vendedor (Nós)

        if (($topic === 'orders_v2' || $topic === 'orders') && $resource) {
            try {
                // Recupera token da empresa correta
                // Assumindo que o ml_user_id (vendedor) bate com o user_id da notificação
                $config = MercadoLibreConfig::where('ml_user_id', $userId)
                    ->where('is_active', true)
                    ->first();

                if ($config) {
                    // Processamento Síncrono (Cuidado com Timeout)
                    $this->processOrder($resource, $config);
                } else {
                    Log::warning("Webhook ML: Nenhuma config ativa encontrada para Seller ID {$userId}");
                }
            } catch (\Exception $e) {
                Log::error("Webhook ML Processing Error: " . $e->getMessage());
                // Não retornar 500 para não travar a fila do ML, apenas logar.
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function processOrder($resource, $config)
    {
        // 1. Busca Detalhes do Pedido
        $response = Http::withToken($config->access_token)->get("https://api.mercadolibre.com{$resource}");

        if ($response->failed()) {
            throw new \Exception("Falha ao buscar pedido ML: " . $response->body());
        }

        $orderData = $response->json();

        // Estrutura básica: id, status, order_items[], buyer, payments[], total_amount, paid_amount
        $mlOrderId = $orderData['id'];
        $status = $orderData['status']; // paid, confirmed, payment_required, payment_in_process, partially_paid, paid, cancelled, invalid

        // Vamos focar apenas em 'paid' para liberar
        if ($status !== 'paid') {
            Log::info("Webhook ML Order {$mlOrderId}: Status '{$status}' ignorado por enquanto.");
            return;
        }

        // Verifica se pedido já existe para evitar duplicidade
        $existingOrder = \App\Models\Order::where('external_reference', (string) $mlOrderId)
            ->orWhere('external_id', (string) $mlOrderId)
            ->first();

        // Se já existe e já foi processado/entregue, sai
        if ($existingOrder && $existingOrder->status === 'paid') {
            Log::info("Webhook ML Order {$mlOrderId}: Pedido já processado anteriormente.");
            return;
        }

        // 2. Processa Itens
        // O ML envia múltiplos itens se for carrinho.
        $itemsToProcess = [];

        foreach ($orderData['order_items'] as $itemData) {
            $mlItemId = $itemData['item']['id'];
            $title = $itemData['item']['title'];
            $quantity = $itemData['quantity'];

            // Busca mapeamento
            $localMap = \App\Models\MercadoLibreItem::where('ml_id', $mlItemId)->first();

            if ($localMap) {
                if ($localMap->download_id) {
                    $itemsToProcess[] = ['type' => 'download', 'id' => $localMap->download_id, 'title' => $title, 'price' => $itemData['unit_price']];
                } elseif ($localMap->plano_id) {
                    $itemsToProcess[] = ['type' => 'plan', 'id' => $localMap->plano_id, 'title' => $title, 'price' => $itemData['unit_price']];
                } else {
                    Log::warning("Webhook ML Order {$mlOrderId}: Item '{$mlItemId}' mapeado mas sem destino (download/plano).");
                }
            } else {
                Log::warning("Webhook ML Order {$mlOrderId}: Item '{$mlItemId}' ({$title}) não vinculado a produto local.");
            }
        }

        if (empty($itemsToProcess)) {
            Log::info("Webhook ML Order {$mlOrderId}: Nenhum serviço local processável no pedido.");
            return;
        }

        // 3. Identifica ou Cria Usuário
        $buyer = $orderData['buyer'];
        $buyerId = $buyer['id'];
        $buyerName = trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? ''));
        $buyerEmail = $buyer['email'] ?? null;
        if (empty($buyerEmail) || str_contains($buyerEmail, 'missing')) {
            $buyerEmail = "ml_{$buyerId}@mercadolivre.com";
        }

        $user = \App\Models\User::where('email', $buyerEmail)->first();

        if (!$user) {
            // Cria usuário
            $user = \App\Models\User::create([
                'nome' => $buyerName ?: "Cliente ML {$buyerId}",
                'email' => $buyerEmail,
                'login' => $buyerEmail,
                'senha' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                'status' => 'Ativo',
                'acesso' => 3,
                'empresa_id' => $config->company_id
            ]);
            // Importante: criar Empresa vinculada para assinaturas funcionarem
            \App\Models\Empresa::firstOrCreate(
                ['email' => $buyerEmail],
                [
                    'razao' => $buyerName,
                    'cnpj' => 'ML-' . $buyerId, // Fake CNPJ
                    'status' => 'Ativo',
                    'data' => now(),
                    'cnpj_representante' => $config->company_id ? \App\Models\Company::find($config->company_id)?->cnpj : null
                ]
            );

            Log::info("Webhook ML: Usuário criado {$user->id}");
        }

        // 4. Cria o Pedido Local e Entrega
        if (!$existingOrder) {
            $existingOrder = \App\Models\Order::create([
                'user_id' => $user->id,
                'status' => 'paid',
                'total' => $orderData['total_amount'],
                'payment_method' => 'mercadopago',
                'external_id' => $mlOrderId,
                'external_reference' => $mlOrderId,
                'paid_at' => now(),
            ]);
        } else {
            $existingOrder->update(['status' => 'paid', 'paid_at' => now()]);
        }

        $deliveredMessages = [];

        foreach ($itemsToProcess as $item) {
            // Cria Item do Pedido
            \App\Models\OrderItem::firstOrCreate([
                'order_id' => $existingOrder->id,
                'product_name' => $item['title']
            ], [
                'download_id' => $item['type'] === 'download' ? $item['id'] : null,
                'price' => $item['price']
            ]);

            // ENTREGA
            if ($item['type'] === 'download') {
                $dl = \App\Models\Download::find($item['id']);
                if ($dl) {
                    \App\Models\UserLibrary::firstOrCreate([
                        'user_id' => $user->id,
                        'download_id' => $dl->id,
                    ], ['order_id' => $existingOrder->id]);

                    $link = route('downloads.show', $dl->slug ?? $dl->id);
                    $deliveredMessages[] = "📥 DOWNLOAD LIBERADO: {$dl->titulo}\nAcesse: {$link}";
                }
            } elseif ($item['type'] === 'plan') {
                $plano = \App\Models\Plano::find($item['id']);
                if ($plano) {
                    // Atualiza Order com plano
                    $existingOrder->update(['plano_id' => $plano->id]);

                    $software = $plano->software;
                    if ($software) {
                        $painelUrl = 'https://adassoft.com/login'; // Ou url dinamica
                        $deliveredMessages[] = "🚀 ASSINATURA ATIVA: {$plano->nome_plano}\nSeu acesso ao software '{$software->nome_software}' foi liberado.\nAcesse: {$painelUrl}\nUser: {$user->email}\nSenha: (Use 'Esqueci minha senha' se necessário)";
                    }
                }
            }
        }

        // 6. Envia Mensagem no Chat da Compra
        if (!empty($deliveredMessages)) {
            $msgContent = "Olá! Seu pedido foi processado com sucesso.\n\n" . implode("\n\n", $deliveredMessages) . "\n\nObrigado pela preferência!";

            $this->sendMessage($orderData['id'], $config->ml_user_id, $buyerId, $msgContent, $config);
        }

        // 6. Envia Mensagem no Chat da Compra
        if (!empty($deliveredLinks)) {
            $msgContent = "Obrigado pela compra! \n\nSeus produtos digitais foram liberados. Acesse para baixar:\n\n" . implode("\n", $deliveredLinks) . "\n\nSe tiver dúvidas, responda aqui.";

            $this->sendMessage($orderData['id'], $config->ml_user_id, $buyerId, $msgContent, $config);
        }
    }

    private function sendMessage($orderId, $sellerId, $buyerId, $text, $config)
    {
        // Endpoint Messaging: /messages/packs/{pack_id}/sellers/{seller_id}?tag=post_sale
        // pack_id geralmente é o order_id para vendas unitárias
        $url = "https://api.mercadolibre.com/messages/packs/{$orderId}/sellers/{$sellerId}?tag=post_sale";

        $body = [
            'from' => ['user_id' => $sellerId],
            'to' => ['user_id' => $buyerId],
            'text' => $text,
        ];

        try {
            $response = Http::withToken($config->access_token)->post($url, $body);
            if ($response->failed()) {
                Log::error("Webhook ML: Erro ao enviar mensagem: " . $response->body());
            } else {
                Log::info("Webhook ML: Mensagem de entrega enviada para Order {$orderId}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook ML: Exceção envio msg: " . $e->getMessage());
        }
    }
}
