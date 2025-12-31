<?php

namespace App\Services;

use App\Models\SerialHistory;
use App\Models\Company;
use App\Models\Software;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class SerialNumberService
{
    /**
     * Gera um novo serial para a empresa e software especificados.
     */
    public function generate(int $empresaCodigo, int $softwareId, int $validadeDias, int $terminais, ?string $cnpjRevenda = null): array
    {
        $empresa = Company::where('codigo', $empresaCodigo)->firstOrFail();
        $software = Software::findOrFail($softwareId);

        $dataValidade = Carbon::now()->addDays($validadeDias)->format('Y-m-d');

        // Gera o Serial String Base
        $serialString = $this->generateSerialString(
            $empresa->cnpj,
            $dataValidade,
            $terminais,
            $software->codigo,
            $software->nome_software
        );

        $tokenPayload = [
            'empresa' => $empresa->razao,
            'cnpj' => $empresa->cnpj,
            'software' => $software->nome_software,
            'serial' => $serialString,
            'validade' => $dataValidade,
            'terminais' => $terminais,
            'gerado_em' => now()->toIso8601String()
        ];

        if ($cnpjRevenda) {
            $tokenPayload['cnpj_revenda'] = $cnpjRevenda;
        }

        $token = base64_encode(json_encode($tokenPayload));

        // Persistir Histórico
        $historico = SerialHistory::create([
            'empresa_codigo' => $empresaCodigo,
            'software_id' => $softwareId,
            'serial_gerado' => $serialString,
            'data_geracao' => now(),
            'validade_licenca' => $dataValidade,
            'terminais_permitidos' => $terminais,
            'ativo' => true,
            'observacoes' => json_encode(['token' => $token, 'payload' => $tokenPayload])
        ]);

        // Atualizar ou Criar Licença Ativa
        $licencaData = [
            'serial_atual' => $serialString,
            'data_ativacao' => now(),
            'data_expiracao' => $dataValidade,
            'terminais_permitidos' => $terminais,
            'status' => 'ativo',
        ];

        if ($cnpjRevenda) {
            $licencaData['cnpj_revenda'] = $cnpjRevenda;
        }

        License::updateOrCreate(
            [
                'empresa_codigo' => $empresaCodigo,
                'software_id' => $softwareId
            ],
            $licencaData
        );

        return [
            'serial' => $serialString,
            'token' => $token,
            'validade' => $dataValidade
        ];
    }

    private function generateSerialString($cnpj, $dataValidade, $nTerminais, $softwareCodigo, $softwareNome)
    {
        // Limpa CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Algoritmo Legado "cryptChave" e "mudaChave"
        $parte1 = substr($cnpj, 0, 4);
        $parte2 = substr($cnpj, 4, 1);
        $parte3 = substr($cnpj, 5, 1);
        $parte4 = substr($cnpj, 6, 2);
        $parte5 = substr($cnpj, 8, 2);
        $parte6 = substr($cnpj, 10, 2);

        $ch1 = $this->cryptChave($parte1);
        $ch2 = $this->cryptChave($parte2);
        $ch3 = $this->cryptChave($parte3);
        $ch4 = $this->cryptChave($parte4);
        $ch5 = $this->cryptChave($parte5);
        $ch6 = $this->cryptChave($parte6);

        // Processa Data
        $dataC = Carbon::parse($dataValidade);
        $ano = $dataC->format('Y');
        $mes = $dataC->format('m');
        $dia = $dataC->format('d');

        $ch7 = $this->mudaChave(substr($ano, 2, 2));
        $ch8 = $this->mudaChave($mes);
        $ch9 = $this->mudaChave($dia);

        // Soma terminais
        $tSoma = 0;
        $strTerminais = (string) $nTerminais;
        for ($i = 0; $i < strlen($strTerminais); $i++) {
            $tSoma += (int) substr($strTerminais, $i, 1);
        }
        $ch10 = $this->mudaChave((string) $tSoma);

        $serialBase = $ch7 . $ch8 . $ch9 . '-' . $tSoma . '-T' . $ch10;

        // Prefixo Software
        $codSoft = str_pad($softwareCodigo, 3, '0', STR_PAD_LEFT);

        // Prefixo Letras Software (2 primeiras letras ou XX)
        $nomeLimpo = preg_replace('/[^A-Za-z]/', '', $softwareNome);
        $prefixoSoft = strtoupper(substr($nomeLimpo, 0, 2));
        if (strlen($prefixoSoft) < 2)
            $prefixoSoft = str_pad($prefixoSoft, 2, 'X');

        return $prefixoSoft . $codSoft . '-' . $serialBase;
    }

    private function cryptChave($valor)
    {
        $original = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $crypto = 'HJIKLCADEMBFGUXNPTQRWOSVZY9876543210';

        $resultado = '';
        for ($i = 0; $i < strlen($valor); $i++) {
            $char = strtoupper($valor[$i]);
            $pos = strpos($original, $char);
            if ($pos !== false) {
                $resultado .= $crypto[$pos];
            } else {
                $resultado .= $char;
            }
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
}
