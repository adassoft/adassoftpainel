unit Shield.Config;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils;

type
  TShieldConfig = class
  private
    FAPIUrl: String;
    FAPIKey: String;
    FSoftwareId: Integer;
    FSoftwareVersion: String;
    FSecretAutomacao: String;
    FCacheDir: String;
  public
    constructor Create(const AAPIUrl, AAPIKey: String; ASoftwareId: Integer; 
                       const AVersion: String = '1.0.0'; 
                       const ASecretAutomacao: String = '');

    property APIUrl: String read FAPIUrl;
    property APIKey: String read FAPIKey;
    property SoftwareId: Integer read FSoftwareId;
    property SoftwareVersion: String read FSoftwareVersion;
    property SecretAutomacao: String read FSecretAutomacao;
    property CacheDir: String read FCacheDir write FCacheDir;
  end;

implementation

constructor TShieldConfig.Create(const AAPIUrl, AAPIKey: String; ASoftwareId: Integer;
  const AVersion: String; const ASecretAutomacao: String);
begin
  FAPIUrl := AAPIUrl;
  if FAPIUrl[Length(FAPIUrl)] <> '/' then
    FAPIUrl := FAPIUrl + '/';
    
  FAPIKey := AAPIKey;
  FSoftwareId := ASoftwareId;
  FSoftwareVersion := AVersion;
  FSecretAutomacao := ASecretAutomacao;
  
  // Padrão multiplataforma: GetAppConfigDir
  FCacheDir := GetAppConfigDir(False) + 'ShieldCache' + PathDelim;
  if not DirectoryExists(FCacheDir) then
    ForceDirectories(FCacheDir);
end;

end.
