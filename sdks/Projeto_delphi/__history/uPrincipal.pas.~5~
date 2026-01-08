unit uPrincipal;

interface

uses uFrmAlert, uFrmRenovacao, Vcl.Dialogs, Winapi.Windows, Winapi.Messages, 
     System.SysUtils, System.Variants, System.Classes, Vcl.Graphics, Vcl.Controls, 
     Vcl.Forms, Vcl.ExtCtrls, Vcl.StdCtrls, Vcl.Buttons, ShellApi, System.UITypes;

type
  TForm1 = class(TForm)
    Timer1: TTimer;
    lblStatus: TLabel;
    Button1: TButton;
    Timer2: TTimer;

    procedure FormCreate(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
    procedure FormShow(Sender: TObject);
    procedure Button1Click(Sender: TObject);
    procedure Timer2Timer(Sender: TObject);
    procedure btnOpenNewsClick(Sender: TObject);
    procedure btnCloseNewsClick(Sender: TObject);
    procedure tmrAnimacaoTimer(Sender: TObject);
    procedure FormResize(Sender: TObject);
  private
    { Private declarations }
    // Componentes do Painel de Noticias (Runtime)
    pnlNewsContainer: TPanel;
    sbNews: TScrollBox;
    pnlHeaderNews: TPanel;
    btnCloseNews: TSpeedButton;
    lblTitleNews: TLabel;
    tmrAnimacao: TTimer;
    btnOpenNews: TSpeedButton;
    
    FNewsExpanded: Boolean;
    FNewsTargetWidth: Integer;
    procedure VerificarNoticiasPrioritarias;
    procedure RenderizarNoticias;
    procedure ToggleNewsPanel(Show: Boolean);
    procedure OnClickLerNoticia(Sender: TObject);
    procedure SalvarStatusLida(Id: Integer);
  public
    { Public declarations }
  end;

var
  Form1: TForm1;

implementation

{$R *.dfm}

uses Shield.Core, Shield.Config, uFrmRegistro, Shield.Types;

var
  MeuShield: TShield;
  Config: TShieldConfig;

procedure TForm1.Button1Click(Sender: TObject);
begin
  TfrmRegistro.Exibir(MeuShield);
end;

procedure TForm1.FormCreate(Sender: TObject);
begin
  // Configuracao (Pegue a API Key no painel Shield)
  Config := TShieldConfig.Create(
    'https://express.adassoft.com/api/v1/adassoft', // URL Base (Novo Padrão REST)
    'd8ae56db' + '7cabcc42' + '9d6f418e' + 'f8ed47d4' + '8e06e1a9' + '3b75062e' + '4f79dd37' + '0d60d2e0',  // API Key Ofuscada
    3,                                                  // ID do Software (Teste Dev)
    '3.10.14',                                          // Versao
    '3547f5d0' + '0316ab9e' + 'd1b967bc' + '36685622' + '8bc32194' + 'ad8df4df' + 'e663844a' + '3a50e107' // Segredo validacao offline (Ofuscado)
  );

  MeuShield := TShield.Create(Config);

  // --- Construcao da UI Moderna Backend-Code ---
  
  // 1. Botao para Abrir Painel (Fica no canto direito)
  btnOpenNews := TSpeedButton.Create(Self);
  btnOpenNews.Parent := Self;
  btnOpenNews.Top := 10;
  btnOpenNews.Width := 40;
  btnOpenNews.Height := 40;
  btnOpenNews.Left := Self.ClientWidth - 50; // Inicial
  btnOpenNews.Anchors := [akTop, akRight];
  btnOpenNews.Caption := '🔔'; // Icone de sino
  btnOpenNews.Font.Size := 14;
  btnOpenNews.Flat := True;
  btnOpenNews.OnClick := btnOpenNewsClick;
  
  // 2. Painel Lateral Container
  pnlNewsContainer := TPanel.Create(Self);
  pnlNewsContainer.Parent := Self;
  pnlNewsContainer.Align := alRight;
  pnlNewsContainer.Width := 0; // Começa fechado
  pnlNewsContainer.BevelOuter := bvNone;
  pnlNewsContainer.Color := $00F9F9F9; // Cinza bem claro
  pnlNewsContainer.ParentBackground := False;
  
  // 3. Header do Painel
  pnlHeaderNews := TPanel.Create(pnlNewsContainer);
  pnlHeaderNews.Parent := pnlNewsContainer;
  pnlHeaderNews.Align := alTop;
  pnlHeaderNews.Height := 50;
  pnlHeaderNews.Color := clWhite;
  pnlHeaderNews.BevelOuter := bvNone;
  pnlHeaderNews.ParentBackground := False;
  
  lblTitleNews := TLabel.Create(pnlHeaderNews);
  lblTitleNews.Parent := pnlHeaderNews;
  lblTitleNews.Caption := 'Notícias e Avisos';
  lblTitleNews.Font.Size := 12;
  lblTitleNews.Font.Style := [fsBold];
  lblTitleNews.Layout := tlCenter;
  lblTitleNews.Left := 15;
  lblTitleNews.Top := 15;
  
  btnCloseNews := TSpeedButton.Create(pnlHeaderNews);
  btnCloseNews.Parent := pnlHeaderNews;
  btnCloseNews.Align := alRight;
  btnCloseNews.Width := 50;
  btnCloseNews.Caption := '✕';
  btnCloseNews.Flat := True;
  btnCloseNews.OnClick := btnCloseNewsClick;
  
  // 4. ScrollBox para Cards
  sbNews := TScrollBox.Create(pnlNewsContainer);
  sbNews.Parent := pnlNewsContainer;
  sbNews.Align := alClient;
  sbNews.BorderStyle := bsNone;
  
  // 5. Timer de Animacao
  tmrAnimacao := TTimer.Create(Self);
  tmrAnimacao.Interval := 15;
  tmrAnimacao.Enabled := False;
  tmrAnimacao.OnTimer := tmrAnimacaoTimer;
end;

procedure TForm1.FormResize(Sender: TObject);
begin
  // Mantem botao alinhado se necessario (Anchors ja resolve a maioria)
end;

procedure TForm1.FormShow(Sender: TObject);
begin
  Timer1.Enabled := True;
end;

procedure TForm1.Timer1Timer(Sender: TObject);
begin
  Timer1.Enabled := False;
  if not MeuShield.CheckLicense then
  begin
    TfrmRegistro.Exibir(MeuShield);
    if not MeuShield.License.IsValid then
    begin
      ShowMessage('Licenca necessaria para continuar.');
      Application.Terminate;
    end;
  end;
  Timer2.Enabled := True;
end;

procedure TForm1.VerificarNoticiasPrioritarias;
var
  I: Integer;
begin
  // Apenas exibe popup se for ALTA prioridade e NAO LIDA
  for I := 0 to High(MeuShield.License.Noticias) do
  begin
     if (LowerCase(MeuShield.License.Noticias[I].Prioridade) = 'alta') and 
        (not MeuShield.License.Noticias[I].Lida) then
     begin
       TfrmAlert.Execute(MeuShield.License.Noticias[I].Titulo + sLineBreak + sLineBreak +
                         MeuShield.License.Noticias[I].Conteudo);
       
       // Marca como lida
       SalvarStatusLida(MeuShield.License.Noticias[I].Id);
     end;
  end;
end;

procedure TForm1.RenderizarNoticias;
var
  I: Integer;
  pnlCard: TPanel;
  lblTit, lblMsg: TLabel;
  btnLer: TSpeedButton;
begin
  // Limpa anteriores
  while sbNews.ControlCount > 0 do
    sbNews.Controls[0].Free;

  if Length(MeuShield.License.Noticias) = 0 then
  begin
    lblTit := TLabel.Create(sbNews);
    lblTit.Parent := sbNews;
    lblTit.Caption := 'Nenhuma notícia nova.';
    lblTit.Align := alTop;
    lblTit.Alignment := taCenter;
    lblTit.Layout := tlCenter;
    lblTit.Height := 40;
    Exit;
  end;

  // Cria cards de baixo para cima (para os mais novos ficarem no topo se array estiver ordenado DESC)
  for I := High(MeuShield.License.Noticias) downto 0 do
  begin
    pnlCard := TPanel.Create(sbNews);
    pnlCard.Parent := sbNews;
    pnlCard.Align := alTop;
    pnlCard.Height := 130; // Altura base
    pnlCard.BevelOuter := bvNone;
    pnlCard.BorderWidth := 5;
    pnlCard.Color := clWhite;
    pnlCard.ParentBackground := False;
    pnlCard.Margins.Bottom := 10;
    pnlCard.AlignWithMargins := True;
    
    // Status visual de lida/nao lida
    if not MeuShield.License.Noticias[I].Lida then
    begin
       pnlCard.Color := $00FFF8E1; // Amarelo claro para nao lidas
    end;

    // Titulo
    lblTit := TLabel.Create(pnlCard);
    lblTit.Parent := pnlCard;
    lblTit.Caption := MeuShield.License.Noticias[I].Titulo;
    lblTit.Font.Style := [fsBold];
    lblTit.Font.Size := 10;
    lblTit.Align := alTop;
    lblTit.WordWrap := True;
    
    // Resumo/Conteudo
    lblMsg := TLabel.Create(pnlCard);
    lblMsg.Parent := pnlCard;
    lblMsg.Caption := Copy(MeuShield.License.Noticias[I].Conteudo, 1, 150) + '...';
    lblMsg.Align := alClient;
    lblMsg.WordWrap := True;
    lblMsg.Font.Color := clGray;
    
    // Botao de Acao (Marcar como lida / Abrir)
    btnLer := TSpeedButton.Create(pnlCard);
    btnLer.Parent := pnlCard;
    btnLer.Align := alBottom;
    btnLer.Height := 25;
    btnLer.Flat := True;
    btnLer.Tag := I; // Guarda o indice do array
    btnLer.OnClick := OnClickLerNoticia;
    
    if MeuShield.License.Noticias[I].Lida then
    begin
       btnLer.Caption := 'Visualizar / Abrir Link';
       btnLer.Font.Color := clBlue;
    end
    else
    begin
       btnLer.Caption := 'Marcar como Lida';
       btnLer.Font.Color := clRed;
    end;
  end;
end;

procedure TForm1.SalvarStatusLida(Id: Integer);
var 
  I: Integer;
begin
  for I := 0 to High(MeuShield.License.Noticias) do
  begin
    if MeuShield.License.Noticias[I].Id = Id then
    begin
      MeuShield.License.Noticias[I].Lida := True;
      Break;
    end;
  end;
  // Salva no disco imediatamente
  MeuShield.SaveCache;
end;

procedure TForm1.OnClickLerNoticia(Sender: TObject);
var
  Idx: Integer;
  Link: string;
begin
  Idx := (Sender as TControl).Tag;
  
  // Marca como lida visualmente e na logica
  SalvarStatusLida(MeuShield.License.Noticias[Idx].Id);
  (Sender as TSpeedButton).Caption := 'Visualizar / Abrir Link';
  (Sender as TSpeedButton).Font.Color := clBlue;
  ((Sender as TSpeedButton).Parent as TPanel).Color := clWhite;
  
  Link := MeuShield.License.Noticias[Idx].Link;
  
  if Link <> '' then
  begin
    ShellExecute(0, 'open', PChar(Link), nil, nil, SW_SHOWNORMAL);
  end
  else
  begin
    ShowMessage(MeuShield.License.Noticias[Idx].Conteudo);
  end;
end;

procedure TForm1.Timer2Timer(Sender: TObject);
begin
  Timer2.Enabled := False;

  if MeuShield.License.AvisoMensagem <> '' then
  begin
      lblStatus.Caption := MeuShield.License.AvisoMensagem;
      lblStatus.Font.Color := clRed;
      
      // Alerta critico com opcao de renovar
      if TfrmAlert.Execute(MeuShield.License.AvisoMensagem) then
      begin
           TfrmRenovacao.Executar(MeuShield);
      end;
  end
  else
  begin
      lblStatus.Caption := Format('Válido até %s', [DateToStr(MeuShield.License.DataExpiracao)]);
  end;
  
  // 1. Verifica Popups Prioritarios
  VerificarNoticiasPrioritarias;
  
  // 2. Renderiza lista lateral
  RenderizarNoticias;
  
  // 3. Verifica se tem noticias nao lidas para decidir se abre o painel automaticamente
  var TemNaoLida := False;
  for var I := 0 to High(MeuShield.License.Noticias) do
    if not MeuShield.License.Noticias[I].Lida then TemNaoLida := True;
    
  if TemNaoLida then
    ToggleNewsPanel(True); // Abre
end;

procedure TForm1.btnOpenNewsClick(Sender: TObject);
begin
  ToggleNewsPanel(True);
end;

procedure TForm1.btnCloseNewsClick(Sender: TObject);
begin
  ToggleNewsPanel(False);
end;

procedure TForm1.ToggleNewsPanel(Show: Boolean);
begin
  FNewsExpanded := Show;
  if Show then
  begin
    FNewsTargetWidth := 300;
    pnlNewsContainer.Visible := True;
    btnOpenNews.Visible := False; // Esconde o botao de abrir
  end
  else
  begin
    FNewsTargetWidth := 0;
    btnOpenNews.Visible := True; // Mostra o botao de abrir
  end;
  tmrAnimacao.Enabled := True;
end;

procedure TForm1.tmrAnimacaoTimer(Sender: TObject);
var
  Step: Integer;
begin
  Step := 20; // Velocidade
  
  if FNewsExpanded then
  begin
    if pnlNewsContainer.Width < FNewsTargetWidth then
      pnlNewsContainer.Width := pnlNewsContainer.Width + Step
    else
    begin
      pnlNewsContainer.Width := FNewsTargetWidth;
      tmrAnimacao.Enabled := False;
    end;
  end
  else
  begin
    if pnlNewsContainer.Width > 0 then
      pnlNewsContainer.Width := pnlNewsContainer.Width - Step
    else
    begin
      pnlNewsContainer.Width := 0;
      pnlNewsContainer.Visible := False;
      tmrAnimacao.Enabled := False;
    end;
  end;
end;

end.
