unit uMain;

interface

uses
  System.SysUtils, System.Types, System.UITypes, System.Classes, System.Variants,
  FMX.Types, FMX.Controls, FMX.Forms, FMX.Graphics, FMX.Dialogs, FMX.StdCtrls,
  FMX.Controls.Presentation, FMX.Edit, Shield.Core, Shield.Config;

type
  TfrmMain = class(TForm)
    lblStatus: TLabel;
    btnMinhaConta: TButton;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure btnMinhaContaClick(Sender: TObject);
    procedure FormShow(Sender: TObject);
  private
    FShield: TShield;
    procedure UpdateStatus;
  public
    { Public declarations }
  end;

var
  frmMain: TfrmMain;

implementation

{$R *.fmx}

uses uFrmRegistro;

procedure TfrmMain.FormCreate(Sender: TObject);
var
  Config: TShieldConfig;
begin
  // Configuração Exemplo - Substitua pelos seus dados reais
  Config := TShieldConfig.Create(
    'https://adassoft.com/api/v1/adassoft', // URL BASE
    '689a'+'97dd'+'741a6'+'8b75'+'e7fcd'+'17f11'+'13681'+'83058'+'6356f'+'8f0ae'+'b69a8'+'99b69'+'3c1b2b9',                          // API KEY
    1,                                           // Software ID
    '3.10.14',                                     // Versão
    'edf82'+'ca9d5'+'317f6e'+'7d6d2'+'15f6aa'+'d9a863'+'45deec'+'9eba26'+'be999e'+'bd56e2'+'9408a23'                        // Segredo Offline
  );
  
  FShield := TShield.Create(Config);
end;

procedure TfrmMain.FormShow(Sender: TObject);
begin
  // Verifica Licença ao Exibir
  FShield.CheckLicense;
  
  if not FShield.License.IsValid then
  begin
    // Se não for válida, abre a tela de registro automaticamente
    // Usamos um Timer pequeno ou chamada postergada para garantir que o Main form carregou
    TThread.ForceQueue(nil, procedure
    begin
      TfrmRegistro.Exibir(FShield);
      UpdateStatus;
    end);
  end
  else
    UpdateStatus;
end;

procedure TfrmMain.FormDestroy(Sender: TObject);
begin
  if Assigned(FShield) then
    FShield.Free;
end;

procedure TfrmMain.UpdateStatus;
begin
  if FShield.License.IsValid then
  begin
    lblStatus.Text := 'SISTEMA ATIVO E LICENCIADO';
    lblStatus.TextSettings.FontColor := TAlphaColors.Green;
  end
  else
  begin
    lblStatus.Text := 'SISTEMA BLOQUEADO / TRIAL EXPIRADO';
    lblStatus.TextSettings.FontColor := TAlphaColors.Red;
  end;
end;

procedure TfrmMain.btnMinhaContaClick(Sender: TObject);
begin
  TfrmRegistro.Exibir(FShield);
  UpdateStatus;
end;

end.
