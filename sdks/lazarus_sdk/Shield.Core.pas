unit Shield.Core;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, fpjson, jsonparser, dateutils,
  Shield.Types, Shield.Config, Shield.API, Shield.Security;

type
  TShield = class
  private
    FConfig: TShieldConfig;
    FLicense: TLicenseInfo;
    FSession: TSessionInfo;
    FAPI: TShieldAPI;
    
    procedure ProcessApiResponse(const JsonStr: String);
  public
    constructor Create(AConfig: TShieldConfig);
    destructor Destroy; override;
    
    function CheckLicense(const Serial: String = ''): Boolean;
    function GetAvailablePlans: TList; // Retorna lista de TPlan
    function CreateCheckoutUrl(PlanId: Integer): String;
    
    property License: TLicenseInfo read FLicense;
    property Session: TSessionInfo read FSession;
  end;

implementation

constructor TShield.Create(AConfig: TShieldConfig);
begin
  FConfig := AConfig;
  FAPI := TShieldAPI.Create;
  FLicense.Clear;
  FSession.Clear;
end;

destructor TShield.Destroy;
begin
  FAPI.Free;
  inherited;
end;

procedure TShield.ProcessApiResponse(const JsonStr: String);
var
  JData: TJSONData;
  JObj: TJSONObject;
  ValidadeStr, StatusStr: String;
begin
  if JsonStr = '' then Exit;

  try
    JData := GetJSON(JsonStr);
    try
      if not (JData is TJSONObject) then Exit;
      JObj := TJSONObject(JData);
      
      FLicense.Mensagem := JObj.Get('mensagem', '');
      if FLicense.Mensagem = '' then FLicense.Mensagem := JObj.Get('error', '');
      
      // Suporte a 'success' (ingles) e 'sucesso' (pt)
      StatusStr := 'erro'; // temp flag
      if JObj.IndexOf('sucesso') > -1 then
         if JObj.Get('sucesso', False) then StatusStr := 'ok';
         
      if StatusStr <> 'ok' then
         if JObj.Get('success', False) then StatusStr := 'ok';

      if StatusStr = 'ok' then
      begin
        // Re-ler status real da licenca
        StatusStr := JObj.Get('status', 'invalido');
        
        if SameText(StatusStr, 'ativo') or 
           SameText(StatusStr, 'true') or 
           JObj.Get('valido', False) then FLicense.Status := lsValid
        else if SameText(StatusStr, 'suspenso') then FLicense.Status := lsInvalid
        else if SameText(StatusStr, 'expirado') then FLicense.Status := lsExpired
        else FLicense.Status := lsInvalid;
        
        ValidadeStr := JObj.Get('validade_licenca', '');
        if ValidadeStr = '' then ValidadeStr := JObj.Get('data_expiracao', '');
        
        // Tentar converter data YYYY-MM-DD
        if ValidadeStr <> '' then
        begin
          try
             // Pega so os 10 primeiros chars YYYY-MM-DD
             if Length(ValidadeStr) > 10 then ValidadeStr := Copy(ValidadeStr, 1, 10);
             FLicense.DataExpiracao := ScanDateTime('yyyy-mm-dd', ValidadeStr); 
          except
             try
                FLicense.DataExpiracao := ISO8601ToDate(ValidadeStr);
             except
                FLicense.DataExpiracao := 0;
             end;
          end;
        end;

        FLicense.DiasRestantes := JObj.Get('dias_restantes', 0);
        FLicense.TerminaisPermitidos := JObj.Get('terminais_permitidos', 1);
        FLicense.TerminaisUtilizados := JObj.Get('terminais_utilizados', 0);
        FLicense.EmpresaCodigo := JObj.Get('empresa_codigo', 0);
        
        // Alertas
        FLicense.AvisoAtivo := JObj.Get('app_alerta_vencimento', True);
        FLicense.DiasAviso := JObj.Get('app_dias_alerta', 5);
        
        // Session
        FSession.Token := JObj.Get('token', '');
      end
      else
      begin
        FLicense.Status := lsInvalid;
      end;
      
    finally
      JData.Free;
    end;
  except
    on E: Exception do
    begin
      FLicense.Status := lsOfflineError;
      FLicense.Mensagem := 'Erro ao processar resposta: ' + E.Message;
    end;
  end;
end;

function TShield.CheckLicense(const Serial: String): Boolean;
var
  Response: String;
  JsonBody: TJSONObject;
  Url: String;
begin
  if Serial <> '' then FLicense.Serial := Serial;
  
  try
    JsonBody := TJSONObject.Create;
    try
      JsonBody.Add('acao', 'validar_serial');
      JsonBody.Add('serial', FLicense.Serial);
      JsonBody.Add('software_id', FConfig.SoftwareId);
      JsonBody.Add('versao', FConfig.SoftwareVersion);
      JsonBody.Add('mac', TShieldSecurity.GetHardwareId);
      JsonBody.Add('pc_name', GetEnvironmentVariable('COMPUTERNAME')); 
      
      // Nova Rota REST: /validate
      Url := FConfig.APIUrl;
      if (Url <> '') and (Url[Length(Url)] <> '/') then Url := Url + '/';
      Url := Url + 'validate';
      
      Response := FAPI.PostRequest(Url, JsonBody.AsJSON);
    finally
      JsonBody.Free;
    end;
    
    ProcessApiResponse(Response);
    Result := FLicense.IsValid;
    
  except
    on E: Exception do
    begin
      FLicense.Status := lsOfflineError;
      FLicense.Mensagem := 'Erro de conexão: ' + E.Message;
      Result := False;
    end;
  end;
end;

function TShield.GetAvailablePlans: TList;
var
  Response: String;
  JData: TJSONData;
  JArr: TJSONArray;
  JObjRoot: TJSONObject;
  JItem: TJSONObject;
  i: Integer;
  Plan: TPlan;
  Url: String;
begin
  Result := TList.Create;
  
  // Nova Rota REST: /software/{id}/plans
  Url := FConfig.APIUrl;
  if (Url <> '') and (Url[Length(Url)] <> '/') then Url := Url + '/';
  Url := Url + 'software/' + IntToStr(FConfig.SoftwareId) + '/plans';
    
  try
    Response := FAPI.GetRequest(Url, FSession.Token);
    if Response = '' then Exit;
    
    JData := GetJSON(Response);
    try
      // A nova API retorna Object com chave "planos"
      if JData is TJSONObject then
      begin
         JObjRoot := TJSONObject(JData);
         JArr := JObjRoot.Get('planos', TJSONArray(nil));
         
         if JArr <> nil then
         begin
            for i := 0 to JArr.Count - 1 do
            begin
              JItem := TJSONObject(JArr.Objects[i]);
              Plan := TPlan.Create;
              Plan.Id := JItem.Get('id', 0);
              Plan.Nome := JItem.Get('nome_plano', '');
              try
                 Plan.Valor := JItem.Get('valor', 0.0);
              except
                 Plan.Valor := StrToFloatDef(JItem.Get('valor', '0'), 0.0);
              end;
              Plan.Recorrencia := JItem.Get('recorrencia', 'mensal');
              Result.Add(Plan);
            end;
         end;
      end
      else if JData is TJSONArray then // Fallback
      begin
        JArr := TJSONArray(JData);
        for i := 0 to JArr.Count - 1 do
        begin
          JItem := TJSONObject(JArr.Objects[i]);
          Plan := TPlan.Create;
          Plan.Id := JItem.Get('id', 0);
          Plan.Nome := JItem.Get('nome_plano', '');
          try
             Plan.Valor := JItem.Get('valor', 0.0);
          except
             Plan.Valor := StrToFloatDef(JItem.Get('valor', '0'), 0.0);
          end;
          Plan.Recorrencia := JItem.Get('recorrencia', 'mensal');
          Result.Add(Plan);
        end;
      end;
    finally
      JData.Free;
    end;
  except
    // Retorna lista vazia
  end;
end;

function TShield.CreateCheckoutUrl(PlanId: Integer): String;
var
  JsonBody: TJSONObject;
  Response: String;
  JData: TJSONData;
  Url: String;
begin
  Result := '';
  try
    JsonBody := TJSONObject.Create;
    try
      JsonBody.Add('plan_id', PlanId);
      JsonBody.Add('licenca_serial', FLicense.Serial);
      
      // Nova Rota REST: /orders
      Url := FConfig.APIUrl;
      if (Url <> '') and (Url[Length(Url)] <> '/') then Url := Url + '/';
      Url := Url + 'orders';
      
      Response := FAPI.PostRequest(Url, JsonBody.AsJSON, FSession.Token);
    finally
      JsonBody.Free;
    end;
    
    if Response <> '' then
    begin
      JData := GetJSON(Response);
      try
         if JData is TJSONObject then
         begin
            Result := TJSONObject(JData).Get('init_point', '');
            if Result = '' then
              Result := TJSONObject(JData).Get('link_pagamento', '');
              
            if Result = '' then
            begin
                // Fallback monta URL manual
                if TJSONObject(JData).Get('success', False) or 
                   TJSONObject(JData).Get('sucesso', False) then
                begin
                   if TJSONObject(JData).IndexOf('cod_transacao') > -1 then
                   begin
                      // URL front
                      Result := StringReplace(FConfig.APIUrl, '/api/v1/adassoft', '', [rfReplaceAll, rfIgnoreCase]);
                      if (Result <> '') and (Result[Length(Result)] = '/') then Delete(Result, Length(Result), 1);
                      Result := Result + '/checkout/pay/' + TJSONObject(JData).Get('cod_transacao', '');
                   end;
                end;
            end;
         end;
      finally
        JData.Free;
      end;
    end;
  except
    Result := '';
  end;
end;

end.
