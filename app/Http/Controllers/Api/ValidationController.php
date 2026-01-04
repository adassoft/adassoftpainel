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

        // Registrar Log de token (opcional aqui, mas bom ter)

        return response()->json([
            'success' => true,
            'token' => $token,
            'expira_em' => $payload['expira_em'],
            'licenca' => [
                'serial' => $license->serial_atual,
                'valido' => ($license->status === 'ativo'), // Corrigido: Campo obrigatório para o SDK
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

        $validacao = $this->licenseService->validateSerialFull($serial);

        // TODO: Inserir lógica de registro de terminal (registrarUsoTerminal)
        // Por enquanto, apenas valida o serial.

        if ($validacao['valido'] && !empty($macAddress)) {
            $registroTerminal = $this->licenseService->registerTerminalUsage(
                $serial,
                $macAddress,
                $request->input('nome_computador') ?? 'PC-Unknown',
                $request->input('codigo_instalacao') ?? '',
                $request->ip()
            );

            $validacao['terminal_registrado'] = $registroTerminal['success'];

            if (!$registroTerminal['success']) {
                $validacao['erro_terminal'] = $registroTerminal['erro'];
            }
        }

        return response()->json([
            'success' => true,
            'validacao' => $validacao,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    // === Métodos de Compatibilidade Legado (SDK Delphi) ===

    public function listPlans(Request $request)
    {
        $softwareId = $request->input('software_id');

        $query = \App\Models\Plano::where('status', '!=', 'inativo')
            ->orWhereNull('status');

        if ($softwareId) {
            $query->where('software_id', $softwareId);
        }

        $planos = $query->orderBy('valor')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'nome_plano' => $p->nome_plano,
                'valor' => $p->valor,
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

            // Criação do Pedido (Dados Iniciais)
            $codTransacao = 'ORD-' . strtoupper(uniqid());

            // --- Integração Asaas (Geração de Pix) ---
            // TODO: Implementar lógica de Revenda (buscar API Key da revenda se aplicável)
            $asaasService = new \App\Services\AsaasService(env('ASAAS_API_KEY'), env('ASAAS_MODE', 'production'));

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
                'payment_method' => 'PIX'
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
                $email = $request->input('email');
                $codigo = $request->input('codigo');
                $senha = $request->input('senha');
                $nome = $request->input('nome');
                $cnpj = $request->input('cnpj');
                $razao = $request->input('razao');
                $whatsapp = $request->input('whatsapp');
                $parceiroCode = $request->input('codigo_parceiro');

                // Valida Código
                $savedCode = \Illuminate\Support\Facades\Cache::get("register_code_{$email}");
                if (!$savedCode || (string) $savedCode !== (string) $codigo) {
                    throw new Exception('Código de verificação inválido ou expirado.');
                }

                // Verifica Parceiro/Revenda
                $cnpjRepresentante = null; // Revenda Padrão (Null ou config do sistema)
                if (!empty($parceiroCode)) {
                    // Busca por ID, CNPJ ou codigo_revenda (se houver)
                    // Assume que User nivel Revenda tem CNPJ.
                    // Busca User Revenda pelo ID ou CNPJ
                    $revenda = User::where(function ($q) use ($parceiroCode) {
                        $q->where('id', $parceiroCode)
                            ->orWhere('cnpj', $parceiroCode);
                        // ->orWhere('slug', $parceiroCode); // Futuro
                    })
                        ->whereIn('acesso', [1, 2]) // Admin ou Revenda
                        ->first();

                    if ($revenda) {
                        $cnpjRepresentante = $revenda->cnpj;
                    }
                }

                // Se não achou parceiro específico, verifica se existe Revenda Padrão configurada no sistema
                if (empty($cnpjRepresentante)) {
                    // Lógica opcional: Buscar revenda com flag 'revenda_padrao' ou similar.
                    // Por enquanto deixa null.
                }

                // Cria Empresa
                if (\App\Models\Company::where('cnpj', $cnpj)->exists()) {
                    throw new Exception('CNPJ já cadastrado.');
                }

                $empresa = \App\Models\Company::create([
                    'cnpj' => preg_replace('/\D/', '', $cnpj),
                    'razao' => $razao,
                    'status' => 'Ativo',
                    'data' => now(),
                    'fone' => $whatsapp,
                    'email' => $email,
                    'cnpj_representante' => $cnpjRepresentante
                ]);

                // Cria Usuário
                $user = User::create([
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => Hash::make($senha),
                    'cnpj' => $empresa->cnpj, // Vinculo
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

                            $licencaCriada = \App\Models\License::create([
                                'empresa_codigo' => $empresa->codigo,
                                'software_id' => $software->id,
                                'cnpj_revenda' => $cnpjRevenda,
                                'serial_atual' => sprintf('%04X-%04X-%04X-%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)),
                                'data_criacao' => now(),
                                'data_ativacao' => now(),
                                'data_expiracao' => now()->addDays((int) $diasTeste),
                                'terminais_permitidos' => 1,
                                'terminais_utilizados' => 0,
                                'status' => 'ativo',
                                'observacoes' => 'Licença de Avaliação criada automaticamente no cadastro.'
                            ]);
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
                        'expira_em' => now()->addMinutes(60)->toIso8601String()
                    ];
                } else {
                    // Token parcial (sem licença)
                    $payload = [
                        'usuario_id' => $user->id,
                        'empresa_codigo' => $empresa->codigo,
                        'usuario_email' => $user->email,
                        'emitido_em' => now()->toIso8601String(),
                        'expira_em' => now()->addMinutes(60)->toIso8601String()
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
