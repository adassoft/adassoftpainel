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
    'https://adassoft.com/api/v1/adassoft', // URL Base
    'COLE_SUA_API_KEY_AQUI',                // API Key de Conexão (Chave Raw iniciada com sk_...)
    1,                                      // Software ID
    '1.0.0',                                // Versão
    'COLE_SEU_HASH_OFFLINE_AQUI'            // Segredo Offline (Hash SHA-256 obtido no botão "Ver Segredo" do Painel)
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
