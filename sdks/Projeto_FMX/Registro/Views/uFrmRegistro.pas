unit uFrmRegistro;

interface

uses
  System.SysUtils, System.Types, System.UITypes, System.Classes, System.Variants,
  FMX.Types, FMX.Controls, FMX.Forms, FMX.Graphics, FMX.Dialogs, FMX.StdCtrls,
  FMX.Controls.Presentation, FMX.Edit, FMX.Layouts, FMX.Objects,
  Shield.Core, Shield.Types;

type
  TfrmRegistro = class(TForm)
    lytHeader: TLayout;
    lblTitle: TLabel;
    lblInstalacaoID: TLabel;
    LineHeader: TLine;
    lytFooter: TLayout;
    btnFechar: TButton;
    btnComprar: TButton;
    btnDesvincular: TButton;
    LineFooter: TLine;
    lytSupport: TLayout;
    lblSuporteZap: TLabel;
    lblSuporteEmail: TLabel;
    lytContent: TLayout;
    pnlLogin: TLayout;
    gbLogin: TGroupBox;
    LabelMsg: TLabel;
    Label2: TLabel;
    edtEmail: TEdit;
    Label3: TLabel;
    edtSenha: TEdit;
    btnAtivar: TButton;
    lblEsqueciSenha: TLabel;
    gbCadastro: TGroupBox;
    LabelInfo: TLabel;
    LabelInfo2: TLabel;
    btnCriarConta: TButton;
    pnlStatus: TLayout;
    lblStatusTexto: TLabel;
    lblDiasRestantes: TLabel;
    pbTempo: TProgressBar;
    lblDataInicio: TLabel;
    lblDataFim: TLabel;
    lblInfoTerminais: TLabel;
    pbTerminais: TProgressBar;
    procedure btnFecharClick(Sender: TObject);
    procedure btnAtivarClick(Sender: TObject);
    procedure btnDesvincularClick(Sender: TObject);
    procedure lblEsqueciSenhaClick(Sender: TObject);
    procedure lblCriarContaClick(Sender: TObject);
    procedure btnComprarClick(Sender: TObject);
    procedure EditKeyDown(Sender: TObject; var Key: Word; var KeyChar: Char; Shift: TShiftState);
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

{$R *.fmx}

uses
  System.DateUtils, uFrmCadastro;

procedure OpenURL(const URL: string);
begin
  ShowMessage('Link: ' + URL);
end;

class procedure TfrmRegistro.Exibir(AShield: TShield);
var
  Form: TfrmRegistro;
begin
  Form := TfrmRegistro.Create(nil);
  try
    Form.FShield := AShield;
    Form.FShield.CheckLicense;
    Form.AtualizarUI;
    Form.ShowModal;
  finally
    Form.Free;
  end;
end;

procedure TfrmRegistro.AtualizarUI;
var
  Info: TLicenseInfo;
  Hoje: TDateTime;
begin
  if FShield = nil then Exit;

  lblInstalacaoID.Text := '{' + FShield.GetMachineFingerprint + '}';
  Info := FShield.License;
  
  if Info.IsValid or (Info.Serial <> '') then
  begin
    pnlLogin.Visible := False;
    pnlStatus.Visible := True;
    
    if Info.IsValid then
    begin
      lblStatusTexto.Text := 'STATUS: LICENÇA ATIVA';
      SetStatusColor(True);
    end
    else
    begin
      lblStatusTexto.Text := 'STATUS: EXPIRADO / INVÁLIDO (' + Info.Mensagem + ')';
      SetStatusColor(False);
      if Info.Serial <> '' then btnDesvincular.Visible := True;
    end;
    
    Hoje := Now;
    if Info.DataExpiracao > 0 then
        lblDataFim.Text := DateToStr(Info.DataExpiracao)
    else
        lblDataFim.Text := '-';
    
    if Info.AvisoMensagem <> '' then
    begin
      lblDiasRestantes.Text := Info.AvisoMensagem;
      lblDiasRestantes.TextSettings.FontColor := TAlphaColors.Red;
    end
    else
    begin
      lblDiasRestantes.Text := Format('%d dias restantes.', [Info.DiasRestantes]);
      lblDiasRestantes.TextSettings.FontColor := TAlphaColors.Blue;
    end;
    
    if (Info.DataExpiracao > 0) and (Info.DataInicio > 0) then
      pbTempo.Max := Trunc(Info.DataExpiracao) - Trunc(Info.DataInicio)
    else
      pbTempo.Max := 30;
      
    if pbTempo.Max <= 0 then pbTempo.Max := 1;
    pbTempo.Value := pbTempo.Max - Info.DiasRestantes;
    if pbTempo.Value < 0 then pbTempo.Value := 0;
    
    if Info.DataInicio > 0 then
       lblDataInicio.Text := 'Início: ' + DateToStr(Info.DataInicio)
    else
       lblDataInicio.Text := 'Hoje: ' + DateToStr(Hoje);

    if Info.TerminaisPermitidos > 0 then
    begin
      pbTerminais.Max := Info.TerminaisPermitidos;
      pbTerminais.Value := Info.TerminaisUtilizados;
      lblInfoTerminais.Text := Format('Terminais: %d / %d',
        [Info.TerminaisUtilizados, Info.TerminaisPermitidos]);
    end
    else
    begin
      lblInfoTerminais.Text := 'Licença ilimitada.';
      pbTerminais.Value := 0;
    end;
    
    btnDesvincular.Visible := True;
  end
  else
  begin
    pnlLogin.Visible := True;
    pnlStatus.Visible := False;
    lblStatusTexto.Text := 'Status: NOVA ATIVAÇÃO';
    SetStatusColor(False);
    btnDesvincular.Visible := False;
  end;
end;

procedure TfrmRegistro.SetStatusColor(const IsValid: Boolean);
begin
  if IsValid then
    lblStatusTexto.TextSettings.FontColor := TAlphaColors.Green
  else
    lblStatusTexto.TextSettings.FontColor := TAlphaColors.Red;
end;

procedure TfrmRegistro.btnAtivarClick(Sender: TObject);
begin
  if (Self.edtEmail = nil) or (Self.edtSenha = nil) then Exit;

  if (Trim(Self.edtEmail.Text) = '') or (Trim(Self.edtSenha.Text) = '') then
  begin
    ShowMessage('Informe e-mail e senha.');
    Exit;
  end;

  try
    try
      if FShield.Authenticate(Self.edtEmail.Text, Self.edtSenha.Text, '') then
      begin
        ShowMessage('Ativado com sucesso!');
        FShield.CheckLicense;
        AtualizarUI;
      end
      else
      begin
        ShowMessage('Ativação falhou. Verifique suas credenciais.');
      end;
    except
      on E: Exception do
        ShowMessage('Erro ao ativar: ' + E.Message);
    end;
  finally
  end;
end;

procedure TfrmRegistro.EditKeyDown(Sender: TObject; var Key: Word; var KeyChar: Char; Shift: TShiftState);
begin
  if Key = vkReturn then
  begin
    Key := 0;
    if Sender = edtEmail then
      edtSenha.SetFocus
    else if Sender = edtSenha then
    begin
       btnAtivar.SetFocus; 
       btnAtivarClick(nil);
    end;
  end;
end;

procedure TfrmRegistro.btnDesvincularClick(Sender: TObject);
begin
  if MessageDlg('Deseja realmente desvincular esta máquina?',
    TMsgDlgType.mtConfirmation, [TMsgDlgBtn.mbYes, TMsgDlgBtn.mbNo], 0) = mrYes then
  begin
    FShield.Logout;
    if Self.edtSenha <> nil then
        Self.edtSenha.Text := ''; 
    AtualizarUI;
  end;
end;

procedure TfrmRegistro.btnFecharClick(Sender: TObject);
begin
  Close;
end;

procedure TfrmRegistro.btnComprarClick(Sender: TObject);
begin
  ShowMessage('Implementar Compra');
end;

procedure TfrmRegistro.lblEsqueciSenhaClick(Sender: TObject);
begin
  OpenURL('https://express.adassoft.com/forgot-password.html');
end;

procedure TfrmRegistro.lblCriarContaClick(Sender: TObject);
begin
  TfrmCadastro.Executar(FShield);
  
  if FShield.IsInitialized then
  begin
     FShield.CheckLicense;
     AtualizarUI;
  end;
end;

end.
