program ProjetoTest;

uses
  Vcl.Forms,
  uPrincipal in 'uPrincipal.pas' {Form1},
  Shield.API in 'Registro\Shield.API.pas',
  Shield.Config in 'Registro\Shield.Config.pas',
  Shield.Core in 'Registro\Shield.Core.pas',
  Shield.Security in 'Registro\Shield.Security.pas',
  Shield.Types in 'Registro\Shield.Types.pas',
  uFrmAlert in 'Registro\Views\uFrmAlert.pas' {frmAlert},
  uFrmCadastro in 'Registro\Views\uFrmCadastro.pas' {frmCadastro},
  uFrmCheckout in 'Registro\Views\uFrmCheckout.pas' {FrmCheckout},
  uFrmRegistro in 'Registro\Views\uFrmRegistro.pas' {frmRegistro},
  uFrmRenovacao in 'Registro\Views\uFrmRenovacao.pas' {frmRenovacao};

{$R *.res}

begin
  Application.Initialize;
  Application.MainFormOnTaskbar := True;
  Application.CreateForm(TForm1, Form1);
  Application.CreateForm(TfrmRegistro, frmRegistro);
  Application.CreateForm(TfrmRenovacao, frmRenovacao);
  Application.CreateForm(TfrmAlert, frmAlert);
  Application.CreateForm(TfrmCadastro, frmCadastro);
  Application.CreateForm(TFrmCheckout, FrmCheckout);
  Application.CreateForm(TfrmAlert, frmAlert);
  Application.CreateForm(TfrmCadastro, frmCadastro);
  Application.CreateForm(TFrmCheckout, FrmCheckout);
  Application.CreateForm(TfrmRegistro, frmRegistro);
  Application.CreateForm(TfrmRenovacao, frmRenovacao);
  Application.Run;
end.
