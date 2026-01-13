unit uFrmRegistro;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.StdCtrls, Vcl.ExtCtrls, Vcl.ComCtrls, System.UITypes, Vcl.Clipbrd,
  Shield.Core, Shield.Types;

type
  TfrmRegistro = class(TForm)
    pnlHeader: TPanel;
    lblInstalacaoID: TLabel;
    Label1: TLabel;
    pnlLogin: TPanel;
    GroupBox1: TGroupBox;
    Label2: TLabel;
    Label3: TLabel;
    edtEmail: TEdit;
    edtSenha: TEdit;
    btnAtivar: TButton;
    lblEsqueciSenha: TLabel;
    pnlStatus: TPanel;
    lblStatusTexto: TLabel;
    pnlStatusColor: TPanel;
    lblDiasRestantes: TLabel;
    pbTempo: TProgressBar;
    lblDataInicio: TLabel;
    lblDataFim: TLabel;
    lblInfoTerminais: TLabel;
    pbTerminais: TProgressBar;
    pnlFooter: TPanel;
    btnFechar: TButton;
    btnComprar: TButton;
    btnDesvincular: TButton;
    Label4: TLabel;
    lblSuporteZap: TLabel;
    lblSuporteEmail: TLabel;
    Bevel1: TBevel;
    GroupBoxCadastro: TGroupBox;
    LabelInfo: TLabel;
    LabelInfo2: TLabel;
    btnCriarConta: TButton;
    procedure FormCreate(Sender: TObject);
    procedure btnFecharClick(Sender: TObject);
    procedure btnAtivarClick(Sender: TObject);
    procedure btnDesvincularClick(Sender: TObject);
    procedure lblEsqueciSenhaClick(Sender: TObject);

    procedure lblCriarContaClick(Sender: TObject);
    procedure btnComprarClick(Sender: TObject);
    procedure lblInstalacaoIDClick(Sender: TObject);
  private
    FShield: TShield;
    procedure AtualizarUI;
    procedure SetStatusColor(const IsValid: Boolean);
  public
    class procedure Exibir(AShield: TShield);
  end;

var
  frmRegistro: TfrmRegistro;

implementation

{$R *.dfm}

uses
  ShellAPI, uFrmRenovacao, uFrmCadastro;

class procedure TfrmRegistro.Exibir(AShield: TShield);
var
  Form: TfrmRegistro;
begin
  Form := TfrmRegistro.Create(nil);
  try
    Form.FShield := AShield;
    // Tenta validar usando o cache carregado (Token/Serial) ao abrir a tela
    Form.FShield.CheckLicense;
    Form.AtualizarUI;
    Form.ShowModal;
  finally
    Form.Free;
  end;
end;

procedure TfrmRegistro.FormCreate(Sender: TObject);
begin
  lblEsqueciSenha.Cursor := crHandPoint;
  lblEsqueciSenha.Font.Style := [fsUnderline];
  lblEsqueciSenha.Font.Color := clBlue;
  
  lblInstalacaoID.Cursor := crHandPoint;
  lblInstalacaoID.ShowHint := True;
  lblInstalacaoID.Hint := 'Clique para copiar o código';
  lblInstalacaoID.OnClick := lblInstalacaoIDClick;
end;

procedure TfrmRegistro.AtualizarUI;
var
  Info: TLicenseInfo;
  Hoje: TDateTime;
begin
  lblInstalacaoID.Caption := 'Código da Instalação: ' + FShield.GetMachineFingerprint;
  Info := FShield.License;
  
  if Info.IsValid or (Info.Serial <> '') then
  begin
    pnlLogin.Visible := False;
    pnlStatus.Visible := True;
    
    if Info.IsValid then
    begin
      lblStatusTexto.Caption := 'Status: ATIVO';
      SetStatusColor(True);
    end
    else
    begin
      lblStatusTexto.Caption := 'Status: EXPIRADO / INVÁLIDO (' + Info.Mensagem + ')';
      SetStatusColor(False);
    end;
    
    Hoje := Now;
    lblDataFim.Caption := DateToStr(Info.DataExpiracao);
    
    if Info.AvisoMensagem <> '' then
    begin
      lblDiasRestantes.Caption := Info.AvisoMensagem;
      lblDiasRestantes.Font.Color := clRed;
      lblDiasRestantes.Font.Style := [fsBold];
    end
    else
    begin
      lblDiasRestantes.Caption := Format('%d dias restantes.', [Info.DiasRestantes]);
      lblDiasRestantes.Font.Color := clBlue; // Retorna cor padrão (assumindo azul pelo screenshot original "0 dias restantes" era azul escuro)
      lblDiasRestantes.Font.Style := [fsBold];
    end;
    
    if (Info.DataExpiracao > 0) and (Info.DataInicio > 0) then
      pbTempo.Max := Trunc(Info.DataExpiracao) - Trunc(Info.DataInicio)
    else
      pbTempo.Max := 30; // Fallback visual
      
    if pbTempo.Max <= 0 then pbTempo.Max := 1;

    // Barra de Progresso como "Tempo Decorrido" (Vazia no inicio, Cheia no fim)
    pbTempo.Position := pbTempo.Max - Info.DiasRestantes;
    
    if pbTempo.Position < 0 then pbTempo.Position := 0;
    
    if Info.DataInicio > 0 then
       lblDataInicio.Caption := 'Início: ' + DateToStr(Info.DataInicio)
    else
       lblDataInicio.Caption := 'Hoje: ' + DateToStr(Hoje);

    if Info.TerminaisPermitidos > 0 then
    begin
      pbTerminais.Max := Info.TerminaisPermitidos;
      pbTerminais.Position := Info.TerminaisUtilizados;
      lblInfoTerminais.Caption := Format('Você anexou %d máquinas de %d disponíveis',
        [Info.TerminaisUtilizados, Info.TerminaisPermitidos]);
    end
    else
    begin
      lblInfoTerminais.Caption := 'Licença ilimitada ou não verificada.';
      pbTerminais.Position := 0;
    end;
    
    btnDesvincular.Visible := True;
    
    btnAtivar.Default := False;
  end
  else
  begin
    pnlLogin.Visible := True;
    pnlStatus.Visible := False;
    
    lblStatusTexto.Caption := 'Status: NOVA ATIVAÇÃO';
    SetStatusColor(False);
    
    btnDesvincular.Visible := False;
    btnAtivar.Default := True;
  end;
end;

procedure TfrmRegistro.SetStatusColor(const IsValid: Boolean);
begin
  if IsValid then
  begin
    lblStatusTexto.Font.Color := clGreen;
    pnlStatusColor.Color := clLime;
  end
  else
  begin
    lblStatusTexto.Font.Color := clRed;
    pnlStatusColor.Color := clRed;
  end;
end;

procedure TfrmRegistro.btnAtivarClick(Sender: TObject);
begin
  if (Trim(edtEmail.Text) = '') or (Trim(edtSenha.Text) = '') then
  begin
    ShowMessage('Informe e-mail e senha.');
    Exit;
  end;

  Screen.Cursor := crHourGlass;
  try
    try
      if FShield.Authenticate(edtEmail.Text, edtSenha.Text, '') then
      begin
        ShowMessage('Ativado com sucesso!');
        FShield.CheckLicense;
        AtualizarUI;
      end;
    except
      on E: Exception do
        ShowMessage('Erro ao ativar: ' + E.Message);
    end;
  finally
    Screen.Cursor := crDefault;
  end;
end;

procedure TfrmRegistro.btnDesvincularClick(Sender: TObject);
begin
  if MessageDlg('Deseja realmente desvincular esta máquina? Voce precisará da senha para ativar novamente.',
    mtConfirmation, [mbYes, mbNo], 0) = mrYes then
  begin
    FShield.Logout;
    edtSenha.Text := ''; 
    AtualizarUI;
  end;
end;

procedure TfrmRegistro.btnFecharClick(Sender: TObject);
begin
  Close;
end;

procedure TfrmRegistro.btnComprarClick(Sender: TObject);
begin
  TfrmRenovacao.Executar(FShield);
end;

procedure TfrmRegistro.lblEsqueciSenhaClick(Sender: TObject);
begin
  ShellExecute(0, 'open', 'https://express.adassoft.com/forgot-password.html', nil, nil, SW_SHOWNORMAL);
end;

procedure TfrmRegistro.lblCriarContaClick(Sender: TObject);
begin
  if TfrmCadastro.Executar(FShield) then
  begin
    FShield.CheckLicense;
    AtualizarUI;
  end;
end;

procedure TfrmRegistro.lblInstalacaoIDClick(Sender: TObject);
var
  Code: string;
begin
  Code := FShield.GetMachineFingerprint;
  Clipboard.AsText := Code;
  ShowMessage('Código de Instalação copiado para a área de transferência!' + #13#10 + Code);
end;

end.
