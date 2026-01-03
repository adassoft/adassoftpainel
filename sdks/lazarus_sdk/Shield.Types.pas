unit Shield.Types;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils;

type
  TLicenseStatus = (lsUnchecked, lsValid, lsExpired, lsInvalid, lsOfflineError);

  TLicenseInfo = record
    SoftwareId: Integer;
    Serial: String;
    EmpresaCodigo: Integer;
    SoftwareNome: String;
    Versao: String;
    TerminaisPermitidos: Integer;
    TerminaisUtilizados: Integer;
    DataExpiracao: TDateTime;
    DiasRestantes: Integer;
    Status: TLicenseStatus;
    Mensagem: String;
    // Novos campos de alerta
    AvisoAtivo: Boolean;
    DiasAviso: Integer;
    
    procedure Clear;
    function IsExpired: Boolean;
    function IsValid: Boolean;
    function ShouldWarnExpiration: Boolean;
  end;

  TSessionInfo = record
    Token: String;
    ExpiraEm: TDateTime;
    procedure Clear;
  end;
  
  TPlan = class
  private
    FId: Integer;
    FNome: String;
    FValor: Double;
    FRecorrencia: String;
  public
    property Id: Integer read FId write FId;
    property Nome: String read FNome write FNome;
    property Valor: Double read FValor write FValor;
    property Recorrencia: String read FRecorrencia write FRecorrencia;
  end;

implementation

{ TLicenseInfo }

procedure TLicenseInfo.Clear;
begin
  Status := lsUnchecked;
  Serial := '';
  Mensagem := '';
  TerminaisUtilizados := 0;
  AvisoAtivo := True;
  DiasAviso := 5;
  DataExpiracao := 0;
end;

function TLicenseInfo.IsExpired: Boolean;
begin
  Result := (DataExpiracao > 0) and (DataExpiracao < Now);
end;

function TLicenseInfo.IsValid: Boolean;
begin
  Result := (Status = lsValid) and not IsExpired;
end;

function TLicenseInfo.ShouldWarnExpiration: Boolean;
begin
  Result := IsValid and AvisoAtivo and (DiasRestantes <= DiasAviso) and (DiasRestantes >= 0);
end;

{ TSessionInfo }

procedure TSessionInfo.Clear;
begin
  Token := '';
  ExpiraEm := 0;
end;

end.
