@extends('layouts.app')

@section('title', 'Documentação SDK Delphi')

@section('extra-css')
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
@endsection

@section('content')
    <div class="container-fluid pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                    <h1 class="h3 mb-0 text-gray-800">📘 Documentação SDK Delphi Profissional</h1>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Visão Geral</h6>
                    </div>
                    <div class="card-body">
                        <p>
                            O <strong>SDK para Delphi</strong> foi reescrito para fornecer uma arquitetura
                            modular, segura e profissional para o Adas Soft Painel.
                            Diferente de versões anteriores, este SDK separa a lógica de negócios da interface do usuário,
                            permitindo integração em segundos.
                        </p>
                        <hr>
                        <h5>📂 Estrutura do Projeto</h5>
                        <ul>
                            <li><code>Shield.Core.pas</code>: O "cérebro" do SDK. Gerencia estado, cache criptografado e
                                validação.</li>
                            <li><code>Shield.API.pas</code>: Camada de comunicação HTTP (Indy).</li>
                            <li><code>Shield.Security.pas</code>: Criptografia local (DPAPI) e Fingerprint de hardware.</li>
                            <li><code>Shield.Config.pas</code>: Configuração centralizada (URL, API Key, SoftwareID).</li>
                            <li><code>Shield.Types.pas</code>: Definição de tipos e records.</li>
                            <li><code>Views/uFrmRegistro.pas</code>: Formulário visual pronto para uso
                                (Login/Status/Renovação).</li>
                            <li><code>Views/uFrmAlert.pas</code>: Diálogo de alerta moderno e customizável.</li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Como Integrar em seu Projeto</h6>
                    </div>
                    <div class="card-body">

                        <h5 class="text-secondary">Passo 1: Instanciação</h5>
                        <p>No seu DataModule ou Unit principal, declare e inicialize o Shield:</p>

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
        <span class="string">'{{ url('/api_validacao.php') }}'</span>, <span class="comment">// URL Base (automática)</span>
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

                        <h5 class="text-secondary mt-4">Passo 2: Verificação de Licença</h5>
                        <p>Para proteger seu sistema, verifique a licença no início ou em pontos críticos:</p>

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

                        <h5 class="text-secondary mt-4">Passo 3: Exibindo Status e Alertas</h5>
                        <p>
                            O SDK persiste automaticamente os dados da licença localmente. Isso significa que
                            <code>MeuShield.License</code> estará preenchido logo ao iniciar o app, mesmo antes da
                            checagem online.
                        </p>
                        <p>Você pode usar isso para mostrar status ou chamar a tela de registro a qualquer momento (ex: menu
                            "Minha Licença"):</p>

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
    </pre>
                        </div>

                        <h5 class="text-secondary mt-4">Passo 4: Gerenciamento de Notícias e Comunicados</h5>
                        <p>
                            A versão mais recente do SDK inclui um sistema completo de <strong>Notícias e Avisos</strong>.
                            Você pode enviar mensagens para seus usuários através do painel, e elas serão entregues
                            diretamente no software.
                        </p>
                        <p>O `Shield.Core` popula automaticamente a lista <code>MeuShield.License.Noticias</code>. Veja como
                            implementar um painel moderno:</p>

                        <div class="code-block">
                            <pre>
    <span class="comment">// 1. Exibir Popup para Notícias de Alta Prioridade</span>
    <span class="keyword">procedure</span> <span class="function">TPrincipal.VerificarNoticiasPrioritarias</span>;
    <span class="keyword">var</span>
      I: Integer;
    <span class="keyword">begin</span>
      <span class="keyword">for</span> I := 0 <span class="keyword">to</span> <span class="function">High</span>(MeuShield.License.Noticias) <span class="keyword">do</span>
      <span class="keyword">begin</span>
         <span class="comment">// Verifica se é ALTA e NÃO LIDA</span>
         <span class="keyword">if</span> (<span class="function">LowerCase</span>(MeuShield.License.Noticias[I].Prioridade) = <span class="string">'alta'</span>) <span class="keyword">and</span> 
            (<span class="keyword">not</span> MeuShield.License.Noticias[I].Lida) <span class="keyword">then</span>
         <span class="keyword">begin</span>
             <span class="comment">// Exibe alerta customizado</span>
             <span class="class">TfrmAlert</span>.Execute(MeuShield.License.Noticias[I].Titulo + <span class="string">#13#10#13#10</span> +
                               MeuShield.License.Noticias[I].Conteudo);

             <span class="comment">// Marca como lida e salva no disco</span>
             MeuShield.License.Noticias[I].Lida := True;
             MeuShield.SaveCache; <span class="comment">// Persiste o status</span>
         <span class="keyword">end</span>;
      <span class="keyword">end</span>;
    <span class="keyword">end</span>;
    </pre>
                        </div>

                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Formulário de Registro Incluso</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p>O SDK inclui uma unit <code>uFrmRegistro</code> que implementa uma tela completa:</p>
                                <ul>
                                    <li>✨ Login (Email/Senha) integrado à API.</li>
                                    <li>✨ Fallback visual (Modo Login vs Modo Status).</li>
                                    <li>✨ Barra de progresso de dias restantes.</li>
                                    <li>✨ Barra de limite de terminais (Usado X de Y).</li>
                                    <li>✨ Botões de "Comprar", "Renovar" e "Desvincular Máquina" já linkados.</li>
                                </ul>
                                <p>Basta adicionar a unit ao projeto e chamar
                                    <code>TfrmRegistro.Exibir(MeuShield)</code>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger">Downloads</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <a href="{{ url('downloads/sdk_delphi.zip') }}" class="btn btn-danger btn-icon-split btn-lg">
                                <span class="icon text-white-50"><i class="fas fa-download"></i></span>
                                <span class="text">Baixar SDK Delphi e Exemplos (.zip)</span>
                            </a>
                            <p class="mt-2 text-muted small">Inclui units Shield.Core, Forms VCL e projeto demo.</p>

                            <hr style="width: 50%;">

                            <a href="{{ url('downloads/delphi_project.zip') }}"
                                class="btn btn-primary btn-icon-split btn-lg">
                                <span class="icon text-white-50"><i class="fas fa-project-diagram"></i></span>
                                <span class="text">Baixar Projeto de Exemplo Completo</span>
                            </a>
                            <p class="mt-2 text-muted small">Projeto VCL completo (uPrincipal, forms, configs) pronto para
                                compilar.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection