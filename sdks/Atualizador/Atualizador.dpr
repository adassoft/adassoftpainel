program Atualizador;

uses
  Vcl.Forms,
  uMain in 'uMain.pas' {frmMain},
  Shield.Core in '..\Projeto_delphi\Registro\Shield.Core.pas',
  Shield.API in '..\Projeto_delphi\Registro\Shield.API.pas',
  Shield.Config in '..\Projeto_delphi\Registro\Shield.Config.pas',
  Shield.Types in '..\Projeto_delphi\Registro\Shield.Types.pas',
  Shield.Security in '..\Projeto_delphi\Registro\Shield.Security.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.MainFormOnTaskbar := True;
  Application.Title := 'Assistente de Atualização';
  Application.CreateForm(TfrmMain, frmMain);
  Application.Run;
end.
