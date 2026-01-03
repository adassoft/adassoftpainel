unit uLoginPrompt;

interface

uses
  System.SysUtils, System.Classes,
  Vcl.Controls, Vcl.Forms, Vcl.StdCtrls, Vcl.Dialogs;

type
  TfrmLoginPrompt = class(TForm)
    lblInstrucao: TLabel;
    lblEmail: TLabel;
    edtEmail: TEdit;
    lblSenha: TLabel;
    edtSenha: TEdit;
    btnAtivar: TButton;
    btnCancelar: TButton;
    chkLembrar: TCheckBox;
    procedure btnAtivarClick(Sender: TObject);
    procedure btnCancelarClick(Sender: TObject);
  public
    class function Executar(const EmailInicial: string;
      out Email, Senha: string; out Lembrar: Boolean): Boolean;
  end;

implementation

{$R *.dfm}

class function TfrmLoginPrompt.Executar(const EmailInicial: string;
  out Email, Senha: string; out Lembrar: Boolean): Boolean;
var
  Form: TfrmLoginPrompt;
begin
  Form := TfrmLoginPrompt.Create(nil);
  try
    Form.edtEmail.Text := EmailInicial;
    Form.edtSenha.Text := '';
    Form.chkLembrar.Checked := True;
    Result := Form.ShowModal = mrOk;
    if Result then
    begin
      Email := Trim(Form.edtEmail.Text);
      Senha := Form.edtSenha.Text;
      Lembrar := Form.chkLembrar.Checked;
    end;
  finally
    Form.Free;
  end;
end;

procedure TfrmLoginPrompt.btnAtivarClick(Sender: TObject);
begin
  if Trim(edtEmail.Text) = '' then
  begin
    ShowMessage('Informe o e-mail cadastrado.');
    Exit;
  end;
  if edtSenha.Text = '' then
  begin
    ShowMessage('Digite a senha do portal.');
    Exit;
  end;
  ModalResult := mrOk;
end;

procedure TfrmLoginPrompt.btnCancelarClick(Sender: TObject);
begin
  ModalResult := mrCancel;
end;

end.
