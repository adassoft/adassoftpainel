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
                Visão Geral (FMX)
            </x-slot>

            <p class="text-gray-500 dark:text-gray-400">
                O <strong>Shield SDK para FireMonkey (FMX)</strong> permite levar a proteção de licenciamento
                para dispositivos móveis (Android e iOS) e aplicações Desktop multiplataforma (Windows/macOS).
            </p>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                Ele compartilha o mesmo núcleo robusto da versão VCL, mas utiliza componentes visuais adaptados
                para o framework FMX, garantindo responsividade e suporte a touch.
            </p>

            <div class="mt-4">
                <h5 class="font-bold mb-2">📱 Plataformas Suportadas</h5>
                <ul class="list-disc pl-5 space-y-1 text-gray-700 dark:text-gray-400">
                    <li><strong>Android:</strong> 10+ (API Level 29+)</li>
                    <li><strong>iOS:</strong> 14+</li>
                    <li><strong>Windows:</strong> 10/11 (32 e 64 bits)</li>
                    <li><strong>macOS:</strong> Ventura+ (Intel e Apple Silicon)</li>
                </ul>
            </div>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Como Integrar
            </x-slot>

            <h5 class="text-xl font-bold mt-4 mb-2 text-primary-600">Passo 1: Instalação</h5>
            <p class="mb-4">Adicione o caminho <code>\Lib\FMX</code> ao Library Path do seu Delphi.</p>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Passo 2: Uso Básico</h5>
            <p class="mb-4">A inicialização é idêntica à versão VCL, mas utilizando as units FMX:</p>

            <div class="code-block">
                <pre>
<span class="keyword">uses</span>
  Shield.Core, Shield.Config,
  <span class="comment">// Use a unit correta para FMX</span>
  Shield.FMX.View.Login;

<span class="keyword">var</span>
  Shield: <span class="class">TShield</span>;
  Config: <span class="class">TShieldConfig</span>;

<span class="keyword">procedure</span> <span class="function">TFormMain.FormCreate</span>(Sender: <span class="class">TObject</span>);
<span class="keyword">begin</span>
  <span class="comment">// Configuração</span>
  Config := <span class="class">TShieldConfig</span>.Create(
    <span class="string">'{{ url('/api/v1/adassoft') }}'</span>,
    <span class="string">'API_KEY_APP'</span>,
    <span class="number">2</span>, <span class="comment">// ID do Software Mobile</span>
    <span class="string">'1.0.0'</span>
  );

  Shield := <span class="class">TShield</span>.Create(Config);

  <span class="comment">// Verificação</span>
  <span class="keyword">if not</span> Shield.CheckLicense <span class="keyword">then</span>
  <span class="keyword">begin</span>
    <span class="comment">// Exibe tela de registro responsiva FMX</span>
    <span class="class">TfrmShieldLoginFMX</span>.ShowModal(Shield,
      <span class="keyword">procedure</span>(AValid: <span class="keyword">Boolean</span>)
      <span class="keyword">begin</span>
        <span class="keyword">if not</span> AValid <span class="keyword">then</span>
        <span class="keyword">begin</span>
           <span class="comment">// Fecha app se não validar</span>
           CreateTimer(200, <span class="keyword">procedure</span> <span class="keyword">begin</span> Application.Terminate; <span class="keyword">end</span>);
        <span class="keyword">end</span>;
      <span class="keyword">end</span>
    );
  <span class="keyword">end</span>;
<span class="keyword">end</span>;
</pre>
            </div>

            <h5 class="text-xl font-bold mt-6 mb-2 text-primary-600">Permissões Android</h5>
            <p class="mb-4">No AndroidManifest.xml, certifique-se de habilitar:</p>
            <ul class="list-disc pl-5 text-gray-600">
                <li><code>android.permission.INTERNET</code> (para validar online)</li>
                <li><code>android.permission.ACCESS_WIFI_STATE</code> (para identificação única - opcional)</li>
            </ul>
        </x-filament::card>

        <x-filament::card>
            <x-slot name="heading">
                Downloads
            </x-slot>

            <div class="flex flex-col items-center justify-center p-4">
                <x-filament::button tag="a" href="{{ route('downloads.file', ['id' => 14]) }}" color="danger" size="lg"
                    icon="heroicon-o-device-phone-mobile" class="mb-4 w-full md:w-auto">
                    Baixar SDK FMX e Demos (.zip)
                </x-filament::button>
                <p class="text-sm text-gray-500 mb-6">Inclui exemplo para Android/iOS.</p>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>