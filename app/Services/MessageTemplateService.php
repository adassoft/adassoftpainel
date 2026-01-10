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

    public function getFormattedMessage(string $key, Order $order, array $extraData = []): string
    {
        $templates = $this->loadTemplates();
        $template = $templates[$key] ?? '';

        if (empty($template)) {
            return '';
        }

        // Dados para substituição
        $vars = [
            '{name}' => $order->user->nome ?? 'Cliente',
            '{company}' => $order->user->empresa->razao ?? 'Sua Empresa',
            '{value}' => 'R$ ' . number_format($order->total, 2, ',', '.'),
            '{due_date}' => $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('d/m/Y') : 'N/A',
            '{link}' => $order->payment_url ?? $order->external_url ?? '#',
            '{days}' => $extraData['days'] ?? '0',
            '{id}' => $order->id,
        ];

        return str_replace(array_keys($vars), array_values($vars), $template);
    }
}
