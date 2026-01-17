unit Shield.Config;

interface

type
  TShieldConfig = record
  public
    BaseUrl: string;
    ApiKey: string;
    SoftwareId: Integer;
    SoftwareVersion: string;
    OfflineSecret: string;
    // Parâmetros opcionais para caminhos de arquivo
    CacheDir: string;
    
    // Construtor helper
    class function Create(const AUrl, AKey: string; ASoftId: Integer; ASoftVer, ASecret: string): TShieldConfig; static;
  end;

implementation

{ TShieldConfig }

uses System.SysUtils;

class function TShieldConfig.Create(const AUrl, AKey: string; ASoftId: Integer;
  ASoftVer, ASecret: string): TShieldConfig;
begin
  Result.BaseUrl := Trim(AUrl);
  Result.ApiKey := Trim(AKey);
  Result.SoftwareId := ASoftId;
  Result.SoftwareVersion := Trim(ASoftVer);
  Result.OfflineSecret := Trim(ASecret);
  Result.CacheDir := ''; // Padrão: AppData/Local
end;

end.
