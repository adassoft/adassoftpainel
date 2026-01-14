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

            'billing_due_soon_email_subject' => "Aviso de Vencimento: Licença expira em breve",
            'billing_due_soon_email_body' => "Olá {name},\n\nSua licença do {software} vencerá em {due_date}.\n\nPara evitar interrupção nos serviços, por favor realize a renovação.\n\nAcesse seu painel: {link}\n\nAtenciosamente,\nEquipe Adassoft",

            'billing_overdue_email_subject' => "URGENTE: Licença Vencida - {software}",
            'billing_overdue_email_body' => "Olá {name},\n\nVerificamos que sua licença do {software} venceu em {due_date}.\n\nPor favor, normalize sua situação para evitar o bloqueio do sistema.\n\nLink para regularização: {link}\n\nAtenciosamente,\nEquipe Adassoft",

            // Onboarding - Welcome
            'onboarding_welcome_whatsapp' => "Olá *{first_name}*! Seja muito bem-vindo(a) ao *{app_name}*! 🚀\nEstamos muito felizes em ter você conosco.\n\nQualquer dúvida que tiver durante seus testes, pode chamar aqui. Estamos à disposição para ajudar você a tirar o máximo proveito do sistema.\n\nAbraços,\nEquipe {app_name}",
            'onboarding_welcome_email_subject' => "Bem-vindo ao {app_name}!",
            'onboarding_welcome_email_body' => "Olá {first_name},\n\nSeja muito bem-vindo ao {app_name}!\n\nEstamos felizes por sua escolha. Nossos sistemas foram desenvolvidos para facilitar sua gestão.\n\nLembre-se: estamos à inteira disposição para qualquer dúvida. Responda este e-mail ou nos chame no WhatsApp.\n\nAtenciosamente,\nEquipe {app_name}",

            // Onboarding - Day 1
            'onboarding_checkin_day1_whatsapp' => "Oi *{first_name}*, tudo bem?\n\nPassando rapidinho para saber se conseguiu acessar o sistema e se precisa de alguma ajuda nesse início?\n\nQualquer dificuldade, é só falar! 😉",
            'onboarding_checkin_day1_email_subject' => "Tudo certo com o {app_name}?",
            'onboarding_checkin_day1_email_body' => "Olá {first_name},\n\nComo foi seu primeiro dia com o {app_name}?\n\nSe tiver alguma dificuldade ou dúvida, por favor, não hesite em nos contatar. Queremos garantir que sua experiência seja excelente.\n\nAtenciosamente,\nEquipe {app_name}",

            // Onboarding - Day 3
            'onboarding_tips_day3_whatsapp' => "Olá *{first_name}*! 👋\n\nSó para lembrar que o sistema tem vários recursos que podem facilitar seu dia a dia.\nJá explorou todas as abas?\n\nSe precisar de um treinamento rápido ou dica, estamos por aqui!",
            'onboarding_tips_day3_email_subject' => "Dicas para aproveitar o {app_name}",
            'onboarding_tips_day3_email_body' => "Olá {first_name},\n\nEsperamos que esteja gostando do sistema.\n\nVocê sabia que temos vídeos e tutoriais que podem ajudar? Se precisar de algo específico, é só responder este e-mail.\n\nAtenciosamente,\nEquipe {app_name}",

            // Onboarding - Day 6 (Closing)
            'onboarding_closing_day6_whatsapp' => "Oi *{first_name}*!\n\nSeu período de teste gratuito do {app_name} está quase acabando. ⏳\n\nO que achou da experiência? Vamos garantir sua licença oficial para não perder o acesso?\n\nMe avise se tiver alguma dúvida sobre os planos!",
            'onboarding_closing_day6_email_subject' => "Seu teste do {app_name} está acabando",
            'onboarding_closing_day6_email_body' => "Olá {first_name},\n\nSeu período de avaliação termina em breve.\n\nPara continuar aproveitando todos os benefícios sem interrupção, confirme sua assinatura hoje mesmo.\n\nSe tiver dúvidas sobre valores ou formas de pagamento, estamos à disposição.\n\nAtenciosamente,\nEquipe {app_name}",

            // Payment Received
            'onboarding_payment_received_whatsapp' => "Olá *{first_name}*! 🤑\n\nRecebemos a confirmação do seu pagamento! Muito obrigado.\n\nEm instantes sua licença será liberada/renovada. Aguarde só um pouquinho...",
            'onboarding_payment_received_email_subject' => "Pagamento Confirmado - {app_name}",
            'onboarding_payment_received_email_body' => "Olá {first_name},\n\nRecebemos a confirmação do seu pagamento. Obrigado pela confiança!\n\nSua licença está sendo processada e será liberada automaticamente em alguns instantes.\n\nAtenciosamente,\nEquipe {app_name}",

            // License Released
            'onboarding_license_released_whatsapp' => "Tudo pronto, *{first_name}*! ✅\n\nSua licença foi liberada com sucesso!\n\n📅 *Validade:* {validity}\n\nAgora é só aproveitar. Qualquer dúvida, estamos aqui!",
            'onboarding_license_released_email_subject' => "Sua Licença foi Liberada! - {app_name}",
            'onboarding_license_released_email_body' => "Olá {first_name},\n\nTudo pronto! Sua licença foi liberada com sucesso.\n\nValidade: {validity}\n\nVocê já pode acessar o sistema normalmente.\n\nQualquer dúvida, entre em contato.\n\nAtenciosamente,\nEquipe {app_name}",

            // Post-Purchase 15 Days
            'onboarding_post_purchase_15d_whatsapp' => "Olá *{first_name}*! Tudo bem? 😃\n\nPassaram-se 15 dias desde que você ativou sua licença.\nEstá tudo correndo bem? Precisando de algum ajuste ou suporte, conta com a gente!\n\nSucesso!",
            'onboarding_post_purchase_15d_email_subject' => "Como estão as coisas com o {app_name}?",
            'onboarding_post_purchase_15d_email_body' => "Olá {first_name},\n\nFaz 15 dias que oficializamos nossa parceria.\n\nGostaríamos de saber se está tudo funcionando perfeitamente e se você precisa de algum auxílio adicional.\n\nConte sempre conosco!\n\nAtenciosamente,\nEquipe {app_name}",
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
                '{first_name}' => explode(' ', $model->user->nome ?? 'Cliente')[0],
                '{company}' => $model->user->empresa->razao ?? 'Sua Empresa',
                '{software}' => $softwareName,
                '{value}' => 'R$ ' . number_format($model->total, 2, ',', '.'),
                '{due_date}' => $model->due_date ? \Carbon\Carbon::parse($model->due_date)->format('d/m/Y') : 'N/A',
                '{link}' => $model->payment_url ?? $model->external_url ?? '#',
                '{id}' => $model->id,
            ];
        } elseif ($model instanceof \App\Models\License) {

            // Definição do LINK (Lógica de Revenda White-Label)
            $link = 'https://painel.adassoft.com/meus-produtos'; // Default

            if ($model->revenda) {
                // Tenta encontrar a configuração desta revenda
                // A revenda é uma Company. Precisamos achar um User dessa Company que tenha ResellerConfig.
                // Geralmente o dono. Pegamos qualquer config ativa dessa empresa.
                $resellerConfig = \App\Models\ResellerConfig::whereHas('user', function ($q) use ($model) {
                    $q->where('empresa_id', $model->revenda->codigo);
                })->where('ativo', true)->first();

                if ($resellerConfig && !empty($resellerConfig->dominios)) {
                    // Pega o primeiro domínio se for lista (ex: "site.com, painel.site.com")
                    $domains = explode(',', $resellerConfig->dominios);
                    $domain = trim($domains[0]);

                    // Garante protocolo
                    if (!str_starts_with($domain, 'http')) {
                        $domain = 'https://' . $domain;
                    }

                    // Remove barra final se houver
                    $domain = rtrim($domain, '/');

                    $link = "{$domain}/meus-produtos";
                }
            }

            $vars = [
                '{name}' => $model->company->razao ?? 'Cliente',
                '{first_name}' => $model->company->razao ?? 'Cliente', // Na licença, name é empresa
                '{company}' => $model->company->razao ?? 'Sua Empresa',
                '{software}' => $model->nome_software ?? 'Software',
                // Licença não tem valor fixo fácil, talvez via Plano? Deixar vazio ou generico.
                '{value}' => '-',
                '{due_date}' => $model->data_expiracao ? $model->data_expiracao->format('d/m/Y') : 'N/A',
                '{link}' => $link,
                '{id}' => $model->id,
                '{validity}' => $model->data_expiracao ? $model->data_expiracao->format('d/m/Y') : 'N/A',
            ];
        } elseif ($model instanceof \App\Models\User) {
            $vars = [
                '{name}' => $model->nome ?? 'Cliente',
                '{first_name}' => explode(' ', $model->nome ?? 'Cliente')[0],
                '{company}' => $model->empresa->razao ?? 'Sua Empresa',
                '{email}' => $model->email,
            ];
        }

        $vars['{days}'] = $extraData['days'] ?? '0';
        $vars['{app_name}'] = config('app.name', 'Adassoft');

        // Merge extra vars safe
        foreach ($extraData as $k => $v) {
            if (is_string($v) || is_numeric($v)) {
                $vars["{{$k}}"] = $v;
            }
        }

        return str_replace(array_keys($vars), array_values($vars), $template);
    }
}
