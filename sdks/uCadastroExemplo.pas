
unit uCadastroExemplo;

interface

uses
  System.SysUtils, System.Classes, System.JSON,

  Vcl.Controls, Vcl.Forms, Vcl.StdCtrls, Vcl.ExtCtrls, IdHTTP, IdSSLOpenSSL, Vcl.Dialogs,
  Vcl.Mask;

type
  TfrmCadastroExemplo = class(TForm)
  private
    { Private declarations }
  public
    procedure AtualizarContexto(const Email, Status, Expira: string);
  published
    PanelHeader: TPanel;
    lblTitulo: TLabel;
    PanelBody: TPanel;
    lblResumo: TLabel;
    MemoHint: TMemo;
    btnFechar: TButton;
    edtNome: TLabeledEdit;
    edtEmail: TLabeledEdit;
    edtTelefone: TLabeledEdit;
    edtCNPJ: TLabeledEdit;
    edtRazao: TLabeledEdit;
    edtSenha: TLabeledEdit;
    edtUF: TLabeledEdit;
    edtLogin: TLabeledEdit;
    btnCadastrar: TButton;
    procedure btnFecharClick(Sender: TObject);
    procedure btnCadastrarClick(Sender: TObject);
  end;


implementation

{$R *.dfm}

procedure TfrmCadastroExemplo.AtualizarContexto(const Email, Status,
  Expira: string);
begin
  lblResumo.Caption := Format('Licença atual: %s | %s | %s', [Email, Status, Expira]);
end;

procedure TfrmCadastroExemplo.btnFecharClick(Sender: TObject);
begin
  Close;
end;


procedure TfrmCadastroExemplo.btnCadastrarClick(Sender: TObject);
var
  Http: TIdHTTP;
  SSL: TIdSSLIOHandlerSocketOpenSSL;
  Json, Resp: TStringStream;
  Body: TJSONObject;
begin
  Http := TIdHTTP.Create(nil);
  SSL := TIdSSLIOHandlerSocketOpenSSL.Create(nil);
  Json := TStringStream.Create('', TEncoding.UTF8);
  Resp := TStringStream.Create('', TEncoding.UTF8);
  try
    Http.IOHandler := SSL;
    Http.Request.ContentType := 'application/json';

    Body := TJSONObject.Create;
    try
      Body.AddPair('action', 'cadastrar_empresa_usuario');
      Body.AddPair('nome', edtNome.Text);
      Body.AddPair('email', edtEmail.Text);
      Body.AddPair('telefone', edtTelefone.Text);
      Body.AddPair('cnpj', edtCNPJ.Text);
      Body.AddPair('razao', edtRazao.Text);
      Body.AddPair('senha', edtSenha.Text);
      Body.AddPair('uf', edtUF.Text);
      Body.AddPair('login', edtLogin.Text);
      Json.WriteString(Body.ToJSON);
    finally
      Body.Free;
    end;

    Http.Post('https://SEU_DOMINIO/api_validacao.php', Json, Resp);
    ShowMessage(Resp.DataString);
  finally
    Json.Free;
    Resp.Free;
    SSL.Free;
    Http.Free;
  end;
end;


end.
