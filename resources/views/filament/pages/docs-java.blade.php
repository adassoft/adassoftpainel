<x-filament::page>
    <style>
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow-x: auto;
            border: 1px solid #333;
        }

        .code-block pre {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9rem;
            margin: 0;
            color: #d4d4d4;
        }

        .keyword {
            color: #569cd6;
            font-weight: bold;
        }

        .string {
            color: #ce9178;
        }

        .comment {
            color: #6a9955;
        }

        .function {
            color: #dcdcaa;
        }

        .class {
            color: #4ec9b0;
        }

        .number {
            color: #b5cea8;
        }

        .type {
            color: #4ec9b0;
        }
    </style>

    <div class="space-y-6">
        <x-filament::card>
            <x-slot name="heading">
                Visão Geral
            </x-slot>
            <p class="text-gray-500 dark:text-gray-400">
                O <strong>SDK para Java</strong> oferece uma solução completa e multiplataforma para
                integrar o licenciamento em aplicações Java Desktop ou Server-side.
                Seguindo a política de "Zero Dependencies", o SDK utiliza apenas bibliotecas nativas do Java
                11+ (`java.net.http`, `java.util`, etc.), facilitando a integração sem poluir seu `pom.xml`
                ou `build.gradle`.
            </p>

            <div class="mt-4">
                <h5 class="font-bold mb-2">📂 Estrutura do Pacote (com.adassoft.shield)</h5>
                <ul class="list-disc pl-5 space-y-1 text-gray-700 dark:text-gray-400">
                    <li><code>ShieldClient.java</code>: O controlador principal. Realiza chamadas à API, validação e
                        lógica de negócios.</li>
                    <li><code>ShieldConfig.java</code>: Classe de configuração (URL Base, API Key, Software ID).</li>
                    <li><code>models/</code>:
                        <ul class="list-disc pl-5 mt-1">
                            <li><code>LicenseInfo.java</code>: Representa o estado da licença (Validade, Terminais,
                                Status).</li>
                            <li><code>SessionInfo.java</code>: Gerencia token de sessão.</li>
                            <li><code>Plan.java</code>: Representa planos de renovação.</li>
                        </ul>
                    </li>
                    <li><code>utils/</code>:
                        <ul class="list-disc pl-5 mt-1">
                            <li><code>SecurityUtil.java</code>: Fingerprint de hardware (MAC Address) e Criptografia AES
                                local.</li>
                            <li><code>HttpUtil.java</code>: Wrapper para <code>HttpClient</code> nativo.</li>
                            <li><code>JsonHelper.java</code>: Parser JSON leve.</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Como Integrar em seu Projeto
            </x-slot>

            <h5 class="text-xl font-bold mt-4 mb-2 text-primary-600">Passo 1: Instalação</h5>
            <p class="mb-4">Basta copiar o pacote <code>com.adassoft.shield</code> para dentro da pasta <code>src</code>
                do seu projeto Java (Maven, Gradle ou Ant).</p>
            <p class="mb-4">Requisito: <strong>Java 11</strong> ou superior.</p>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 2: Inicialização</h5>
            <p class="mb-4">No método `main` ou classe de configuração da sua app:</p>

            <div class="code-block">
                <pre>
<span class="keyword">import</span> com.adassoft.shield.*;

<span class="keyword">public class</span> <span class="class">App</span> {
    <span class="keyword">public static void</span> <span class="function">main</span>(<span class="class">String</span>[] args) {
        <span class="comment">// 1. Configurar</span>
        <span class="class">ShieldConfig</span> config = <span class="keyword">new</span> <span class="class">ShieldConfig</span>(
            <span class="string">"SEU_APP_API_KEY"</span>,  <span class="comment">// API Key do Software</span>
            <span class="number">1</span>,                  <span class="comment">// Software ID</span>
            <span class="string">"1.0.0"</span>             <span class="comment">// Versão da sua App</span>
        );
        
        <span class="comment">// 2. Instanciar Cliente</span>
        <span class="class">ShieldClient</span> client = <span class="keyword">new</span> <span class="class">ShieldClient</span>(<span class="string">"{{ url('/') }}"</span>, config);
    }
}
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 3: Validando Licença</h5>
            <p class="mb-4">Chame o método <code>checkLicense(serial)</code> para obter o status completo em tempo real.
            </p>

            <div class="code-block">
                <pre>
<span class="keyword">String</span> serialUsuario = <span class="string">"AAAA-BBBB-CCCC-DDDD"</span>;
<span class="class">LicenseInfo</span> info = client.<span class="function">checkLicense</span>(serialUsuario);

<span class="keyword">if</span> (info.<span class="function">isValid</span>()) {
    <span class="class">System</span>.out.<span class="function">println</span>(<span class="string">"Licença Válida! Expira em: "</span> + info.<span class="function">getDataExpiracao</span>());
    
    <span class="comment">// Verificar Alertas de Vencimento</span>
    <span class="keyword">if</span> (info.<span class="function">shouldWarnExpiration</span>()) {
        <span class="class">System</span>.out.<span class="function">println</span>(<span class="string">"[AVISO] Faltam "</span> + info.<span class="function">getDiasRestantes</span>() + <span class="string">" dias para vencer!"</span>);
    }
} <span class="keyword">else</span> {
    <span class="class">System</span>.out.<span class="function">println</span>(<span class="string">"Licença Inválida: "</span> + info.<span class="function">getMensagem</span>());
    <span class="class">System</span>.exit(<span class="number">1</span>);
}
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 4: Renovação In-App (Checkout)</h5>
            <p class="mb-4">O SDK permite listar planos e gerar links de pagamento diretamente na aplicação:</p>

            <div class="code-block">
                <pre>
<span class="comment">// Listar Planos</span>
<span class="class">List</span>&lt;<span class="class">Plan</span>&gt; plans = client.<span class="function">getAvailablePlans</span>(info);
<span class="keyword">for</span> (<span class="class">Plan</span> p : plans) {
    <span class="class">System</span>.out.<span class="function">println</span>(p.<span class="function">getId</span>() + <span class="string">" - "</span> + p.<span class="function">getNome</span>() + <span class="string">" (R$ "</span> + p.<span class="function">getValor</span>() + <span class="string">")"</span>);
}

<span class="comment">// Gerar Link de Pagamento para o Plano ID 1</span>
<span class="class">String</span> link = client.<span class="function">createCheckoutUrl</span>(<span class="number">1</span>, serialUsuario);
<span class="keyword">if</span> (!link.<span class="function">isEmpty</span>()) {
    <span class="comment">// Abrir no navegador padrão</span>
    <span class="class">Desktop</span>.<span class="function">getDesktop</span>().<span class="function">browse</span>(<span class="keyword">new</span> <span class="class">URI</span>(link));
}
</pre>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Integração JavaFX (GUI)
            </x-slot>
            <p class="mb-4">O SDK inclui um pacote opcional <code>com.adassoft.shield.fx</code> contendo uma tela de
                registro completa e estilizada.</p>
            <p class="mb-4">Para usar, basta instanciar o <code>ShieldForm</code>:</p>
            <div class="code-block">
                <pre>
<span class="keyword">import</span> com.adassoft.shield.fx.ShieldForm;

<span class="comment">// Dentro da sua Application JavaFX:</span>
<span class="class">ShieldForm</span> form = <span class="keyword">new</span> <span class="class">ShieldForm</span>(client, <span class="string">"SERIAL-ATUAL"</span>);
form.<span class="function">showAndWait</span>();
</pre>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Nota sobre Produção
            </x-slot>
            <p>O pacote <code>utils/JsonHelper.java</code> incluído é um parser JSON minimalista baseado em Rexex.
                Para ambientes de produção robustos, recomendamos fortemente substituir esse helper por
                bibliotecas consagradas como <strong>Gson</strong> (Google) ou <strong>Jackson</strong>.</p>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Downloads
            </x-slot>
            <div class="flex flex-col items-center justify-center p-4">
                <x-filament::button tag="a" href="{{ url('downloads/sdk_java.zip') }}" color="warning" size="lg"
                    icon="heroicon-o-arrow-down-tray" class="mb-4 w-full md:w-auto">
                    Baixar SDK Java e Exemplos (.zip)
                </x-filament::button>
                <p class="text-sm text-gray-500 mb-6">Inclui projeto console e exemplo JavaFX.</p>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>