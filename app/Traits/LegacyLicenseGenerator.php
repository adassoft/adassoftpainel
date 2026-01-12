<?php

namespace App\Traits;

use App\Models\License;
use App\Models\Company;
use App\Models\CreditHistory;
use App\Models\Software;
use App\Models\SerialHistory;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

trait LegacyLicenseGenerator
{
    private function getLicenseSecret(): string
    {
        return env('SHIELD_LICENSE_SECRET', 'defina-um-segredo-seguro-e-unico');
    }

    private function getOfflineSecret(): string
    {
        return env('SHIELD_OFFLINE_SECRET', 'defina-um-segredo-offline');
    }

    // --- Criptografia Legada (funcoes_serial_avancado.php) ---

    private function cryptChave($valor)
    {
        $original = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $crypto = 'HJIKLCADEMBFGUXNPTQRWOSVZY9876543210';
        $resultado = '';
        for ($i = 0; $i < strlen($valor); $i++) {
            $char = strtoupper($valor[$i]);
            $pos = strpos($original, $char);
            $resultado .= ($pos !== false) ? $crypto[$pos] : $char;
        }
        return $resultado;
    }

    private function mudaChave($valor)
    {
        $mudancas = [
            'D' => '0',
            'B' => '1',
            'F' => '2',
            'G' => '3',
            'U' => '4',
            'X' => '5',
            'N' => '6',
            'P' => '7',
            'T' => '8',
            'Q' => '9',
            '0' => 'D',
            '1' => 'B',
            '2' => 'F',
            '3' => 'G',
            '4' => 'U',
            '5' => 'X',
            '6' => 'N',
            '7' => 'P',
            '8' => 'T',
            '9' => 'Q'
        ];
        $resultado = '';
        for ($i = 0; $i < strlen($valor); $i++) {
            $char = strtoupper($valor[$i]);
            $resultado .= $mudancas[$char] ?? $char;
        }
        return $resultado;
    }

    private function gerarChaveEmpresa($cnpj, $dataValidade, $nTerminais, $softwareId = null)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        $parte1 = substr($cnpj, 0, 4);
        $parte2 = substr($cnpj, 4, 1);
        $parte3 = substr($cnpj, 5, 1);
        $parte4 = substr($cnpj, 6, 2);
        $parte5 = substr($cnpj, 8, 2);
        $parte6 = substr($cnpj, 10, 2);

        $ch1 = $this->cryptChave($parte1); // Not used in legacy final string? Legacy code didn't use ch1..ch6 in final concat?
        // Wait, checking legacy code:
        // $serialBase = $ch7 . $ch8 . $ch9 . '-' . $tSoma . '-T' . $ch10;
        // Yes, parts 1-6 are unused in the final string in the legacy function provided!
        // I will trust the legacy code provided.

        $dataArray = explode('-', $dataValidade);
        $ano = $dataArray[0];
        $mes = $dataArray[1];
        $dia = $dataArray[2];

        $ch7 = $this->mudaChave(substr($ano, 2, 2));
        $ch8 = $this->mudaChave($mes);
        $ch9 = $this->mudaChave($dia);

        $tSoma = 0;
        for ($i = 0; $i < strlen((string) $nTerminais); $i++) {
            $tSoma += (int) substr((string) $nTerminais, $i, 1);
        }

        $ch10 = $this->mudaChave((string) $tSoma);

        return $ch7 . $ch8 . $ch9 . '-' . $tSoma . '-T' . $ch10;
    }

    private function gerarSerialSeguro(array $empresa, array $software, int $tentativas = 3): string
    {
        $nomeSoftware = $software['nome_software'] ?? '';
        $prefixo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nomeSoftware), 0, 3));
        if (strlen($prefixo) < 3) {
            $prefixo = str_pad($prefixo, 3, 'X');
        }

        $cnpjLimpo = preg_replace('/[^0-9]/', '', $empresa['cnpj'] ?? '');

        for ($i = 0; $i < $tentativas; $i++) {
            $entropy = implode(':', [
                $cnpjLimpo,
                $software['id'] ?? '',
                microtime(true),
                bin2hex(random_bytes(8))
            ]);

            $hash = strtoupper(bin2hex(hash_hmac('sha256', $entropy, $this->getLicenseSecret(), true)));
            $hash = substr($hash, 0, 12);
            $segmentado = trim(chunk_split($hash, 4, '-'), '-');
            $serial = sprintf('SER-%s-%s', $prefixo, $segmentado);

            if (strlen($serial) >= 8) {
                return $serial;
            }
        }
        throw new Exception('Não foi possível gerar serial seguro. Tente novamente.');
    }

    private function gerarTokenLicenca(array $payload): string
    {
        if (empty($payload['serial'])) {
            throw new InvalidArgumentException('Payload sem serial.');
        }

        $payload['emitido_em'] = $payload['emitido_em'] ?? date('c');
        ksort($payload);

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = $this->shield_base64url_encode($json);
        $assinatura = hash_hmac('sha256', $encoded, $this->getLicenseSecret());

        return $encoded . '.' . $assinatura;
    }

    private function shield_base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function registrarObservacaoLicenca(string $token, array $payload, string $modo = 'online', array $extras = []): string
    {
        $conteudo = array_merge([
            'modo' => $modo,
            'token' => $token,
            'payload' => $payload
        ], $extras);
        return json_encode($conteudo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function serialJaExiste($serial)
    {
        return SerialHistory::where('serial_gerado', $serial)->exists();
    }

    private function atualizarObservacoesComDados($observacaoAtual, array $dadosExtras)
    {
        $base = json_decode($observacaoAtual, true);
        if (!is_array($base)) {
            $base = empty($observacaoAtual) ? [] : ['observacao_legado' => $observacaoAtual];
        }

        foreach ($dadosExtras as $chave => $valor) {
            if ($valor === null) {
                unset($base[$chave]);
            } else {
                $base[$chave] = $valor;
            }
        }
        return json_encode($base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // --- Método Principal para ser chamado no Filament ---

    public function gerarLicencaCompleta($empresaCodigo, $softwareId, $validadeDias = 30, $nTerminais = 1)
    {
        $empresa = Company::where('codigo', $empresaCodigo)->firstOrFail();
        $software = Software::where('id', $softwareId)->first();

        if (!$software) {
            throw new Exception("Software não encontrado no banco de dados (ID: {$softwareId}). Verifique se o cadastro do software está ativo.");
        }

        $validadeSolicitada = max(1, (int) $validadeDias);
        $saldoRemanescenteDias = 0;

        // Verifica licença ativa para adicionar dias
        $licencaAtual = License::where('empresa_codigo', $empresaCodigo)
            ->where('software_id', $softwareId)
            ->first();

        if ($licencaAtual && in_array($licencaAtual->status, ['ativo', 'suspenso'])) {
            if ($licencaAtual->data_expiracao > now()) {
                $saldoRemanescenteDias = (int) now()->diff($licencaAtual->data_expiracao)->days;
            }
        }

        $validadeTotalDias = $validadeSolicitada + $saldoRemanescenteDias;
        $dataValidade = now()->addDays($validadeTotalDias)->format('Y-m-d');

        // Gerar Serial Seguro (Hash Aleatório único)
        // O usuário confirmou que prefere a lógica nova segura, não a legada determinística.

        $serialGerado = null;
        for ($tentativa = 0; $tentativa < 5; $tentativa++) {
            $novoSerial = $this->gerarSerialSeguro($empresa->toArray(), $software->toArray());
            // Verifica duplicidade no DB
            if (!$this->serialJaExiste($novoSerial)) {
                $serialGerado = $novoSerial;
                break;
            }
        }

        if (!$serialGerado)
            throw new Exception('Não foi possível gerar um serial único (Time-out na geração de entropia).');

        // Inativar anteriores
        $antigas = SerialHistory::where('empresa_codigo', $empresaCodigo)
            ->where('software_id', $softwareId)
            ->where('ativo', 1)
            ->get();

        foreach ($antigas as $antiga) {
            $obs = $this->atualizarObservacoesComDados($antiga->observacoes, [
                'status_inativacao' => 'renovado',
                'status_inativacao_data' => date('c')
            ]);
            $antiga->update(['ativo' => 0, 'observacoes' => $obs]);
        }

        // Token
        $payloadToken = [
            'serial' => $serialGerado,
            'empresa_codigo' => $empresaCodigo,
            'software_id' => $softwareId,
            'validade' => $dataValidade,
            'terminais' => $nTerminais
        ];

        if ($saldoRemanescenteDias > 0) {
            $payloadToken['saldo_remanescente_dias'] = $saldoRemanescenteDias;
            $payloadToken['validade_solicitada_dias'] = $validadeSolicitada;
            $payloadToken['validade_total_dias'] = $validadeTotalDias;
        }

        $tokenLicenca = $this->gerarTokenLicenca($payloadToken);
        $extrasObs = ($saldoRemanescenteDias > 0) ? [
            'saldo_remanescente_dias' => $saldoRemanescenteDias,
            'validade_solicitada_dias' => $validadeSolicitada,
            'validade_total_dias' => $validadeTotalDias
        ] : [];

        $observacoes = $this->registrarObservacaoLicenca($tokenLicenca, $payloadToken, 'online', $extrasObs);

        // Inserir Historico
        SerialHistory::create([
            'empresa_codigo' => $empresaCodigo,
            'software_id' => $softwareId,
            'serial_gerado' => $serialGerado,
            'validade_licenca' => $dataValidade,
            'terminais_permitidos' => $nTerminais,
            'observacoes' => $observacoes,
            'ativo' => 1
        ]);

        // Atualizar Licenca Ativa (Upsert logic)

        // Correcting Upsert timestamps logic:
        $licenca = License::firstOrNew(['empresa_codigo' => $empresaCodigo, 'software_id' => $softwareId]);
        if (!$licenca->exists) {
            $licenca->data_criacao = now();
        }
        $licenca->serial_atual = $serialGerado;
        $licenca->data_ativacao = now();
        $licenca->data_expiracao = $dataValidade;
        $licenca->terminais_permitidos = $nTerminais;
        $licenca->status = 'ativo';
        $licenca->save();

        return [
            'success' => true,
            'serial' => $serialGerado,
            'validade' => $dataValidade
        ];
    }

    public function processarEntregaPedido(Order $pedido)
    {
        if ($pedido->status_entrega === 'entregue') {
            throw new Exception('Este pedido já foi entregue.');
        }

        $resellerCompany = Company::where('cnpj', $pedido->cnpj_revenda)->lockForUpdate()->firstOrFail();

        $valor = (float) $pedido->valor;
        // Verifica se JÁ houve débito para este pedido (evitar duplicidade em reprocessamentos)
        $jaDebitou = CreditHistory::where('empresa_cnpj', $pedido->cnpj_revenda)
            ->where('descricao', 'like', "%Pedido #{$pedido->id}%")
            ->where('tipo', 'saida')
            ->exists();

        if (!$jaDebitou) {
            if ($resellerCompany->saldo < $valor) {
                throw new Exception("Saldo insuficiente na carteira da revenda para liberar este pedido. Necessário: R$ " . number_format($valor, 2, ',', '.'));
            }

            $resellerCompany->decrement('saldo', $valor);

            CreditHistory::create([
                'empresa_cnpj' => $pedido->cnpj_revenda,
                'usuario_id' => Auth::id() ?? 0,
                'tipo' => 'saida',
                'valor' => $valor,
                'descricao' => "Liberação de Pedido #{$pedido->id} - Cliente: {$pedido->cnpj}",
                'data_movimento' => now()
            ]);
        }

        $cnpjLimpo = preg_replace('/\D/', '', $pedido->cnpj);
        $clienteEmpresa = Company::where('cnpj', $cnpjLimpo)->orWhere('cnpj', $pedido->cnpj)->first();
        if (!$clienteEmpresa) {
            throw new Exception("Empresa cliente não encontrada para CNPJ: {$pedido->cnpj}");
        }

        $diasValidade = ($pedido->recorrencia ?? 1) * 30;

        // Prioriza o software do PLANO, pois todo pedido tem plano. Fallback para direto se houver.
        $softwareId = $pedido->plan?->software_id ?? $pedido->software_id;

        $resultado = $this->gerarLicencaCompleta(
            $clienteEmpresa->codigo,
            $softwareId,
            $diasValidade
        );

        $pedido->update([
            'status_entrega' => 'entregue',
            'serial_gerado' => $resultado['serial'],
            'status' => 'pago' // Mudei para 'status'. Se o enum for 'paid' avisar.
        ]);

        return $resultado;
    }
}
