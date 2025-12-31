<div class="space-y-2">
    <p class="text-xs text-gray-500">Use o comando no cron do aaPanel (Shell Script) para rodar diariamente:</p>
    <code class="block p-2 rounded bg-gray-50 border border-gray-100 text-[10px] break-all" style="color: #e83e8c;">
        /usr/bin/php /www/wwwroot/express.adassoft.com/cron_notificar_licencas.php >> /www/wwwroot/express.adassoft.com/logs/cron_notificacoes.log 2>&1
    </code>
    <ul class="list-disc list-inside space-y-1 text-[10px] text-gray-400">
        <li>Crie a pasta <strong>/www/wwwroot/express.adassoft.com/logs</strong> se não existir.</li>
        <li>Ajuste o caminho do PHP se necessário (verifique com <code>which php</code>).</li>
        <li>Agende em Cron > Add Cron > Shell Script > horário diário (ex: 08:00).</li>
    </ul>
</div>