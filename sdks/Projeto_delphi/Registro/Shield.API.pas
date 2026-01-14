unit Shield.API;

interface

uses
  System.SysUtils, System.Classes, System.JSON, IdHTTP, IdSSLOpenSSL, IdURI, System.DateUtils,
  Shield.Types, Shield.Config;

type
  TShieldAPI = class
  private
    FConfig: TShieldConfig;
    function CreateClient: TIdHTTP;
  public
    constructor Create(const AConfig: TShieldConfig);
    
    function Ping: Boolean;
    function PostRequest(const Action: string; Payload: TJSONObject; const Token: string = ''): TJSONObject;
    function GetRequest(const Action: string; const Token: string = ''): TJSONObject;
    function GetPlans(const SoftwareId: Integer; const Token: string): TJSONArray;
    
    // Updated to return full payment info
    function CreateOrder(const PlanId: Integer; const Serial: string; const Token: string): TPaymentInfo;
    function CheckPaymentStatus(const TransactionId: string; const Token: string): string;
    
    function RegisterUser(Payload: TJSONObject): TJSONObject;
    function CheckUpdate(const SoftwareId: Integer; const CurrentVersion: string): TJSONObject;
  end;

implementation



{ TShieldAPI }

constructor TShieldAPI.Create(const AConfig: TShieldConfig);
begin
  FConfig := AConfig;
end;

function TShieldAPI.CreateClient: TIdHTTP;
var
  SSL: TIdSSLIOHandlerSocketOpenSSL;
begin
  Result := TIdHTTP.Create(nil);
  SSL := TIdSSLIOHandlerSocketOpenSSL.Create(Result);
  SSL.SSLOptions.Mode := sslmClient;
  SSL.SSLOptions.SSLVersions := [sslvTLSv1_2];
  Result.IOHandler := SSL;
  
  Result.ConnectTimeout := 10000;
  Result.ReadTimeout := 10000;
  Result.Request.Accept := 'application/json';
  Result.Request.ContentType := 'application/json';
  Result.Request.CharSet := 'utf-8';
  Result.Request.UserAgent := 'ShieldSDK/1.0 (Delphi)';
  
  if FConfig.ApiKey <> '' then
    Result.Request.CustomHeaders.Values['X-API-KEY'] := FConfig.ApiKey;
end;

function TShieldAPI.Ping: Boolean;
var
  Http: TIdHTTP;
begin
  Http := CreateClient;
  try
    try
      // Tenta um HEAD simples na URL base, se falhar, tenta GET
      try
         Http.Head(FConfig.BaseUrl);
      except
         // Alguns servidores bloqueiam HEAD
         Http.Get(FConfig.BaseUrl);
      end;
      Result := Http.ResponseCode = 200;
    except
      Result := False;
    end;
  finally
    Http.Free;
  end;
end;

function TShieldAPI.PostRequest(const Action: string; Payload: TJSONObject; const Token: string): TJSONObject;
var
  Http: TIdHTTP;
  ReqStream: TStringStream;
  RespString: string;
begin
  Http := CreateClient;
  try
    if Token <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + Token;
      
    // Adiciona a action ao payload se não existir
    if Payload.Values['action'] = nil then
      Payload.AddPair('action', Action);
      
    ReqStream := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
    try
      try
        RespString := Http.Post(FConfig.BaseUrl, ReqStream);
        Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
        if Result = nil then
          raise Exception.Create('Invalid JSON response');
      except
        on E: EIdHTTPProtocolException do
        begin
          // Tenta ler o erro do corpo se houver
          RespString := E.ErrorMessage;
          if RespString = '' then RespString := '{}';
          Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
          if Result = nil then
             // Se não for JSON, relança o erro original
            raise Exception.Create('API Error: ' + E.Message);
        end;
        on E: Exception do
          raise Exception.Create('Connection Error: ' + E.Message);
      end;
    finally
      ReqStream.Free;
    end;
  finally
    Http.Free;
  end;
end;

function TShieldAPI.GetRequest(const Action: string; const Token: string): TJSONObject;
var
  Http: TIdHTTP;
  RespString: string;
  Url: string;
begin
  Http := CreateClient;
  try
    if Token <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + Token;
      
    Url := FConfig.BaseUrl + '?action=' + Action;
    try
      RespString := Http.Get(Url);
      Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
    except
       on E: Exception do raise Exception.Create('Get Error: ' + E.Message);
    end;
  finally
    Http.Free;
  end;
end;

function TShieldAPI.GetPlans(const SoftwareId: Integer; const Token: string): TJSONArray;
var
  Http: TIdHTTP;
  RespString: string;
  Url: string;
  JsonObj: TJSONObject;
begin
  Http := CreateClient;
  try
    if Token <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + Token;
      
    // Nova Rota REST: /software/{id}/plans
    // Assume BaseUrl = .../api/v1/shield
    // Removemos barra final se houver para garantir concatenação limpa
    Url := FConfig.BaseUrl;
    if Url[Length(Url)] = '/' then Delete(Url, Length(Url), 1);
    
    Url := Url + '/software/' + IntToStr(SoftwareId) + '/plans';
    
    // Auth Params para Middleware
    Url := Url + '?api_key=' + TIdURI.ParamsEncode(FConfig.ApiKey);
    Url := Url + '&timestamp=' + TIdURI.ParamsEncode(DateToISO8601(Now, False));
    
    try
      RespString := Http.Get(Url);
      
      JsonObj := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      try
        if JsonObj <> nil then
        begin
          if JsonObj.GetValue('planos') is TJSONArray then
            Result := JsonObj.GetValue('planos').Clone as TJSONArray
          else
            Result := TJSONArray.Create;
        end
        else
          raise Exception.Create('Invalid JSON from GetPlans');
      finally
        JsonObj.Free;
      end;
    except
      on E: Exception do
      begin
         // Fallback silencioso ou re-raise
         raise Exception.Create('Erro ao buscar planos: ' + E.Message);
      end;
    end;
  finally
    Http.Free;
  end;
end;

function TShieldAPI.CreateOrder(const PlanId: Integer; const Serial: string; const Token: string): TPaymentInfo;
var
  Http: TIdHTTP;
  ReqStream: TStringStream;
  Payload: TJSONObject;
  RespString: string;
  Url: string;
  RespJson, PayObj: TJSONObject;
begin
  Result.Clear;
  Http := CreateClient;
  Payload := TJSONObject.Create;
  try
    if Token <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + Token;

    // Nova Rota REST: /orders
    Url := FConfig.BaseUrl;
    if Url[Length(Url)] = '/' then Delete(Url, Length(Url), 1);
    Url := Url + '/orders';
    
    Payload.AddPair('plan_id', TJSONNumber.Create(PlanId));
    if Serial <> '' then
      Payload.AddPair('licenca_serial', Serial);
      
    // Auth Params obrigatórios
    Payload.AddPair('api_key', FConfig.ApiKey);
    Payload.AddPair('timestamp', DateToISO8601(Now, False));
      
    ReqStream := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
    try
      try
      RespString := Http.Post(Url, ReqStream);
      
      RespJson := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      try
        if (RespJson <> nil) then
        begin
             if RespJson.GetValue('cod_transacao') <> nil then
                Result.TransactionId := RespJson.GetValue('cod_transacao').Value;

             if RespJson.GetValue('payment') is TJSONObject then
             begin
                PayObj := RespJson.GetValue('payment') as TJSONObject;
                if PayObj.GetValue('qr_code_base64') <> nil then
                   Result.QrCodeBase64 := PayObj.GetValue('qr_code_base64').Value;
                   
                if PayObj.GetValue('qr_code_payload') <> nil then
                   Result.QrCodePayload := PayObj.GetValue('qr_code_payload').Value;
                   
                 if PayObj.GetValue('valor') <> nil then
                   Result.Valor := StrToFloatDef(PayObj.GetValue('valor').Value, 0.0, TFormatSettings.Invariant);
                   
                if PayObj.GetValue('vencimento') <> nil then
                   Result.Vencimento := PayObj.GetValue('vencimento').Value;
             end;
        end
        else
          raise Exception.Create('Failed to create order: No valid JSON response.');
      finally
        RespJson.Free;
      end;
    except
      on E: EIdHTTPProtocolException do
      begin
         // Tenta ler o JSON de erro
         RespString := E.ErrorMessage;
         if RespString = '' then RespString := '{}';
         RespJson := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
         try
           if (RespJson <> nil) and (RespJson.GetValue('error') <> nil) then
              raise Exception.Create('API Error: ' + RespJson.GetValue('error').Value)
           else
              raise Exception.Create('HTTP Error ' + IntToStr(E.ErrorCode) + ': ' + E.Message);
         finally
           RespJson.Free;
         end;
      end;
    end;
    finally
      ReqStream.Free;
    end;
  finally
    Payload.Free;
    Http.Free;
  end;
end;

function TShieldAPI.CheckPaymentStatus(const TransactionId: string; const Token: string): string;
var
  Http: TIdHTTP;
  ReqStream: TStringStream;
  Payload: TJSONObject;
  RespString: string;
  Url: string;
  RespJson: TJSONObject;
begin
  Result := 'pending';
  Http := CreateClient;
  Payload := TJSONObject.Create;
  try
    if Token <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + Token;

    // Rota: /orders/status (POST para enviar JSON body com cod_transacao)
    Url := FConfig.BaseUrl;
    if Url[Length(Url)] = '/' then Delete(Url, Length(Url), 1);
    Url := Url + '/orders/status';
    
    Payload.AddPair('cod_transacao', TransactionId);
      
    ReqStream := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
    try
      RespString := Http.Post(Url, ReqStream);
      
      RespJson := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      try
        if (RespJson <> nil) and (RespJson.GetValue('status') <> nil) then
        begin
             Result := RespJson.GetValue('status').Value;
        end;
      finally
        RespJson.Free;
      end;
    except
       // Se der erro de conexão etc, retorna pending para tentar de novo
       Result := 'error'; 
    end;
  finally
    ReqStream.Free;
    Payload.Free;
    Http.Free;
  end;
end;

function TShieldAPI.RegisterUser(Payload: TJSONObject): TJSONObject;
var
  Http: TIdHTTP;
  ReqStream: TStringStream;
  RespString: string;
  Url: string;
begin
  Http := CreateClient;
  
  // Nova Rota REST: /register
  Url := FConfig.BaseUrl;
  if Url[Length(Url)] = '/' then Delete(Url, Length(Url), 1);
  Url := Url + '/register';

  ReqStream := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
  try
    try
      RespString := Http.Post(Url, ReqStream);
      Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      if Result = nil then raise Exception.Create('Invalid JSON Response');
    except
      on E: EIdHTTPProtocolException do
      begin
        RespString := E.ErrorMessage;
        if RespString = '' then RespString := '{}';
        Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      end;
      on E: Exception do raise Exception.Create('Register Error: ' + E.Message);
    end;
  finally
    ReqStream.Free;
    Http.Free;
  end;
end;

end;
    
function TShieldAPI.CheckUpdate(const SoftwareId: Integer; const CurrentVersion: string): TJSONObject;
var
  Http: TIdHTTP;
  RespString, Url: string;
begin
  Http := CreateClient;
  try
    Url := FConfig.BaseUrl;
    if Url[Length(Url)] = '/' then Delete(Url, Length(Url), 1);
    Url := Url + '/updates/check';
    
    Url := Url + '?software_id=' + IntToStr(SoftwareId) + 
                 '&current_version=' + TIdURI.ParamsEncode(CurrentVersion);

    try
      RespString := Http.Get(Url);
      Result := TJSONObject.ParseJSONValue(RespString) as TJSONObject;
      if Result = nil then raise Exception.Create('Invalid JSON from CheckUpdate');
    except
      on E: Exception do raise Exception.Create('CheckUpdate Error: ' + E.Message);
    end;
  finally
    Http.Free;
  end;
end;

end.
