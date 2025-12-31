<ul class="list-disc list-inside space-y-2 text-xs text-info">
    <li>Crie um App em developers.facebook.com &gt; Create App &gt; Other &gt; Type: Business.</li>
    <li>Em WhatsApp &gt; API Setup, obtenha o <strong>Phone Number ID</strong> e faça o "Add Phone Number" para o número
        definitivo.</li>
    <li>Gere um <strong>token permanente</strong>: Business Settings (business.facebook.com/settings) &gt; Users &gt;
        System Users &gt; Add &gt; Generate New Token &gt; selecione o App e marque permissões
        whatsapp_business_messaging e whatsapp_business_management.</li>
    <li>Salve o token gerado aqui (Access Token) e o Phone Number ID; teste com um número que tenha opt-in para receber.
    </li>
    <li>Para produção, publique templates se for usar mensagens template; para texto simples, mantenha o opt-in e evite
        links encurtados.</li>
</ul>
<div class="mt-3 text-[10px] text-gray-400 italic">
    Se o token expirar ou for revogado, gere outro System User token e substitua aqui.
</div>