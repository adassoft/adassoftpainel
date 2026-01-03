unit Shield.Types;

interface

uses
  System.SysUtils, System.JSON, System.DateUtils;

type
  TShieldStatus = (stUnchecked, stValid, stExpired, stInvalid, stOfflineError);

  TSessionInfo = record
    Token: string;
    ExpiraEm: TDateTime;
    procedure Clear;
  end;

  TNewsItem = record
    Id: Integer;
    Titulo: string;
    Conteudo: string;
    Link: string;
    Prioridade: string; // 'alta', 'normal', 'baixa'
    Data: TDateTime;
    Lida: Boolean;
  end;
  TNewsArray = TArray<TNewsItem>;

  TLicenseInfo = record
    SoftwareId: Integer;
    Serial: string;
    EmpresaCodigo: Integer;
    SoftwareNome: string;
    Versao: string;
    TerminaisPermitidos: Integer;
    TerminaisUtilizados: Integer;
    AvisoAtivo: Boolean;
    DiasAviso: Integer;
    DataExpiracao: TDateTime;
    DiasRestantes: Integer;
    Status: TShieldStatus;
    Mensagem: string;
    AvisoMensagem: string;
    Noticias: TNewsArray; // Lista de noticias
    procedure Clear;
    function IsExpired: Boolean;
    function IsValid: Boolean;
    function ShouldWarnExpiration: Boolean;
  end;

  TUserInfo = record
    Email: string;
    Nome: string;
    procedure Clear;
  end;

  TPlan = record
    Id: Integer;
    Nome: string;
    Valor: Double;
    RecorrenciaMeses: Integer;
    Descricao: string;
  end;
  TPlanArray = TArray<TPlan>;

  TShieldCallback = reference to procedure(const Success: Boolean; const Msg: string);

implementation

{ TSessionInfo }

procedure TSessionInfo.Clear;
begin
  Token := '';
  ExpiraEm := 0;
end;

{ TLicenseInfo }

procedure TLicenseInfo.Clear;
begin
  SoftwareId := 0;
  Serial := '';
  EmpresaCodigo := 0;
  SoftwareNome := '';
  Versao := '';
  TerminaisPermitidos := 0;
  TerminaisUtilizados := 0;
  AvisoAtivo := True; // Padrão
  DiasAviso := 5;     // Padrão
  DataExpiracao := 0;
  DiasRestantes := 0;
  Status := stUnchecked;
  Mensagem := '';
  AvisoMensagem := '';
  SetLength(Noticias, 0);
end;

function TLicenseInfo.IsExpired: Boolean;
begin
  Result := (DataExpiracao > 0) and (DataExpiracao < Now);
end;

function TLicenseInfo.IsValid: Boolean;
begin
  Result := (Status = stValid) and (not IsExpired);
end;

function TLicenseInfo.ShouldWarnExpiration: Boolean;
begin
  Result := IsValid and AvisoAtivo and (DiasRestantes <= DiasAviso) and (DiasRestantes >= 0);
end;

{ TUserInfo }

procedure TUserInfo.Clear;
begin
  Email := '';
  Nome := '';
end;

end.
