<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generateContent(string $prompt, ?string $imageBase64 = null, string $mimeType = 'image/png'): array
    {
        $config = Configuration::where('chave', 'google_config')->first();

        if (!$config) {
            return ['success' => false, 'error' => 'Configuração do Google não encontrada.'];
        }

        $configData = json_decode($config->valor, true);
        $apiKey = $configData['gemini_api_key'] ?? '';
        $model = $configData['gemini_model'] ?? 'gemini-1.5-flash'; // 1.5 Flash suporta visão

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API Key do Gemini não configurada.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

        // Build Payload
        $parts = [
            ["text" => $prompt]
        ];

        // Se tem imagem, adiciona ao payload
        if ($imageBase64) {
            // Remove header se existir "data:image/png;base64,"
            if (strpos($imageBase64, ',') !== false) {
                $imageBase64 = explode(',', $imageBase64)[1];
            }

            $parts[] = [
                "inlineData" => [
                    "mimeType" => $mimeType,
                    "data" => $imageBase64
                ]
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])
                ->withoutVerifying() // Match legacy behavior
                ->post($url, [
                    "contents" => [
                        [
                            "parts" => $parts
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return ['success' => true, 'reply' => $result['candidates'][0]['content']['parts'][0]['text']];
                }
                return ['success' => false, 'error' => 'Resposta inválida da API.', 'full_response' => $result];
            }

            $errorMsg = $response->json()['error']['message'] ?? 'Erro desconhecido na API.';
            return ['success' => false, 'error' => 'Google API Error: ' . $errorMsg];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
    /**
     * Analisa solicitação de ticket usando IA e histórico.
     */
    public function analyzeTicketRequest(string $subject, string $description): array
    {
        try {
            // 1. Busca tickets similares (Keyword Match simples)
            // Divide o assunto para buscar palavras-chave
            $keywords = explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $subject));
            $keywords = array_filter($keywords, fn($k) => strlen($k) > 3);

            if (empty($keywords)) {
                // Se não tiver keywords úteis, manda só o prompt generativo
                $prompt = "Você é um suporte técnico. O usuário tem este problema: $subject - $description. Ajude-o.";
                return $this->generateContent($prompt);
            }

            $similarTickets = \App\Models\Ticket::where('status', 'closed')
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('subject', 'LIKE', "%{$word}%");
                    }
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $context = "";
            if ($similarTickets->isNotEmpty()) {
                $context = "Histórico de casos resolvidos da empresa:\n";
                foreach ($similarTickets as $t) {
                    $context .= "- REF {$t->id}: {$t->subject}. Desc: " . substr($t->description, 0, 150) . "...\n";

                    // Tenta achar a resposta do suporte
                    // Assumimos que a resposta do suporte é de um usuário que NÃO é o dono do ticket
                    // Ou podemos buscar pelo user de suporte se tiver role, mas vamos simplificar:
                    $lastMsg = $t->messages()
                        ->where('user_id', '!=', $t->user_id)
                        ->latest()
                        ->first();

                    if ($lastMsg) {
                        $context .= "  Resolução dada: " . substr($lastMsg->content, 0, 300) . "\n";
                    }
                }
            } else {
                $context = "Não foram encontrados casos similares exatos no histórico de tickets.\n";
            }

            // Busca na Base de Conhecimento (KnowledgeBase)
            $kbArticles = \App\Models\KnowledgeBase::where('is_active', true)
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('title', 'LIKE', "%{$word}%")
                            ->orWhere('tags', 'LIKE', "%{$word}%");
                    }
                })
                ->limit(3)
                ->get();

            if ($kbArticles->isNotEmpty()) {
                $context .= "\nARTIGOS RECOMENDADOS DA BASE DE CONHECIMENTO:\n";
                foreach ($kbArticles as $kb) {
                    $context .= "- Título: {$kb->title}\n  Resumo: " . strip_tags(substr($kb->content, 0, 400)) . "...\n";
                }
            } else {
                $context .= "\nNenhum artigo específico encontrado na Base de Conhecimento para estas palavras-chave.\n";
            }

            $prompt = "Você é um assistente de suporte técnico Nível 1 da AdasSoft (Software House).
            
            O usuário está descrevendo um problema para abrir um chamado:
            ASSUNTO: {$subject}
            DESCRIÇÃO: {$description}
            
            CONSULTA AO BANCO DE CONHECIMENTO (Casos Passados):
            {$context}
            
            INSTRUÇÕES:
            1. Analise se algum dos casos passados resolve o problema atual. Se sim, cite a solução de forma clara.
            2. Se não houver caso similar, use seu conhecimento geral em TI/Software para sugerir um troubleshooting inicial.
            3. Seja cordial, direto e útil. Formate a resposta em Markdown (use tópicos).
            4. Se não souber, peça para ele continuar com a abertura do chamado com mais detalhes.
            
            RESPONDA EM PORTUGUÊS DO BRASIL.";

            return $this->generateContent($prompt);

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
