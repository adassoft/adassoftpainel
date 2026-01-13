<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use Exception;
use App\Models\User;
use App\Models\Company;
use App\Models\License;
use Illuminate\Support\Facades\Hash;

class ValidationController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(Request $request)
    {
        $action = $request->input('action');
        $method = $request->method();

        try {
            if ($method === 'GET') {
                return response()->json([
                    'status' => 'online',
                    'version' => '2.0.0 (Laravel)',
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            if ($method !== 'POST') {
                throw new Exception('Método não suportado: ' . $method);
            }

            switch ($action) {
                case 'emitir_token':
                    return $this->emitirToken($request);
                case 'validar_serial':
                    return $this->validarSerial($request);
                case 'status_licenca':
                    return $this->statusLicenca($request);
                case 'listar_terminais':
                    return $this->listarTerminais($request);
                case 'remover_terminal':
                    return $this->removerTerminal($request);
                case 'cadastrar_empresa_usuario':
                    // Não prioritário, manter exception ou implementar depois
                    throw new Exception("Em implementação: cadastrar_empresa_usuario");
                case 'solicitar_recuperacao_senha':
                    throw new Exception("Em implementação: solicitar_recuperacao_senha");
                case 'validar_codigo_recuperacao':
                    throw new Exception("Em implementação: validar_codigo_recuperacao");
                default:
                    throw new Exception('Ação não reconhecida: ' . $action);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'mensagem' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 400);
        }
    }

    private function statusLicenca(Request $request)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $request->input('cnpj') ?? '');

        if (empty($cnpj)) {
            throw new Exception('CNPJ é obrigatório');
        }

        $company = Company::where('cnpj', $cnpj)->first();
        if (!$company) {
            throw new Exception('Empresa não encontrada');
        }

        $query = License::with(['software'])
            ->where('empresa_codigo', $company->codigo)
            ->where('status', 'ativo');

        if ($request->has('software_id')) {
            $query->where('software_id', $request->input('software_id'));
        }

        $licencas = $query->get()->map(function ($licenca) {
            return [
                'software' => $licenca->software->nome_software ?? 'Desconhecido',
                'validade' => $licenca->data_expiracao ? $licenca->data_expiracao->format('Y-m-d') : null,
                'terminais' => (int) $licenca->terminais_permitidos,
                'utilizados' => (int) $licenca->terminais_utilizados
            ];
        });

        return response()->json([
            'success' => true,
            'licencas' => $licencas,
            'total' => $licencas->count(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    private function getPayloadFromToken($token)
    {
        if (empty($token)) {
            throw new Exception('Token obrigatório.');
        }
        $validacao = $this->licenseService->validateLicenseToken($token);
        if (!$validacao['valido']) {
            throw new Exception('Token inválido: ' . ($validacao['erro'] ?? ''));
        }
        return $validacao['payload'];
    }

    private function listarTerminais(Request $request)
    {
        $payload = $this->getPayloadFromToken($request->input('token'));
        $licencaId = $payload['licenca_id'] ?? 0;

        $terminais = \App\Models\TerminalSoftware::with('terminal') // Ensure relationship in model
            ->where('licenca_id', $licencaId)
            ->where('ativo', 1)
            ->get()
            ->map(function ($ts) {
                return [
                    'codigo' => $ts->terminal->CODIGO,
                    'mac' => $ts->terminal->MAC,
                    'nome_computador' => $ts->terminal->NOME_COMPUTADOR,
                    'ultima_atividade' => $ts->ultima_atividade ? $ts->ultima_atividade->toDateTimeString() : null
                ];
            });

        return response()->json([
            'success' => true,
            'terminais' => $terminais
        ]);
    }

    private function removerTerminal(Request $request)
    {
        $payload = $this->getPayloadFromToken($request->input('token'));
        $licencaId = $payload['licenca_id'] ?? 0;
        $terminalId = $request->input('terminal_id');

        // Buscar terminal via TerminaisSoftware que pertença à licença
        $ts = \App\Models\TerminalSoftware::where('licenca_id', $licencaId)
            ->where('terminal_codigo', $terminalId)
            ->first();

        if (!$ts) {
            throw new Exception('Terminal não encontrado nesta licença.');
        }

        $ts->ativo = 0;
        $ts->save();

        // Atualizar contagem
        $newCount = \App\Models\TerminalSoftware::where('licenca_id', $licencaId)->where('ativo', 1)->count();
        License::where('id', $licencaId)->update(['terminais_utilizados' => $newCount]);

        return response()->json([
            'success' => true,
            'mensagem' => 'Terminal removido com sucesso.',
            'terminais_utilizados' => $newCount
        ]);
    }

    private function emitirToken(Request $request)
    {
        $email = strtolower(trim($request->input('email') ?? ''));
        $senha = $request->input('senha') ?? '';
        $softwareId = (int) ($request->input('software_id') ?? 0);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido');
        }
        if (empty($senha)) {
            throw new Exception('Senha é obrigatória');
        }

        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($senha, $user->senha)) {
            throw new Exception('Credenciais inválidas');
        }

        // Verifica Status do Usuário
        if (in_array(strtolower($user->status), ['bloqueado', 'inativo', 'blocked', 'inactive'])) {
            throw new Exception('Usuário bloqueado ou inativo. Entre em contato com o suporte.');
        }

        $company = Company::where('cnpj', $user->cnpj)->first();
        if (!$company) {
            throw new Exception('Empresa não encontrada para o usuário');
        }

        // Buscar licença ativa
        $license = License::with(['software'])
            ->where('empresa_codigo', $company->codigo)
            ->where('software_id', $softwareId)
            ->where('status', 'ativo')
            ->first();

        // AUTO-REPARO: Verifica integridade da licença (Histórico e Token)
        if ($license && $license->status === 'ativo') {
            $needsSave = false;
            $obs = json_decode($license->observacoes, true) ?? [];

            // 1. Garante que existe Token Offline gerado (para o Painel)
            if (!isset($obs['token'])) {
                $payloadOffline = [
                    'serial' => $license->serial_atual,
                    'empresa_codigo' => $license->empresa_codigo,
                    'software_id' => $license->software_id,
                    'validade' => $license->data_expiracao ? $license->data_expiracao->format('Y-m-d') : null,
                    'terminais' => $license->terminais_permitidos,
                    'emitido_em' => now()->toIso8601String()
                ];
                $tokenOffline = $this->licenseService->generateToken($payloadOffline);
                $obs['token'] = $tokenOffline;
                $obs['modo'] = 'auto_reparo';
                $license->observacoes = json_encode($obs);
                $needsSave = true;
            }

            if ($needsSave) {
                $license->save();
            }

            // 2. Garante Histórico de Serial
            $historyExists = \App\Models\SerialHistory::where('serial_gerado', $license->serial_atual)->exists();
            if (!$historyExists) {
                try {
                    \App\Models\SerialHistory::create([
                        'empresa_codigo' => $license->empresa_codigo,
                        'software_id' => $license->software_id,
                        'serial_gerado' => $license->serial_atual,
                        'data_geracao' => $license->data_criacao ?? now(),
                        'validade_licenca' => $license->data_expiracao ? $license->data_expiracao->format('Y-m-d') : null,
                        'terminais_permitidos' => $license->terminais_permitidos,
                        'observacoes' => $license->observacoes, // Copia observações com o token
                        'ativo' => true,
                    ]);
                } catch (\Exception $e) {
                    // Ignora erro
                }
            }
        }

        if (!$license) {
            // Tenta criar licença Trial se for o primeiro acesso e o software permitir
            $software = \App\Models\Software::find($softwareId);

            // Verifica se já existiu alguma licença deste software para esta empresa (evitar abuso de trial)
            $jaTeveLicenca = License::where('empresa_codigo', $company->codigo)
                ->where('software_id', $softwareId)
                ->exists();

            if ($software && !$jaTeveLicenca) {
                try {
                    $diasTeste = $software->setup_dias_teste ?? 7;

                    // Revenda
                    $cnpjRevenda = $company->cnpj_representante;
                    if (empty($cnpjRevenda)) {
                        $master = \App\Models\User::where('acesso', 1)->orderBy('id')->first();
                        if ($master)
                            $cnpjRevenda = $master->cnpj;
                    }

                    $license = License::create([
                        'empresa_codigo' => $company->codigo,
                        'software_id' => $softwareId,
                        'cnpj_revenda' => $cnpjRevenda,
                        'serial_atual' => sprintf('%04X-%04X-%04X-%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)),
                        'data_criacao' => now(),
                        'data_ativacao' => now(),
                        'data_expiracao' => now()->addDays((int) $diasTeste),
                        'terminais_permitidos' => 1,
                        'terminais_utilizados' => 0,
                        'status' => 'ativo',
                        'observacoes' => 'Licença Trial gerada no primeiro login.'
                    ]);

                    // Recarrega relacionamento para usar abaixo
                    $license->load('software');

                } catch (Exception $ex) {
                    \Illuminate\Support\Facades\Log::error('Falha ao criar trial no login: ' . $ex->getMessage());
                    throw new Exception('Licença ativa não encontrada para este software');
                }
            } else {
                throw new Exception('Licença ativa não encontrada para este software');
            }
        }

        // Gerar Token
        $payload = [
            'serial' => $license->serial_atual,
            'empresa_codigo' => $company->codigo,
            'software_id' => $softwareId,
            'software' => $license->software->nome_software ?? 'Unknown',
            'versao_software' => $request->input('versao_software'),
            'instalacao_id' => $request->input('codigo_instalacao'),
            'usuario_email' => $email,
            'usuario_id' => $user->id,
            'licenca_id' => $license->id,
            'emitido_em' => now()->toIso8601String(),
            'expira_em' => now()->addMinutes(15)->toIso8601String()
        ];

        $token = $this->licenseService->generateLicenseToken($payload);

        // --- Lógica de Cobrança (SDK/Tela) ---
        $alertaCobranca = null;
        try {
            $pendingOrder = \App\Models\Order::where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereNotNull('due_date')
                ->orderBy('due_date') // Pega a mais antiga (urgente)
                ->first();

            if ($pendingOrder) {
                $prefs = new \App\Services\NotificationPreferences();
                $dueDate = \Carbon\Carbon::parse($pendingOrder->due_date);
                $now = now();
                $diffDays = $now->diffInDays($dueDate, false); // +10 (future), -5 (past)

                // Verifica se deve notificar (Regra Genérica: Se cliente tem QUALQUER notificação habilitada)
                // Se estiver vencido:
                if ($diffDays < 0) {
                    // Checa se alerta de vencido está ativo para cliente (reuso da config 'overdue')
                    $channels = $prefs->shouldNotify('overdue', 'customer', 'whatsapp')
                        || $prefs->shouldNotify('overdue', 'customer', 'email')
                        || $prefs->shouldNotify('overdue', 'customer', 'sms');

                    if ($channels) {
                        $alertaCobranca = [
                            'titulo' => 'Fatura em Atraso',
                            'mensagem' => "Atenção: Consta uma fatura vencida em {$dueDate->format('d/m/Y')}. Evite o bloqueio.",
                            'link_pagamento' => $pendingOrder->payment_url ?? $pendingOrder->external_url, // Ajustar conforme Asaas
                            'tipo' => 'erro' // Vermelho
                        ];
                    }
                }
                // Se estiver vencendo em breve
                elseif ($diffDays <= $prefs->getDaysBeforeDue()) {
                    $channels = $prefs->shouldNotify('days_before_due', 'customer', 'whatsapp')
                        || $prefs->shouldNotify('days_before_due', 'customer', 'email');

                    if ($channels) {
                        $alertaCobranca = [
                            'titulo' => 'Renovação da Licença',
                            'mensagem' => "Sua licença vencerá em {$dueDate->format('d/m/Y')}. Clique aqui para renovar.",
                            'link_pagamento' => $pendingOrder->payment_url,
                            'tipo' => 'aviso' // Amarelo
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencioso para não travar login
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'expira_em' => $payload['expira_em'],
            'alerta_cobranca' => $alertaCobranca, // Novo campo para o SDK
            'licenca' => [
                'serial' => $license->serial_atual,
                'valido' => ($license->status === 'ativo'),
                'software_id' => $softwareId,
                'software' => $payload['software'],
                'versao' => $request->input('versao_software'),
                'empresa_codigo' => (int) $company->codigo,
                'terminais_permitidos' => (int) $license->terminais_permitidos,
                'data_expiracao' => $license->data_expiracao ? $license->data_expiracao->format('Y-m-d') : null
            ],
            'usuario' => [
                'email' => $user->email,
                'nome' => $user->nome
            ]
        ]);
    }

    private function validarSerial(Request $request)
    {
        $serial = trim($request->input('serial') ?? '');
        $macAddress = trim($request->input('mac_address') ?? '');

        if (empty($serial)) {
            throw new Exception('Serial é obrigatório.');
        }

        $softwareId = $request->input('software_id'); // Validado pelo Middleware

        $validacao = $this->licenseService->validateSerialFull($serial, $softwareId);

        \Illuminate\Support\Facades\Log::info("DEBUG VALIDAR_SERIAL", [
            'serial' => $serial,
            'software_id_request' => $request->input('software_id'), // Se vier
            'resultado' => $validacao
        ]);

        // Registro de Terminal
        if ($validacao['valido'] && !empty($macAddress)) {
            $ip = $request->ip();
            $computerName = $request->input('nome_computador') ?? 'PC-Unknown';
            $instalacaoId = $request->input('codigo_instalacao') ?? $macAddress;

            $registroTerminal = $this->licenseService->registerTerminalUsage(
                $serial,
                $macAddress,
                $computerName,
                $instalacaoId,
                $ip
            );

            if (!$registroTerminal['success']) {
                // Se falhar o registro (ex: limite excedido), invalida a resposta
                return response()->json([
                    'success' => true, // Request em si foi OK
                    'validacao' => [
                        'valido' => false,
                        'erro' => $registroTerminal['erro'] ?? 'Limite de terminais atingido ou erro ao registrar máquina.'
                    ],
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Se registrou com sucesso, precisamos atualizar a contagem de terminais utilizados na resposta
            // O registroTerminal atualiza o banco, mas o $validacao tem dados antigos.
            // Vamos recarregar os dados da licença ou incrementar manualmente.
            // Atualiza a contagem na resposta com o valor real do banco após o registro
            $licenseFresh = \App\Models\License::find($validacao['licenca_id'] ?? 0);
            if ($licenseFresh) {
                $validacao['terminais_utilizados'] = $licenseFresh->terminais_utilizados;
            }
        }



        // --- CHECK DE ATUALIZAÇÃO ---
        $updateInfo = null;
        $versaoCliente = $request->input('versao_software');

        if (!empty($validacao['licenca_id'])) {
            $lic = \App\Models\License::with('software')->find($validacao['licenca_id']);
            if ($lic && $lic->software) {
                $versaoServer = trim($lic->software->versao ?? '');

                // [DEBUG LOG]
                \Illuminate\Support\Facades\Log::info("CHECK UPDATE", [
                    'client_version' => $versaoCliente,
                    'server_version' => $versaoServer,
                    'result_compare' => version_compare(trim($versaoCliente ?? ''), $versaoServer, '<')
                ]);

                // Usa version_compare do PHP (ex: 3.10.15 > 3.10.14)
                if ($versaoCliente && !empty($versaoServer) && version_compare(trim($versaoCliente), $versaoServer, '<')) {
                    $updateInfo = [
                        'disponivel' => true,
                        'nova_versao' => $versaoServer,
                        'mensagem' => "A versão {$versaoServer} já está disponível.",
                        'prioridade' => 'normal'
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'validacao' => $validacao,
            'update' => $updateInfo,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    // === Métodos de Compatibilidade Legado (SDK Delphi) ===

    public function listPlans(Request $request, $software_id = null)
    {
        $softwareId = $software_id ?? $request->input('software_id');

        $query = \App\Models\Plano::where(function ($q) {
            $q->where('status', '!=', 'inativo')
                ->orWhereNull('status');
        });

        if ($softwareId) {
            $query->where('software_id', $softwareId);
        }

        // Recupera o Usuário e tenta extrair contexto da Licença do Token
        $user = $request->user();
        $cnpjRevenda = null;

        // Tenta obter dados da Licença através do Token bearer, se disponível
        $token = $request->bearerToken() ?? $request->input('token');
        if ($token) {
            try {
                // Decodifica o token para pegar o payload (assumindo que o método getPayloadFromToken existe e é acessível)
                // Se for protected, usamos reflection ou duplicamos a lógica simples de decode se for JWT padrão
                // Mas como estamos no Controller, podemos usar $this->getPayloadFromToken se for private/protected (php permite call interna)
                $payload = $this->getPayloadFromToken($token);

                if (!empty($payload['licenca_id'])) {
                    $licenca = \App\Models\License::find($payload['licenca_id']);
                    if ($licenca && !empty($licenca->cnpj_revenda)) {
                        $cnpjRevenda = preg_replace('/\D/', '', $licenca->cnpj_revenda);
                    }
                } elseif (!empty($payload['serial'])) {
                    $licenca = \App\Models\License::where('serial_atual', $payload['serial'])->first();
                    if ($licenca && !empty($licenca->cnpj_revenda)) {
                        $cnpjRevenda = preg_replace('/\D/', '', $licenca->cnpj_revenda);
                    }
                }
            } catch (\Exception $e) {
                // Token inválido ou erro de parse, ignora e segue lógica padrão
            }
        }

        // Fallback: Se não achou pela Licença, tenta pelo Representante da Empresa do Usuário
        if (!$cnpjRevenda && $user) {
            // NEW: Tenta usar a relação direta por ID (Mais seguro e rápido)
            if ($user->empresa && $user->empresa->revenda) {
                $cnpjRevenda = preg_replace('/\D/', '', $user->empresa->revenda->cnpj);
            }
            // LEGACY: Fallback para busca textual se a relação ID não existir
            elseif ($user->cnpj) {
                $cnpjUserLimpo = preg_replace('/\D/', '', $user->cnpj);
                $empresaCliente = \App\Models\Company::where('cnpj', $cnpjUserLimpo)->first();

                if ($empresaCliente) {
                    // Tenta ver se a empresa tem revenda_id mesmo que user->empresa não tenha sido carregado
                    if ($empresaCliente->revenda) {
                        $cnpjRevenda = preg_replace('/\D/', '', $empresaCliente->revenda->cnpj);
                    } elseif (!empty($empresaCliente->cnpj_representante)) {
                        $cnpjRevenda = preg_replace('/\D/', '', $empresaCliente->cnpj_representante);
                    }
                }
            }
        }

        $planos = $query->orderBy('valor')->get()->map(function ($p) use ($cnpjRevenda) {
            $valorFinal = $p->valor;

            // Se identificamos uma revenda, busca preço diferenciado
            if ($cnpjRevenda) {
                $planoRevenda = \App\Models\PlanoRevenda::where('cnpj_revenda', $cnpjRevenda)
                    ->where('plano_id', $p->id)
                    ->first();

                // Prioriza valor de venda da revenda se existir
                if ($planoRevenda && isset($planoRevenda->valor_venda)) {
                    $valorFinal = $planoRevenda->valor_venda;
                }
            }

            return [
                'id' => $p->id,
                'nome_plano' => $p->nome_plano,
                'valor' => $valorFinal, // Valor ajustado
                'recorrencia' => $p->recorrencia,
                'status' => $p->status
            ];
        });

        return response()->json(['planos' => $planos]);
    }

    public function createOrder(Request $request)
    {
        try {
            // Autenticação via Token Legado ou Header
            $token = $request->header('Authorization') ? str_replace('Bearer ', '', $request->header('Authorization')) : $request->input('token');
            $payload = $this->getPayloadFromToken($token);

            $userId = $payload['usuario_id'];
            $planId = $request->input('plan_id');
            // $serial = $request->input('licenca_serial'); // Opcional (renovação)

            // Verifica Plano
            $plano = \App\Models\Plano::find($planId);
            if (!$plano)
                throw new Exception('Plano não encontrado.');

            // Verifica Usuário
            $user = User::find($userId);
            if (!$user)
                throw new Exception('Usuário não encontrado.');

            if (in_array(strtolower($user->status), ['bloqueado', 'inativo'])) {
                throw new Exception('Usuário bloqueado.');
            }

            // Criação do Pedido (Dados Iniciais)
            $codTransacao = 'ORD-' . strtoupper(uniqid());

            // --- Integração Asaas (Geração de Pix) ---
            // --- Integração Asaas (Geração de Pix) ---

            // Lógica para obter Credenciais do Asaas (Revenda ou Matriz)

            $empresaRecebedora = null;
            $serialEnviado = $request->input('licenca_serial');

            // 1. Prioridade: Buscar Revenda vinculada à Licença (Renovação)
            if ($serialEnviado) {
                $licencaAlvo = \App\Models\License::where('serial_atual', $serialEnviado)->first();
                if ($licencaAlvo && !empty($licencaAlvo->cnpj_revenda)) {
                    // Busca CNPJ Limpo
                    $cnpjRevendaLimpo = preg_replace('/\D/', '', $licencaAlvo->cnpj_revenda);
                    $empresaRecebedora = \App\Models\Company::where('cnpj', $cnpjRevendaLimpo)->first();
                }
            }

            // 2. Fallback: Revenda Padrão do Cliente (Se não achou pela licença)
            if (!$empresaRecebedora) {
                // Identificar a Empresa do Usuário
                $empresaCliente = null;

                // Tenta achar a empresa cliente via USER
                if ($user->empresa) {
                    $empresaCliente = $user->empresa;
                } elseif ($user->cnpj) {
                    // Legado
                    $cnpjUserLimpo = preg_replace('/\D/', '', $user->cnpj);
                    $empresaCliente = \App\Models\Company::where('cnpj', $cnpjUserLimpo)->first();
                }

                if ($empresaCliente) {
                    // Tenta achar a Revenda vinculada
                    if ($empresaCliente->revenda) {
                        // Novo padrão: ID
                        $empresaRecebedora = $empresaCliente->revenda;
                    } elseif (!empty($empresaCliente->cnpj_representante)) {
                        // Velho padrão: CNPJ String
                        $cnpjRepLimpo = preg_replace('/\D/', '', $empresaCliente->cnpj_representante);
                        $empresaRecebedora = \App\Models\Company::where('cnpj', $cnpjRepLimpo)->first();
                    }

                    // Se a própria empresa do usuário for revenda (tem token)
                    if (!$empresaRecebedora && !empty($empresaCliente->asaas_access_token)) {
                        $empresaRecebedora = $empresaCliente;
                    }
                }
            }

            // 3. Fallback Final: Revenda Padrão do Sistema
            if (!$empresaRecebedora || empty($empresaRecebedora->asaas_access_token)) {
                // Busca quem está marcado como padrão
                $empresaRecebedora = \App\Models\Company::where('revenda_padrao', true)->first();

                // Se ainda assim não achar, tenta ID 1 como última esperança
                if (!$empresaRecebedora) {
                    $empresaRecebedora = \App\Models\Company::find(1);
                }
            }

            $asaasToken = $empresaRecebedora->asaas_access_token ?? null;

            // Determina modo: Preferência do Banco > ENV > 'production'
            $asaasMode = $empresaRecebedora->asaas_mode ?? env('ASAAS_MODE', 'production');
            // Normaliza modo para o construtor do Service (se for 'producao' vira 'production', etc)
            $asaasMode = ($asaasMode === 'homologacao' || $asaasMode === 'sandbox') ? 'sandbox' : 'production';

            if (empty($asaasToken) && env('ASAAS_API_KEY')) {
                $asaasToken = env('ASAAS_API_KEY');
            }

            if (empty($asaasToken)) {
                $debugInfo = "Revenda CNPJ: " . ($empresaRecebedora->cnpj ?? 'N/A');
                if (isset($licencaAlvo))
                    $debugInfo .= " (Licença Revenda: " . ($licencaAlvo->cnpj_revenda ?? 'N/A') . ")";
                $debugInfo .= " | Cliente CNPJ: " . ($user->cnpj ?? 'N/A');

                throw new Exception("Configuração de Pagamento (Asaas) não encontrada. Detalhes: $debugInfo");
            }

            $asaasService = new \App\Services\AsaasService($asaasToken, $asaasMode);

            // 1. Criar/Recuperar Cliente Asaas
            $customerId = $asaasService->createCustomer($user);

            // 2. Criar Cobrança Pix
            $descricao = "Licenca Soft: " . ($plano->nome_plano ?? 'Plano') . " (Ref: $codTransacao)";
            $pixData = $asaasService->createPixCharge(
                $customerId,
                $plano->valor,
                $descricao,
                $codTransacao
            );

            // Persiste o Pedido com dados do Asaas
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'plano_id' => $plano->id,
                'valor' => $plano->valor,
                'total' => $plano->valor,
                'status' => 'pending',
                'external_reference' => $codTransacao,
                'situacao' => 'AGUARDANDO',
                'licenca_id' => $payload['licenca_id'] ?? null,
                'cnpj' => $user->cnpj,
                'recorrencia' => $plano->recorrencia,
                'asaas_payment_id' => $pixData->id,
                'payment_method' => 'PIX',
                'due_date' => $pixData->expirationDate
            ]);

            // Retorna o payload completo para o Delphi montar a tela de Pix
            return response()->json([
                'success' => true,
                'cod_transacao' => $codTransacao,
                'payment' => [
                    'id' => $pixData->id,
                    'qr_code_base64' => $pixData->encodedImage,
                    'qr_code_payload' => $pixData->payload, // Copia e Cola
                    'valor' => $pixData->value,
                    'vencimento' => $pixData->expirationDate
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'details' => $e->getTraceAsString()], 400);
        }
    }

    public function checkPaymentStatus(Request $request)
    {
        try {
            // Autenticação Opcional ou via Token? Melhor protejer.
            // Para polling simples, vamos permitir check pelo External Reference + Token
            $token = $request->header('Authorization') ? str_replace('Bearer ', '', $request->header('Authorization')) : $request->input('token');
            // Validar token se necessário (mas polling pode ser frequente, validar token toda vez onera.
            // Vamos assumir que quem tem o TransactionID (External Reference) é dono do pedido).

            $codTransacao = $request->input('cod_transacao');
            if (!$codTransacao)
                throw new Exception('Código de transação obrigatório.');

            $order = \App\Models\Order::where('external_reference', $codTransacao)->first();

            if (!$order)
                throw new Exception('Pedido não encontrado.');

            // Se ainda está pendente no nosso banco, consulta o Asaas para garantir (caso webhook falhe/atrase)
            // OBS: Consultar Asaas a cada 3s por milhares de clientes pode dar Rate Limit.
            // O ideal é confiar no Webhook. Mas para UX "Real Time", consultar se o webhook ainda não bateu.
            // Vamos confiar no banco local primeiro. Se status == 'paid', retorna OK.

            // Se quiser forçar check no Asaas (cuidado com cota):
            // $asaasService->getPaymentStatus($order->asaas_payment_id);

            $pago = in_array(strtolower($order->status), ['paid', 'received', 'confirmed']) ||
                in_array(strtolower($order->situacao), ['pago', 'aprovado']);

            return response()->json([
                'success' => true,
                'pago' => $pago,
                'status' => $order->status
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function registerUser(Request $request)
    {
        $acao = $request->input('acao');

        try {
            if ($acao === 'solicitar_codigo') {
                // Simulação: Enviar e-mail com código (Em prod, disparar Mail/Notification)
                $email = $request->input('email');

                // Validações básicas
                if (\App\Models\User::where('email', $email)->exists()) {
                    throw new Exception('E-mail já cadastrado.');
                }

                $code = rand(100000, 999999);
                // Armazenar código em cache/banco temporário (Cache::put("register_$email", $code, 600))

                \Illuminate\Support\Facades\Cache::put("register_code_{$email}", $code, 600); // 10 min

                $appName = config('app.name', 'Adassoft');

                // Envia E-mail
                try {
                    \Illuminate\Support\Facades\Mail::raw("Seu código de verificação {$appName} é: {$code}", function ($message) use ($email, $appName) {
                        $message->to($email)
                            ->subject("Código de Verificação - {$appName}");
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erro ao enviar e-mail de código: ' . $e->getMessage());
                    // Não trava o processo, mas avisa? Melhor deixar passar se for dev, mas em prod é critico.
                }

                $response = [
                    'success' => true,
                    'mensagem' => "Código enviado para {$email}"
                ];

                // Retorna código para teste apenas se NÃO for produção
                if (!app()->environment('production')) {
                    $response['debug_code'] = (string) $code;
                    // Opcional: Anexa na mensagem para o Delphi exibir se ele usa 'mensagem' direto
                    $response['mensagem'] .= " (DEBUG: $code)";
                }

                return response()->json($response);

            } elseif ($acao === 'confirmar_cadastro') {
                $email = strtolower(trim($request->input('email')));
                $codigo = trim($request->input('codigo'));
                $senha = $request->input('senha');
                $nome = trim($request->input('nome'));
                $cnpj = preg_replace('/[^0-9]/', '', $request->input('cnpj'));
                $razao = trim($request->input('razao'));
                $whatsapp = $request->input('whatsapp');
                // ... skipped ...

                // Valida Código
                // ...

                // Double Check: Impede duplicidade se solicitar_codigo falhou ou foi burlado
                if (\App\Models\User::where('email', $email)->exists()) {
                    throw new Exception('E-mail já cadastrado (usuário existente).');
                }

                // Cria Empresa
                // ...

                // Cria Usuário
                $user = User::create([
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => Hash::make($senha),
                    'empresa_id' => $empresa->codigo, // <-- Linked Correctly
                    // 'cnpj' => $empresa->cnpj, // Legacy
                    'nivel' => 'CLIENTE',
                    'acesso' => 3, // 3 = Cliente
                    'status' => 'Ativo'
                ]);

                // *** Criação Automática de Licença de Avaliação ***
                $softwareId = $request->input('software_id');
                $licencaCriada = null;

                if ($softwareId) {
                    $software = \App\Models\Software::find($softwareId);
                    if ($software) {
                        try {
                            $diasTeste = $software->setup_dias_teste ?? 7;

                            // Define Revenda da Licença
                            $cnpjRevenda = $empresa->cnpj_representante;
                            if (empty($cnpjRevenda)) {
                                // Fallback para revenda principal (User ID 1 ou config)
                                // Se não achar admin, deixa null (sem revenda)
                                $master = User::where('acesso', 1)->orderBy('id')->first();
                                if ($master)
                                    $cnpjRevenda = $master->cnpj;
                            }

                            $licencaData = $this->licenseService->createLicense($empresa, $software, (int) $diasTeste, 1);
                            $licencaCriada = $licencaData['license'];

                            // Atualiza Revenda e Observações na licença recém-criada
                            if ($licencaCriada) {
                                $licencaCriada->cnpj_revenda = $cnpjRevenda;
                                $obs = json_decode($licencaCriada->observacoes, true) ?? [];
                                $obs['origem'] = 'cadastro_trial';
                                $licencaCriada->observacoes = json_encode($obs);
                                $licencaCriada->save();
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Erro ao criar licença trial: ' . $e->getMessage());
                            // Não falha o cadastro por isso, mas loga.
                        }
                    }
                }

                // Auto-login: Emitir token
                // Se criamos a licença, usamos os dados dela para um token completo
                if ($licencaCriada) {
                    $payload = [
                        'serial' => $licencaCriada->serial_atual,
                        'empresa_codigo' => $empresa->codigo,
                        'software_id' => $licencaCriada->software_id,
                        'software' => $software->nome_software ?? 'Software',
                        'usuario_email' => $user->email,
                        'usuario_id' => $user->id,
                        'licenca_id' => $licencaCriada->id,
                        'emitido_em' => now()->toIso8601String(),
                        'expira_em' => now()->addDays(30)->toIso8601String()
                    ];
                } else {
                    // Token parcial (sem licença)
                    $payload = [
                        'usuario_id' => $user->id,
                        'empresa_codigo' => $empresa->codigo,
                        'usuario_email' => $user->email,
                        'emitido_em' => now()->toIso8601String(),
                        'expira_em' => now()->addDays(30)->toIso8601String()
                    ];
                }

                $token = $this->licenseService->generateLicenseToken($payload);

                return response()->json([
                    'success' => true,
                    'token' => $token
                ]);
            }

            throw new Exception('Ação inválida.');

        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
