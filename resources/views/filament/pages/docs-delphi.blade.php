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
    </style>

    <div class="space-y-6">
        <x-filament::card>
            <x-slot name="heading">
                Visão Geral
            </x-slot>

            <p class="text-gray-500 dark:text-gray-400">
                O <strong>Shield SDK para Delphi</strong> foi reescrito para fornecer uma arquitetura
                modular, segura e profissional para o painel.
                Diferente de versões anteriores, este SDK separa a lógica de negócios da interface do usuário,
                permitindo integração em segundos.
            </p>

            <div class="mt-4">
                <h5 class="font-bold mb-2">📂 Estrutura do Projeto</h5>
                <ul class="list-disc pl-5 space-y-1 text-gray-700 dark:text-gray-400">
                    <li><code>Shield.Core.pas</code>: O "cérebro" do SDK. Gerencia estado, cache criptografado e
                        validação.</li>
                    <li><code>Shield.API.pas</code>: Camada de comunicação HTTP (Indy).</li>
                    <li><code>Shield.Security.pas</code>: Criptografia local (DPAPI) e Fingerprint de hardware.</li>
                    <li><code>Shield.Config.pas</code>: Configuração centralizada (URL, API Key, SoftwareID).</li>
                    <li><code>Shield.Types.pas</code>: Definição de tipos e records.</li>
                    <li><code>Views/uFrmRegistro.pas</code>: Formulário visual pronto para uso (Login/Status/Renovação).
                    </li>
                    <li><code>Views/uFrmAlert.pas</code>: Diálogo de alerta moderno e customizável.</li>
                </ul>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Como Integrar em seu Projeto
            </x-slot>

            <h5 class="text-xl font-bold mt-4 mb-2 text-primary-600">Passo 1: Instanciação</h5>
            <p class="mb-4">No seu DataModule ou Unit principal, declare e inicialize o Shield:</p>

            <div class="code-block">
                <pre>
<span class="keyword">uses</span> Shield.Core, Shield.Config;

<span class="keyword">var</span>
  MeuShield: <span class="class">TShield</span>;
  Config: <span class="class">TShieldConfig</span>;

<span class="keyword">procedure</span> <span class="function">TPrincipal.FormCreate</span>(Sender: <span class="class">TObject</span>);
<span class="keyword">begin</span>
  <span class="comment">// Configuração (Pegue a API Key no painel)</span>
  Config := <span class="class">TShieldConfig</span>.Create(
    <span class="string">'{{ url('/api/v1/adassoft') }}'</span>, <span class="comment">// Endpoint API (Laravel)</span>
    <span class="string">'SUA_API_KEY_AQUI'</span>,                               <span class="comment">// API Key do Software</span>
    <span class="number">1</span>,                                                <span class="comment">// ID do Software</span>
    <span class="string">'1.0.0'</span>,                                          <span class="comment">// Versão</span>
    <span class="string">'SEGREDO_OFFLINE_AQUI'</span>                            <span class="comment">// Segredo validação offline</span>
  );

  <span class="comment">// Inicializa o Controller</span>
  MeuShield := <span class="class">TShield</span>.Create(Config);
<span class="keyword">end</span>;
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 2: Verificação de Licença</h5>
            <p class="mb-4">Para proteger seu sistema, verifique a licença no início ou em pontos críticos:</p>

            <div class="code-block">
                <pre>
<span class="keyword">if not</span> MeuShield.CheckLicense <span class="keyword">then</span>
<span class="keyword">begin</span>
  <span class="comment">// Se expirou ou não ativou, abre a tela de registro/ativação</span>
  <span class="class">TfrmRegistro</span>.Exibir(MeuShield);

  <span class="comment">// Verifica novamente se o usuário ativou na tela</span>
  <span class="keyword">if not</span> MeuShield.License.IsValid <span class="keyword">then</span>
  <span class="keyword">begin</span>
    <span class="function">ShowMessage</span>(<span class="string">'Licença necessária para continuar.'</span>);
    Application.Terminate;
  <span class="keyword">end</span>;
<span class="keyword">end</span>;
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 3: Exibindo Status e Alertas</h5>
            <p class="mb-4">
                O SDK persiste automaticamente os dados da licença localmente. Isso significa que
                <code>MeuShield.License</code> estará preenchido logo ao iniciar o app, mesmo antes da
                checagem online.
            </p>

            <div class="code-block">
                <pre>
<span class="comment">// Exemplo de botão "Minha Licença"</span>
<span class="keyword">procedure</span> <span class="function">TPrincipal.btnMinhaLicencaClick</span>(Sender: <span class="class">TObject</span>);
<span class="keyword">begin</span>
  <span class="comment">// Abre a tela visualizadora mesmo se estiver válido</span>
  <span class="class">TfrmRegistro</span>.Exibir(MeuShield);
<span class="keyword">end</span>;

<span class="comment">// Exemplo de Alerta Moderno na tela principal (FormShow)</span>
<span class="keyword">uses</span> ..., uFrmAlert, uFrmRenovacao;

<span class="keyword">procedure</span> <span class="function">TPrincipal.FormShow</span>(Sender: <span class="class">TObject</span>);
<span class="keyword">begin</span>
  <span class="keyword">if</span> MeuShield.License.IsValid <span class="keyword">then</span>
  <span class="keyword">begin</span>
      lblStatus.Caption := <span class="function">Format</span>(<span class="string">'Vence em %s (%d dias)'</span>, 
        [<span class="function">DateToStr</span>(MeuShield.License.DataExpiracao), MeuShield.License.DiasRestantes]);

      <span class="comment">// Novo: Se o servidor enviou alerta (ex: "ATENÇÃO: Vence Hoje")</span>
      <span class="comment">// Exibe diálogo moderno "uFrmAlert" que chama a renovação automaticamente</span>
      <span class="keyword">if</span> MeuShield.License.AvisoMensagem <> <span class="string">''</span> <span class="keyword">then</span>
      <span class="keyword">begin</span>
          <span class="keyword">if</span> <span class="class">TfrmAlert</span>.Execute(MeuShield.License.AvisoMensagem) <span class="keyword">then</span>
          <span class="keyword">begin</span>
              <span class="comment">// Se o usuário clicou em "Renovar Agora"</span>
              <span class="class">TfrmRenovacao</span>.Executar(MeuShield);
          <span class="keyword">end</span>;
      <span class="keyword">end</span>;
  <span class="keyword">end</span>;
<span class="keyword">end</span>;
<span class="keyword">end</span>;
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Dica: Ativação Offline Manual</h5>
            <p class="mb-4">
                O painel oferece duas formas de ativação offline:
            </p>
            <ul class="list-disc pl-5 mb-4 text-gray-600">
                <li><strong>Método Simples:</strong> Vá em <em>Gestão de Clientes > Licenças</em>, clique em "Ações >
                    Ver Token" e copie o token. O cliente apenas cola este token.</li>
                <li><strong>Método Seguro (Challenge):</strong> Vá em <em>Gestão de Clientes > Ativação Offline</em>.
                    Cole o "Challenge" gerado pelo software do cliente para receber um token de reposta exclusivo.</li>
            </ul>
            <p class="mb-4">
                Para testes rápidos, use o Método Simples com o código abaixo:
            </p>
            <div class="code-block">
                <pre>
<span class="comment">// Exemplo de botão "Ativar Offline"</span>
<span class="keyword">procedure</span> <span class="function">TFormLogin.btnOfflineClick</span>(Sender: <span class="class">TObject</span>);
<span class="keyword">var</span>
  Token: <span class="keyword">String</span>;
<span class="keyword">begin</span>
  Token := <span class="function">InputBox</span>(<span class="string">'Ativação Offline'</span>, <span class="string">'Cole o Token de Ativação:'</span>, <span class="string">''</span>);
  <span class="keyword">if</span> Token = <span class="string">''</span> <span class="keyword">then</span> <span class="keyword">Exit</span>;

  <span class="keyword">try</span>
    <span class="comment">// Valida a assinatura usando o OfflineSecret definido no Config</span>
    MeuShield.ActivateOffline(Token);

    <span class="keyword">if</span> MeuShield.License.IsValid <span class="keyword">then</span>
    <span class="function">ShowMessage</span>(<span class="string">'Ativado com Sucesso!'</span>)
    <span class="keyword">else</span>
    <span class="function">ShowMessage</span>(<span class="string">'Token inválido.'</span>);

  <span class="keyword">except</span>
    <span class="keyword">on</span> E: Exception <span class="keyword">do</span>
      <span class="function">ShowMessage</span>(<span class="string">'Erro: '</span> + E.Message);
  <span class="keyword">end</span>;
<span class="keyword">end</span>;
</pre>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Downloads
            </x-slot>

            <div class="flex flex-col items-center justify-center p-4">
                <x-filament::button tag="a" href="{{ route('downloads.file', ['id' => 10]) }}" color="danger" size="lg"
                    icon="heroicon-o-arrow-down-tray" class="mb-4 w-full md:w-auto">
                    Baixar SDK Delphi e Exemplos (.zip)
                </x-filament::button>
                <p class="text-sm text-gray-500 mb-6">Inclui units Shield.Core, Forms VCL e projeto demo.</p>

                <div class="w-1/2 border-t border-gray-200 my-4"></div>

                <x-filament::button tag="a" href="{{ route('downloads.file', ['id' => 11]) }}" color="primary" size="lg"
                    icon="heroicon-o-computer-desktop" class="mb-2 w-full md:w-auto">
                    Baixar Projeto de Exemplo Completo
                </x-filament::button>
                <p class="text-sm text-gray-500">Projeto VCL completo (uPrincipal, forms, configs) pronto para compilar.
                </p>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>