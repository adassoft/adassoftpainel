unit uFrmCadastro;

interface

uses
  System.SysUtils, System.Types, System.UITypes, System.Classes, System.Variants,
  FMX.Types, FMX.Controls, FMX.Forms, FMX.Graphics, FMX.Dialogs, FMX.StdCtrls,
  FMX.Controls.Presentation, FMX.Edit, FMX.Layouts, FMX.Objects,
  Shield.Core;

type
  TEstadoCadastro = (estSolicitando, estConfirmando);

  TfrmCadastro = class(TForm)
    lytHeader: TLayout;
    bgHeader: TRectangle;
    lblTitle: TLabel;
    lytFooter: TLayout;
    LineFooter: TLine;
    btnCancelar: TButton;
    btnReenviar: TButton;
    btnAcao: TButton;
    ScrollBox: TVertScrollBox;
    lytContent: TLayout;
    lbNome: TLabel;
    edtNome: TEdit;
    lbEmail: TLabel;
    edtEmail: TEdit;
    lbSenha: TLabel;
    edtSenha: TEdit;
    lbCNPJ: TLabel;
    edtCNPJ: TEdit;
    lbRazao: TLabel;
    edtRazao: TEdit;
    lbZap: TLabel;
    edtWhatsapp: TEdit;
    lbParceiro: TLabel;
    edtParceiro: TEdit;
    lytCodigo: TLayout;
    lbCodigo: TLabel;
    edtCodigo: TEdit;
    procedure btnCancelarClick(Sender: TObject);
    procedure btnAcaoClick(Sender: TObject);
    procedure btnReenviarClick(Sender: TObject);
  private
    FShield: TShield;
    FEstado: TEstadoCadastro;
    procedure MudarEstado(NovoEstado: TEstadoCadastro);
  public
    class procedure Executar(AShield: TShield);
  end;

var
  frmCadastro: TfrmCadastro;

implementation

{$R *.fmx}

class procedure TfrmCadastro.Executar(AShield: TShield);
var
  Form: TfrmCadastro;
begin
  Form := TfrmCadastro.Create(nil);
  try
    Form.FShield := AShield;
    Form.MudarEstado(estSolicitando);
    Form.ShowModal;
  finally
    Form.Free;
  end;
end;

procedure TfrmCadastro.MudarEstado(NovoEstado: TEstadoCadastro);
begin
  FEstado := NovoEstado;
  case FEstado of
    estSolicitando:
    begin
      btnAcao.Text := 'Enviar Código de Validação';
      btnReenviar.Visible := False;
      lytCodigo.Visible := False;
      
      edtNome.Enabled := True;
      edtEmail.Enabled := True;
      edtSenha.Enabled := True;
      edtCNPJ.Enabled := True;
    end;
    estConfirmando:
    begin
      btnAcao.Text := 'CONFIRMAR CADASTRO';
      btnReenviar.Visible := True;
      btnReenviar.Enabled := True;
      lytCodigo.Visible := True;
      edtCodigo.SetFocus;
      
      // Bloqueia campos chave para evitar troca de e-mail no meio do processo
      edtNome.Enabled := False;
      edtEmail.Enabled := False;
      edtSenha.Enabled := False;
      edtCNPJ.Enabled := False;
    end;
  end;
end;

procedure TfrmCadastro.btnAcaoClick(Sender: TObject);
var
  Msg: string;
begin
  // Passo 1: SOLICITAR
  if FEstado = estSolicitando then
  begin
      if (Trim(edtNome.Text) = '') or (Trim(edtEmail.Text) = '') or 
         (Trim(edtSenha.Text) = '') or (Trim(edtCNPJ.Text) = '') then
      begin
        ShowMessage('Preencha os campos obrigatórios (*).');
        Exit;
      end;

      try
        Msg := FShield.SolicitarCodigoCadastro(
          edtNome.Text,
          edtEmail.Text,
          edtCNPJ.Text,
          edtRazao.Text
        );
        
        ShowMessage('Sucesso: ' + Msg);
        MudarEstado(estConfirmando);
      except
        on E: Exception do
          ShowMessage('Erro ao solicitar código: ' + E.Message);
      end;
  end
  // Passo 2: CONFIRMAR
  else if FEstado = estConfirmando then
  begin
      if Trim(edtCodigo.Text) = '' then
      begin
        ShowMessage('Por favor, digite o código de verificação enviado para seu e-mail.');
        edtCodigo.SetFocus;
        Exit;
      end;

      try
        if FShield.ConfirmarCadastro(
          edtNome.Text,
          edtEmail.Text,
          edtSenha.Text,
          edtCNPJ.Text,
          edtRazao.Text,
          edtWhatsapp.Text,
          edtCodigo.Text,
          edtParceiro.Text
        ) then
        begin
          ShowMessage('Cadastro realizado com sucesso!');
          Close;
        end;
      except
        on E: Exception do
          ShowMessage('Erro ao confirmar: ' + E.Message);
      end;
  end;
end;

procedure TfrmCadastro.btnReenviarClick(Sender: TObject);
begin
  // Reenvia usando os mesmos dados (já validados visualmente)
  try
    FShield.SolicitarCodigoCadastro(
      edtNome.Text,
      edtEmail.Text,
      edtCNPJ.Text,
      edtRazao.Text
    );
    ShowMessage('Código reenviado! Verifique seu e-mail.');
  except
    on E: Exception do
      ShowMessage('Erro ao reenviar: ' + E.Message);
  end;
end;

procedure TfrmCadastro.btnCancelarClick(Sender: TObject);
begin
  Close;
end;

end.
