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
            // TODO: Adicionar lógica de auto-reparo baseada no historico_seriais
            throw new Exception('Licença ativa não encontrada para este software');
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
}
