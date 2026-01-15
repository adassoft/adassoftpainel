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
    </style>

    <div class="space-y-6">
        <x-filament::card>
            <x-slot name="heading">
                Visão Geral
            </x-slot>
            <p class="text-gray-500 dark:text-gray-400">
                O <strong>SDK para Lazarus</strong> foi desenvolvido com foco total em portabilidade.
                Utilizando apenas bibliotecas nativas do Free Pascal (FPC) como <code>fpjson</code> e
                <code>fphttpclient</code>, ele garante que sua aplicação compile e rode em Windows, Linux e
                macOS sem dores de cabeça com dependências externas.
            </p>

            <div class="mt-4">
                <h5 class="font-bold mb-2">📂 Estrutura das Units</h5>
                <ul class="list-disc pl-5 space-y-1 text-gray-700 dark:text-gray-400">
                    <li><code>Shield.Core.pas</code>: Controlador principal.</li>
                    <li><code>Shield.Config.pas</code>: Configurações de API e Software ID.</li>
                    <li><code>Shield.API.pas</code>: Camada HTTP nativa (FCL-Web).</li>
                    <li><code>Shield.Security.pas</code>: Camada de segurança multiplataforma.</li>
                    <li><code>Shield.Types.pas</code>: Definições de tipos.</li>
                </ul>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Como Integrar
            </x-slot>

            <h5 class="text-xl font-bold mt-4 mb-2 text-primary-600">Passo 1: Instalação</h5>
            <p class="mb-4">Adicione a pasta do SDK ao <strong>Library Path</strong> do seu projeto Lazarus ou copie os
                arquivos <code>.pas</code> para junto do seu projeto.</p>
            <p class="mb-4">Dependências necessárias no projeto (Menu Project Inspector > Add > New Requirement):
                <strong>LCL</strong> (se for GUI) ou apenas o compilador base.
            </p>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 2: Uso Básico</h5>
            <p class="mb-4">Exemplo de console application:</p>

            <div class="code-block">
                <pre>
<span class="keyword">uses</span> Shield.Core, Shield.Config;

<span class="keyword">var</span>
  Config: <span class="keyword">TShieldConfig</span>;
  Shield: <span class="keyword">TShield</span>;
<span class="keyword">begin</span>
  <span class="comment">// 1. Configurar</span>
  Config := <span class="keyword">TShieldConfig</span>.Create(
    <span class="string">'{{ url('/') }}'</span>, <span class="comment">// URL Base</span>
    <span class="string">'API_KEY_AQUI'</span>,                    <span class="comment">// API Key</span>
    <span class="number">1</span>,                                 <span class="comment">// Software ID</span>
    <span class="string">'1.0.0'</span>                            <span class="comment">// Versão</span>
  );
  
  <span class="comment">// 2. Instanciar</span>
  Shield := <span class="keyword">TShield</span>.Create(Config);
  
  <span class="keyword">try</span>
    <span class="comment">// 3. Verificar</span>
    <span class="keyword">if</span> Shield.CheckLicense(<span class="string">'SERIAL-DO-USUARIO'</span>) <span class="keyword">then</span>
      WriteLn(<span class="string">'Licença Válida!'</span>)
    <span class="keyword">else</span>
      WriteLn(<span class="string">'Inválida: '</span> + Shield.License.Mensagem);
      
    <span class="comment">// 4. Verificar Alertas (Novo)</span>
    <span class="keyword">if</span> Shield.License.ShouldWarnExpiration <span class="keyword">then</span>
      WriteLn(<span class="string">'[ALERTA] Vence em breve!'</span>);
      
  <span class="keyword">finally</span>
    Shield.Free;
    Config.Free;
  <span class="keyword">end</span>;
<span class="keyword">end</span>.
</pre>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Downloads
            </x-slot>
            <div class="flex flex-col items-center justify-center p-4">
                <x-filament::button tag="a" href="{{ url('downloads/sdk_lazarus.zip') }}" color="info" size="lg"
                    icon="heroicon-o-arrow-down-tray" class="mb-4 w-full md:w-auto">
                    Baixar SDK Lazarus e Exemplos (.zip)
                </x-filament::button>
                <p class="text-sm text-gray-500 mb-6">Inclui units Shield.Core e exemplo Console nativo.</p>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>