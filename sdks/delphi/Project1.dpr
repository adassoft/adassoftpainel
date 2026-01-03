program Project1;

uses
  Vcl.Forms,
  uChave in 'uChave.pas' {frmChave},
  uCadastroExemplo in 'uCadastroExemplo.pas' {frmCadastroExemplo},
  uLoginPrompt in 'uLoginPrompt.pas' {frmLoginPrompt},
  Example.Config in 'Example.Config.pas',
  uGerenciarTerminais in 'uGerenciarTerminais.pas' {frmGerenciarTerminais};

{$R *.res}

begin
  Application.Initialize;
  Application.MainFormOnTaskbar := True;
  Application.CreateForm(TfrmChave, frmChave);
  Application.Run;
end.
