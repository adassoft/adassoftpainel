<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $c = preg_replace('/\D/', '', $value);

        if (strlen($c) === 11) {
            if (!$this->validateCpf($c)) {
                $fail('O CPF informado é inválido.');
            }
        } elseif (strlen($c) === 14) {
            if (!$this->validateCnpj($c)) {
                $fail('O CNPJ informado é inválido.');
            }
        } else {
            $fail('O documento deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
        }
    }

    protected function validateCpf($cpf)
    {
        if (preg_match('/(\d)\1{10}/', $cpf))
            return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d)
                return false;
        }
        return true;
    }

    protected function validateCnpj($cnpj)
    {
        if (preg_match('/(\d)\1{13}/', $cnpj))
            return false;

        $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 2; $i++) {
            for ($s = 0, $j = 0; $j < 12 + $i; $j++) {
                $s += $cnpj[$j] * $b[$j + 1 - $i];
            }
            $s = ($s % 11) < 2 ? 0 : 11 - ($s % 11);
            if ($cnpj[12 + $i] != $s)
                return false;
        }
        return true;
    }
}
