<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    protected $baseUrl;
    protected $token;

    public function __construct($token = null, $mode = 'production')
    {
        $this->token = $token;
        $this->setBaseUrl($mode);
    }

    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function setMode($mode)
    {
        $this->setBaseUrl($mode);
        return $this;
    }

    protected function setBaseUrl($mode)
    {
        if ($mode === 'sandbox') {
            $this->baseUrl = 'https://sandbox.asaas.com/api/v3';
        } else {
            // Production
            $this->baseUrl = 'https://api.asaas.com/v3';
        }
    }

    /**
     * Cria ou recupera um cliente no Asaas baseado no email/cpf
     */
    public function createCustomer($user)
    {
        if ($this->isMockToken()) {
            return 'cus_simulado_' . $user->id;
        }

        // 1. Tenta usar o ID já salvo no banco (vínculo seguro)
        if (!empty($user->asaas_customer_id)) {
            // Atualiza para garantir dados frescos
            try {
                Http::withHeader('access_token', $this->token)
                    ->post($this->baseUrl . "/customers/{$user->asaas_customer_id}", [
                        'name' => $user->nome,
                        'email' => $user->email,
                        'mobilePhone' => $user->empresa?->fone ?? $user->telefone ?? null,
                        'cpfCnpj' => $user->cnpj // Garante que CNPJ também esteja sync (se mudou)
                    ]);
            } catch (\Exception $e) {
                // Ignora falha na atualização para não travar venda, mas loga
                Log::warning('Falha ao atualizar cliente Asaas via ID cacheado: ' . $e->getMessage());
            }
            return $user->asaas_customer_id;
        }

        try {
            // 2. Se não tem ID, Busca pelo CNPJ
            $response = Http::withHeader('access_token', $this->token)
                ->get($this->baseUrl . '/customers', [
                    'cpfCnpj' => $user->cnpj
                ]);

            if ($response->successful() && count($response->json()['data']) > 0) {
                $asaasCustomer = $response->json()['data'][0];
                $customerId = $asaasCustomer['id'];

                // Atualiza Asaas
                Http::withHeader('access_token', $this->token)
                    ->post($this->baseUrl . "/customers/{$customerId}", [
                        'name' => $user->nome,
                        'email' => $user->email,
                        'mobilePhone' => $user->empresa?->fone ?? $user->telefone ?? null,
                    ]);

                // Salva o ID no usuário para não precisar buscar na próxima
                $user->update(['asaas_customer_id' => $customerId]);

                return $customerId;
            }

            // Se não encontrar, cria novo
            $response = Http::withHeader('access_token', $this->token)
                ->post($this->baseUrl . '/customers', [
                    'name' => $user->nome,
                    'cpfCnpj' => $user->cnpj,
                    'email' => $user->email,
                    'mobilePhone' => $user->empresa?->fone ?? $user->telefone ?? null,
                ]);

            if ($response->failed()) {
                Log::error('Erro ao criar cliente Asaas', $response->json());
                throw new \Exception('Erro na integração de pagamento: ' . ($response->json()['errors'][0]['description'] ?? 'Erro desconhecido'));
            }

            $customerId = $response->json()['id'];
            $user->update(['asaas_customer_id' => $customerId]);

            return $customerId;
        } catch (\Exception $e) {
            Log::error('Exceção Asaas Customer: ' . $e->getMessage());
            // Em dev, se falhar conexão, retorna mock para não travar
            if (app()->isLocal())
                return 'cus_fallback_dev';
            throw $e;
        }
    }

    /**
     * Gera uma cobrança PIX imediata
     */
    public function createPixCharge($customerId, $value, $description, $externalReference)
    {
        if ($this->isMockToken()) {
            return (object) [
                'id' => 'pay_simulado_' . uniqid(),
                'status' => 'PENDING',
                'value' => $value,
                'encodedImage' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', // Pixel vermelho mock
                'payload' => '00020126580014br.gov.bcb.pix0136simulacao-pix-copia-cola-1234565204000053039865405' . number_format($value, 2, '', '') . '5802BR5913Adassoft6008Brasilia62070503***6304E2CA',
                'expirationDate' => now()->addDay()->format('Y-m-d')
            ];
        }

        // 1. Criar Cobrança
        $payload = [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $value,
            'dueDate' => now()->format('Y-m-d'), // Vencimento hoje
            'description' => $description,
            'externalReference' => $externalReference
        ];

        $response = Http::withHeader('access_token', $this->token)
            ->post($this->baseUrl . '/payments', $payload);

        if ($response->failed()) {
            Log::error('Erro ao criar cobrança Asaas', $response->json());
            throw new \Exception('Erro ao gerar pagamento: ' . ($response->json()['errors'][0]['description'] ?? 'Erro desconhecido'));
        }

        $paymentId = $response->json()['id'];

        // 2. Obter QR Code e Copia/Cola
        $qrResponse = Http::withHeader('access_token', $this->token)
            ->get($this->baseUrl . "/payments/{$paymentId}/pixQrCode");

        if ($qrResponse->failed()) {
            throw new \Exception('Erro ao obter QR Code PIX.');
        }

        $qrData = $qrResponse->json();

        return (object) [
            'id' => $paymentId,
            'status' => 'PENDING', // Assumido
            'value' => $value,
            'encodedImage' => $qrData['encodedImage'],
            'payload' => $qrData['payload'],
            'expirationDate' => $qrData['expirationDate']
        ];
    }

    protected function isMockToken()
    {
        return empty($this->token) || str_contains($this->token, 'token_simulado') || $this->token === 'mock';
    }
}
