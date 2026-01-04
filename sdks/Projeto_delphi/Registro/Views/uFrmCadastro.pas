unit uFrmCadastro;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.StdCtrls, Vcl.ExtCtrls,
  Shield.Core;

type
  TEstadoCadastro = (ecSolicitar, ecConfirmar);

  TfrmCadastro = class(TForm)
    pnlTopo: TPanel;
    lblTitulo: TLabel;
    pnlBottom: TPanel;
    btnCadastrar: TButton;
    btnCancelar: TButton;
    pnlCentro: TPanel;
    Label1: TLabel;
    Label2: TLabel;
    Label3: TLabel;
    Label4: TLabel;
    Label5: TLabel;
    Label6: TLabel;
    edtNome: TEdit;
    edtEmail: TEdit;
    edtSenha: TEdit;
    edtCNPJ: TEdit;
    edtRazao: TEdit;
    edtWhatsapp: TEdit;
    LabelCodigo: TLabel;
    edtCodigo: TEdit;
    LabelParceiro: TLabel;
    edtParceiro: TEdit;
    procedure btnCadastrarClick(Sender: TObject);
    procedure btnCancelarClick(Sender: TObject);
    procedure FormCreate(Sender: TObject);
  private
    FShield: TShield;
    FEstado: TEstadoCadastro;
  public
    class function Executar(AShield: TShield): Boolean;
  end;

var
  frmCadastro: TfrmCadastro;

implementation

{$R *.dfm}

class function TfrmCadastro.Executar(AShield: TShield): Boolean;
var
  Form: TfrmCadastro;
begin
  Form := TfrmCadastro.Create(nil);
  try
    Form.FShield := AShield;
    Result := (Form.ShowModal = mrOk);
  finally
    Form.Free;
  end;
end;

procedure TfrmCadastro.FormCreate(Sender: TObject);
begin
  FEstado := ecSolicitar;
  LabelCodigo.Visible := False;
  edtCodigo.Visible := False;
  btnCadastrar.Caption := 'Enviar Código de Validação';
end;

procedure TfrmCadastro.btnCadastrarClick(Sender: TObject);
var
  Msg: string;
begin
  if FEstado = ecSolicitar then
  begin
    // --- ETAPA 1: SOLICITAR CÓDIGO ---
    
    if (Trim(edtNome.Text) = '') or (Trim(edtEmail.Text) = '') or (Trim(edtSenha.Text) = '') or (Trim(edtCNPJ.Text) = '') then
    begin
      ShowMessage('Preencha os campos obrigatórios (Nome, Email, Senha, CNPJ).');
      Exit;
    end;

    if Pos('@', edtEmail.Text) = 0 then
    begin
      ShowMessage('O e-mail informado parece inválido.');
      edtEmail.SetFocus;
      Exit;
    end;
    
    Screen.Cursor := crHourGlass;
    try
      try
        Msg := FShield.SolicitarCodigoCadastro(edtNome.Text, edtEmail.Text, edtCNPJ.Text, edtRazao.Text);
        
        ShowMessage(Msg); 
        
        FEstado := ecConfirmar;
        LabelCodigo.Visible := True;
        edtCodigo.Visible := True;
        btnCadastrar.Caption := 'CONFIRMAR CADASTRO';
        
        edtEmail.Enabled := False; 
        edtParceiro.Enabled := False; 
        edtCodigo.SetFocus;
      except
        on E: Exception do
          ShowMessage(E.Message);
      end;
    finally
      Screen.Cursor := crDefault;
    end;
  end
  else
  begin
    // --- ETAPA 2: CONFIRMAR CÓDIGO ---
    
    if Trim(edtCodigo.Text) = '' then
    begin
      ShowMessage('Por favor, informe o código de verificação enviado para ' + edtEmail.Text);
      edtCodigo.SetFocus;
      Exit;
    end;
    
    Screen.Cursor := crHourGlass;
    try
      try
         if FShield.ConfirmarCadastro(edtNome.Text, edtEmail.Text, edtSenha.Text, 
                                      edtCNPJ.Text, edtRazao.Text, edtWhatsapp.Text, edtCodigo.Text, edtParceiro.Text) then
         begin
           ShowMessage('Cadastro realizado com sucesso! Você já está autenticado.');
           ModalResult := mrOk;
         end;
      except
        on E: Exception do
          ShowMessage('Erro na confirmação: ' + E.Message);
      end;
    finally
      Screen.Cursor := crDefault;
    end;
  end;
end;

procedure TfrmCadastro.btnCancelarClick(Sender: TObject);
begin
  ModalResult := mrCancel;
end;

end.
