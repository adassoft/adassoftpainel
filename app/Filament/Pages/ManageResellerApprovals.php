<?php

namespace App\Filament\Pages;

use App\Models\ResellerConfig;
use App\Models\ResellerConfigHistory;
use App\Models\Configuration;
use App\Services\AAPanelService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class ManageResellerApprovals extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Gestão de Revendas';

    protected static ?string $navigationLabel = 'Aprovações Pendentes';

    protected static ?string $title = 'Aprovações Pendentes (White Label)';

    protected static string $view = 'filament.pages.manage-reseller-approvals';

    public $requests = [];

    public function mount()
    {
        $this->loadRequests();
    }

    public function loadRequests()
    {
        $this->requests = ResellerConfig::whereIn('status_aprovacao', ['pendente', 'com_pendencia'])
            ->with(['user', 'history' => fn($q) => $q->orderBy('data_registro', 'desc')])
            ->get();
    }

    public function approve($id, $force = false)
    {
        $config = ResellerConfig::findOrFail($id);
        $dados = $config->dados_pendentes;

        if (!$dados) {
            Notification::make()->danger()->title('Erro')->body('Dados pendentes não encontrados.')->send();
            return;
        }

        // Integração aaPanel
        $service = app(AAPanelService::class);
        $dominiosArr = array_map('trim', explode(',', $dados['dominios'] ?? ''));
        $errors = [];

        foreach ($dominiosArr as $dom) {
            if (empty($dom))
                continue;
            $res = $service->adicionarDominio($dom);
            if (!$res['success'] && stripos($res['msg'], 'exist') === false) {
                $errors[] = "Falha ao configurar domínio $dom: " . $res['msg'];
            }
        }

        if (count($errors) > 0) {
            if ($force) {
                Notification::make()
                    ->warning()
                    ->title('Atenção: Erros na Integração (Aprovado Forçadamente)')
                    ->body(implode(' | ', $errors))
                    ->persistent()
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title('Erro na Integração Server')
                    ->body(implode(' | ', $errors) . "\n\nUse a opção 'Forçar Aprovação' se o domínio estiver no Cloudflare.")
                    ->persistent()
                    ->send();
                return;
            }
        }

        // Sucesso -> Atualiza Config
        $updateData = array_merge($dados, [
            'status_aprovacao' => 'aprovado',
            'dados_pendentes' => null,
            'mensagem_rejeicao' => null,
            'ativo' => true,
        ]);

        // Fix: Tratar campos nulos e garantir defaults
        $updateData['logo_path'] = $updateData['logo_path'] ?? '';
        $updateData['icone_path'] = $updateData['icone_path'] ?? '';
        $updateData['dominios'] = $updateData['dominios'] ?? '';

        // SEGURANÇA: Filtrar apenas colunas que existem no Model (fillable)
        // Isso evita erros "Column not found" se o JSON tiver dados extras
        $fillable = $config->getFillable();
        $safeData = array_intersect_key($updateData, array_flip($fillable));

        // Garante campos cruciais mesmo após filtro
        $safeData['status_aprovacao'] = 'aprovado';
        $safeData['dados_pendentes'] = null;
        $safeData['mensagem_rejeicao'] = null;
        $safeData['ativo'] = true;

        $config->update($safeData);

        // Log Histórico
        ResellerConfigHistory::create([
            'revenda_config_id' => $config->id,
            'acao' => 'aprovacao',
            'mensagem' => 'Solicitação aprovada pelo admin.',
            'admin_id' => auth()->id(),
        ]);

        // Cache Busting: Nuclear Option (Garante que View, Config e Cache sejam limpos)
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        Notification::make()->success()->title('Aprovado!')->body('Configurações aplicadas e caches do servidor limpos.')->send();
        $this->loadRequests();
    }

    public function reject($id, $motivo)
    {
        $config = ResellerConfig::findOrFail($id);
        $config->update([
            'status_aprovacao' => 'rejeitado',
            'mensagem_rejeicao' => $motivo,
        ]);

        ResellerConfigHistory::create([
            'revenda_config_id' => $config->id,
            'acao' => 'rejeicao',
            'mensagem' => $motivo,
            'admin_id' => auth()->id(),
        ]);

        Notification::make()->warning()->title('Rejeitado')->body('Solicitação rejeitada.')->send();
        $this->loadRequests();
    }

    public function requestCorrection($id, $instrucoes)
    {
        $config = ResellerConfig::findOrFail($id);
        $config->update([
            'status_aprovacao' => 'com_pendencia',
            'mensagem_rejeicao' => $instrucoes,
        ]);

        ResellerConfigHistory::create([
            'revenda_config_id' => $config->id,
            'acao' => 'pendencia',
            'mensagem' => $instrucoes,
            'admin_id' => auth()->id(),
        ]);

        Notification::make()->info()->title('Pendência solicitada')->body('Instruções enviadas para a revenda.')->send();
        $this->loadRequests();
    }

    public function analyzeIA($id)
    {
        $item = ResellerConfig::with('user')->findOrFail($id);
        $dadosPendentes = $item->dados_pendentes;

        if (!$dadosPendentes)
            return "Sem dados para analisar.";

        // Basic DNS Check
        $dnsInfo = "Verificação Técnica de DNS:\n";
        $dominiosArr = array_map('trim', explode(',', $dadosPendentes['dominios'] ?? ''));
        $targetCname = 'express.adassoft.com';

        foreach ($dominiosArr as $dom) {
            if (empty($dom))
                continue;

            $dns = @dns_get_record($dom, DNS_CNAME);
            $found = false;
            if ($dns) {
                foreach ($dns as $r) {
                    if (($r['target'] ?? '') === $targetCname)
                        $found = true;
                }
            }

            if (!$found) {
                $ip = @gethostbyname($dom);
                if ($ip && $ip != $dom) {
                    $dnsInfo .= "- $dom: CNAME incorreto ou registro A (IP: $ip). Requer atenção.\n";
                } else {
                    $dnsInfo .= "- $dom: Domínio inacessível ou DNS não propagado.\n";
                }
            } else {
                $dnsInfo .= "- $dom: DNS Configurado CORRETAMENTE.\n";
            }
        }

        // Call Gemini
        $setting = Configuration::where('chave', 'google_config')->first();
        $apiKey = '';
        $modelName = 'gemini-1.5-flash';

        if ($setting) {
            $c = is_array($setting->valor) ? $setting->valor : json_decode($setting->valor, true);
            $apiKey = $c['gemini_api_key'] ?? '';
            $modelName = $c['gemini_model'] ?? $modelName;
        }

        if (!$apiKey)
            return "API Key do Gemini não configurada.";

        $prompt = "Você é um assistente de moderação para um sistema SaaS White Label. Analise a seguinte solicitação de personalização feita por uma revenda:\n\n";
        $prompt .= "Revendedor: {$item->user->nome} (Email: {$item->user->email})\n\n";
        $prompt .= "Solicitação:\n";
        $prompt .= "- Nome proposto: " . ($dadosPendentes['nome_sistema'] ?? 'N/A') . "\n";
        $prompt .= "- Slogan: " . ($dadosPendentes['slogan'] ?? 'N/A') . "\n";
        $prompt .= "- Domínios: " . ($dadosPendentes['dominios'] ?? 'N/A') . "\n";
        $prompt .= "- Cores (Start/End): " . ($dadosPendentes['cor_primaria_gradient_start'] ?? '?') . " até " . ($dadosPendentes['cor_primaria_gradient_end'] ?? '?') . "\n";
        $prompt .= "- Cor Acento: " . ($dadosPendentes['cor_acento'] ?? 'N/A') . "\n";
        $prompt .= "- Cor Secundária: " . ($dadosPendentes['cor_secundaria'] ?? 'N/A') . "\n";
        $prompt .= $dnsInfo . "\n";
        $prompt .= "Instruções:\n1. Verifique se há termos ofensivos ou marcas famosas.\n2. Avalie se as cores são visualmente agradáveis e harmônicas.\n3. Verifique o DNS.\n4. Responda em HTML simples (<b>, <p>).\n5. Dê um Veredito: Aprovar, Rejeitar ou Investigar.";

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey, [
                "contents" => [["parts" => [["text" => $prompt]]]]
            ]);

            return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Erro ao processar análise.";
        } catch (\Exception $e) {
            return "Erro ao chamar IA: " . $e->getMessage();
        }
    }
}
