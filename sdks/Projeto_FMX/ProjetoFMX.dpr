program ProjetoFMX;

uses
  System.StartUpCopy,
  FMX.Forms,
  uMain in 'uMain.pas' {frmMain},
  uFrmRegistro in 'Registro\Views\uFrmRegistro.pas' {frmRegistro},
  uFrmCadastro in 'Registro\Views\uFrmCadastro.pas' {frmCadastro},
  Shield.API in 'Registro\Shield.API.pas',
  Shield.Config in 'Registro\Shield.Config.pas',
  Shield.Core in 'Registro\Shield.Core.pas',
  Shield.Security in 'Registro\Shield.Security.pas',
  Shield.Types in 'Registro\Shield.Types.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.CreateForm(TfrmMain, frmMain);
  Application.Run;
end.
