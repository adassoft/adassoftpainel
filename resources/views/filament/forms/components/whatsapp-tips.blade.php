<div class="text-sm text-gray-600 dark:text-gray-400">
    <h3 class="font-bold mb-2">Meta Official (Cloud API)</h3>
    <ul class="list-disc pl-5 space-y-1 text-xs mb-4">
        <li>Crie um App em developers.facebook.com e adicione o produto WhatsApp.</li>
        <li>Use um <strong>System User Token</strong> permanente (Business Settings) para evitar expiração.</li>
        <li>Habilite as permissões: <code>whatsapp_business_messaging</code>.</li>
        <li>Custo: Pago por conversa iniciada (marketing/utility).</li>
    </ul>

    <h3 class="font-bold mb-2">Evolution API (Unofficial / Self-Hosted)</h3>
    <ul class="list-disc pl-5 space-y-1 text-xs">
        <li>Requer instalação própria (Docker/VPS).</li>
        <li>Escaneie o QR Code na interface da Evolution para conectar seu WhatsApp.</li>
        <li>Use a <strong>Global API Key</strong> definida no seu `.env` e o nome da instância.</li>
        <li>Custo: Gratuito por mensagem (custo apenas do servidor). Ideal para notificações frequentes.</li>
    </ul>
</div>