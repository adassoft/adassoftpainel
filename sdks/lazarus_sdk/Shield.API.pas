unit Shield.API;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, fphttpclient, opensslsockets; 
  // Nota: Em Windows precisa das DLLs do OpenSSL. 
  // Em Linux geralmente já tem.
  // Se não quiser HTTPS, remova opensslsockets, mas a API Shield é HTTPS geralmente.

type
  TShieldAPI = class
  private
    FClient: TFPHTTPClient;
  public
    constructor Create;
    destructor Destroy; override;
    
    function PostRequest(const URL, JsonBody: String; const Token: String = ''): String;
    function GetRequest(const URL: String; const Token: String = ''): String;
  end;

implementation

constructor TShieldAPI.Create;
begin
  FClient := TFPHTTPClient.Create(nil);
  FClient.AddHeader('Content-Type', 'application/json');
  // Ajuste timeout se necessário
  FClient.IOTimeout := 15000; 
  // Permitir redirecionamentos (útil para alguns configs de server)
  FClient.AllowRedirect := True;
end;

destructor TShieldAPI.Destroy;
begin
  FClient.Free;
  inherited;
end;

function TShieldAPI.PostRequest(const URL, JsonBody: String; const Token: String): String;
var
  ResponseStream: TStringStream;
  SourceStream: TStringStream;
begin
  if Token <> '' then
    FClient.AddHeader('Authorization', 'Bearer ' + Token);
    
  SourceStream := TStringStream.Create(JsonBody);
  ResponseStream := TStringStream.Create('');
  try
    try
      FClient.RequestBody := SourceStream;
      FClient.Post(URL, ResponseStream);
      Result := ResponseStream.DataString;
    except
      on E: Exception do
        raise Exception.Create('Erro HTTP POST: ' + E.Message);
    end;
  finally
    SourceStream.Free;
    ResponseStream.Free;
  end;
end;

function TShieldAPI.GetRequest(const URL: String; const Token: String): String;
begin
  if Token <> '' then
    FClient.AddHeader('Authorization', 'Bearer ' + Token);
    
  try
    Result := FClient.Get(URL);
  except
    on E: Exception do
      Result := ''; // Ou raise
  end;
end;

end.
