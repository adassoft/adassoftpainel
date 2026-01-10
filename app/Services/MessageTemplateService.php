<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Order;

class MessageTemplateService
{
    public function loadTemplates(): array
    {
        $config = Configuration::where('chave', 'message_templates')->first();

        $defaults = [
            'billing_due_soon_whatsapp' => "Olá {name}, sua fatura Adassoft no valor de {value} vence em {due_date}. Evite bloqueios! Link: {link}",
            'billing_overdue_whatsapp' => "URGENTE: Consta uma fatura em aberto vencida em {due_date}. Valor: {value}. Regularize para evitar suspensão: {link}",

            'billing_due_soon_sms' => "Adassoft: Fatura de {value} vence em {due_date}. Link: {link}",
            'billing_overdue_sms' => "Adassoft: Fatura vencida! Pague agora e evite bloqueio: {link}",
        ];

        if ($config) {
            $json = json_decode($config->valor, true);
            if (is_array($json)) {
                return array_merge($defaults, $json);
            }
        }

        return $defaults;
    }

    public function getFormattedMessage(string $key, $model, array $extraData = []): string
    {
        $templates = $this->loadTemplates();
        $template = $templates[$key] ?? '';

        if (empty($template)) {
            return '';
        }

        $vars = [];

        if ($model instanceof Order) {
            $softwareName = 'Produtos Adassoft';
            if ($model->licenca_id) {
                // Tenta pegar nome do software da licença
                try {
                    $lic = \App\Models\License::find($model->licenca_id);
                    if ($lic)
                        $softwareName = $lic->nome_software;
                } catch (\Exception $e) {
                }
            } elseif ($model->items && $model->items->isNotEmpty()) {
                $softwareName = $model->items->first()->product_name;
            }

            $vars = [
                '{name}' => $model->user->nome ?? 'Cliente',
                '{company}' => $model->user->empresa->razao ?? 'Sua Empresa',
                '{software}' => $softwareName,
                '{value}' => 'R$ ' . number_format($model->total, 2, ',', '.'),
                '{due_date}' => $model->due_date ? \Carbon\Carbon::parse($model->due_date)->format('d/m/Y') : 'N/A',
                '{link}' => $model->payment_url ?? $model->external_url ?? '#',
                '{id}' => $model->id,
            ];
        } elseif ($model instanceof \App\Models\License) {
            $vars = [
                '{name}' => $model->company->razao ?? 'Cliente',
                '{company}' => $model->company->razao ?? 'Sua Empresa',
                '{software}' => $model->nome_software ?? 'Software',
                // Licença não tem valor fixo fácil, talvez via Plano? Deixar vazio ou generico.
                '{value}' => '-',
                '{due_date}' => $model->data_expiracao ? $model->data_expiracao->format('d/m/Y') : 'N/A',
                '{link}' => 'https://painel.adassoft.com/meus-produtos', // Link genérico para renovação
                '{id}' => $model->id,
            ];
        }

        $vars['{days}'] = $extraData['days'] ?? '0';

        return str_replace(array_keys($vars), array_values($vars), $template);
    }
}
