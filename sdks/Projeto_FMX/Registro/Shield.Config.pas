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

class function TShieldConfig.Create(const AUrl, AKey: string; ASoftId: Integer;
  ASoftVer, ASecret: string): TShieldConfig;
begin
  Result.BaseUrl := AUrl;
  Result.ApiKey := AKey;
  Result.SoftwareId := ASoftId;
  Result.SoftwareVersion := ASoftVer;
  Result.OfflineSecret := ASecret;
  Result.CacheDir := ''; // Padrão: AppData/Local
end;

end.
