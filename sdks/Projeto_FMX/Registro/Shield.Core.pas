unit Shield.Core;

interface

uses
  System.SysUtils, System.Classes, System.JSON, System.DateUtils, System.IOUtils, System.Generics.Collections, System.NetEncoding,
  Shield.Types, Shield.Config, Shield.Security, Shield.API;

type
  TShield = class
  private
    FConfig: TShieldConfig;
    FAPI: TShieldAPI;
    FLicense: TLicenseInfo;
    FSession: TSessionInfo;
    FOnChange: TShieldCallback;
    FIsInitialized: Boolean;
    
    function GetCachePath: string;
    procedure LoadCache;

    procedure ProcessApiResponse(Json: TJSONObject);
    function BuildCommonPayload: TJSONObject;
  public
    constructor Create(const AConfig: TShieldConfig);
    destructor Destroy; override;

    procedure SaveCache;

    // Métodos Principais
    function CheckLicense(const Serial: string = ''): Boolean;
    function Authenticate(const Email, Senha, InstalacaoID: string): Boolean;
    function GenerateOfflineChallenge(const Serial, InstalacaoID: string): string;
    function ActivateOffline(const ActivationKeyString: string): Boolean;
    procedure Logout;
    
    // Renovação e Pagamento
    function GetAvailablePlans: TPlanArray;
    function CheckoutPlan(const PlanId: Integer): TPaymentInfo;
    function CheckPaymentStatus(const TransactionId: string): string;
    
    // Propriedades
    property License: TLicenseInfo read FLicense;
    property Session: TSessionInfo read FSession;
    property IsInitialized: Boolean read FIsInitialized;
    property Config: TShieldConfig read FConfig;
    
    // Utilitário
    function GetMachineFingerprint: string;
    
    // Cadastro (Novos métodos 2FA)
    function SolicitarCodigoCadastro(const Nome, Email, CNPJ, Razao: string): string;
    function ConfirmarCadastro(const Nome, Email, Senha, CNPJ, Razao, WhatsApp, Codigo: string; const Parceiro: string = ''): Boolean;
  end;

implementation

{ TShield }

constructor TShield.Create(const AConfig: TShieldConfig);
begin
  FConfig := AConfig;
  if FConfig.CacheDir = '' then
    FConfig.CacheDir := TPath.Combine(TPath.GetHomePath, 'ShieldApps');
    
  if not TDirectory.Exists(FConfig.CacheDir) then
    TDirectory.CreateDirectory(FConfig.CacheDir);
    
  FAPI := TShieldAPI.Create(FConfig);
  FLicense.Clear;
  FSession.Clear;
  FIsInitialized := True;
  
  // Tenta carregar estado anterior
  LoadCache;
end;

destructor TShield.Destroy;
begin
  FAPI.Free;
  inherited;
end;

function TShield.GetCachePath: string;
begin
  Result := TPath.Combine(FConfig.CacheDir, 'shield_' + IntToStr(FConfig.SoftwareId) + '.dat');
end;

function TShield.GetMachineFingerprint: string;
begin
  Result := TShieldSecurity.GenerateFingerprint;
end;

procedure TShield.LoadCache;
var
  Path: string;
  Encrypted, JsonStr: string;
  Obj: TJSONObject;
begin
  Path := GetCachePath;
  if not TFile.Exists(Path) then Exit;
  
  try
    Encrypted := TFile.ReadAllText(Path);
    JsonStr := TShieldSecurity.DecryptString(Encrypted);
    Obj := TJSONObject.ParseJSONValue(JsonStr) as TJSONObject;
    if Obj <> nil then
    try
      if Obj.GetValue('token') <> nil then
        FSession.Token := Obj.GetValue('token').Value;
        
      if Obj.GetValue('serial') <> nil then
        FLicense.Serial := Obj.GetValue('serial').Value;

      if Obj.GetValue('data_inicio') <> nil then
         FLicense.DataInicio := ISO8601ToDate(Obj.GetValue('data_inicio').Value);

      // Recupera Data de Validade
      if Obj.GetValue('data_expiracao') <> nil then
      begin
        FLicense.DataExpiracao := ISO8601ToDate(Obj.GetValue('data_expiracao').Value);
        // Recalcula dias restantes localmente para exibir correto na inicialização
        if FLicense.DataExpiracao > 0 then
          FLicense.DiasRestantes := Trunc(FLicense.DataExpiracao) - Trunc(Now);
      end;
        
      if Obj.GetValue('terminais_permitidos') <> nil then
        FLicense.TerminaisPermitidos := StrToIntDef(Obj.GetValue('terminais_permitidos').Value, 0);

      if Obj.GetValue('terminais_utilizados') <> nil then
        FLicense.TerminaisUtilizados := StrToIntDef(Obj.GetValue('terminais_utilizados').Value, 0);

      if Obj.GetValue('data_inicio') <> nil then
        FLicense.DataInicio := ISO8601ToDate(Obj.GetValue('data_inicio').Value);
        
      // Recupera Aviso offline
      if Obj.GetValue('aviso_licenca') <> nil then
        FLicense.AvisoMensagem := Obj.GetValue('aviso_licenca').Value;

      // Sanitiza Cache se data já passou
      if (FLicense.DataExpiracao > 0) and (FLicense.DiasRestantes < 0) then
      begin
          FLicense.Status := stExpired;
          FLicense.AvisoMensagem := 'Sua licença expirou. Conecte-se para renovar.';
      end;

      // Recupera Noticias (Cache Local)
      if Obj.GetValue('noticias_cache') <> nil then
      begin
         var RepArray := Obj.GetValue('noticias_cache') as TJSONArray;
         SetLength(FLicense.Noticias, RepArray.Count);
         for var I := 0 to RepArray.Count - 1 do
         begin
            var NItem := RepArray.Items[I] as TJSONObject;
            FLicense.Noticias[I].Id := StrToIntDef(NItem.GetValue('id').Value, 0);
            FLicense.Noticias[I].Titulo := NItem.GetValue('titulo').Value;
            FLicense.Noticias[I].Conteudo := NItem.GetValue('conteudo').Value;
            if NItem.GetValue('link') <> nil then FLicense.Noticias[I].Link := NItem.GetValue('link').Value;
            if NItem.GetValue('prioridade') <> nil then FLicense.Noticias[I].Prioridade := NItem.GetValue('prioridade').Value;
            if NItem.GetValue('lida') <> nil then 
               FLicense.Noticias[I].Lida := NItem.GetValue<TJSONBool>('lida').AsBoolean;
            if NItem.GetValue('data') <> nil then
               FLicense.Noticias[I].DataPublicacao := ISO8601ToDate(NItem.GetValue('data').Value);
         end;
      end;

    finally
      Obj.Free;
    end;
  except
    TFile.Delete(Path);
  end;
end;

procedure TShield.SaveCache;
var
  Obj: TJSONObject;
  JsonStr, Encrypted: string;
  ArrNoticias: TJSONArray;
  NObj: TJSONObject;
begin
  Obj := TJSONObject.Create;
  try
    if FSession.Token <> '' then
      Obj.AddPair('token', FSession.Token);
    
    if FLicense.Serial <> '' then
      Obj.AddPair('serial', FLicense.Serial);
      
    if FLicense.DataInicio > 0 then
      Obj.AddPair('data_inicio', DateToISO8601(FLicense.DataInicio));

    if FLicense.DataExpiracao > 0 then
      Obj.AddPair('data_expiracao', DateToISO8601(FLicense.DataExpiracao));
      
    if FLicense.TerminaisPermitidos > 0 then
      Obj.AddPair('terminais_permitidos', TJSONNumber.Create(FLicense.TerminaisPermitidos));

    if FLicense.TerminaisUtilizados > 0 then
      Obj.AddPair('terminais_utilizados', TJSONNumber.Create(FLicense.TerminaisUtilizados));

    if FLicense.DataInicio > 0 then
      Obj.AddPair('data_inicio', DateToISO8601(FLicense.DataInicio));
      
    if FLicense.AvisoMensagem <> '' then
      Obj.AddPair('aviso_licenca', FLicense.AvisoMensagem);
      
    // Salvar array de Noticias
    if Length(FLicense.Noticias) > 0 then
    begin
       ArrNoticias := TJSONArray.Create;
       for var I := 0 to High(FLicense.Noticias) do
       begin
          NObj := TJSONObject.Create;
          NObj.AddPair('id', TJSONNumber.Create(FLicense.Noticias[I].Id));
          NObj.AddPair('titulo', FLicense.Noticias[I].Titulo);
          NObj.AddPair('conteudo', FLicense.Noticias[I].Conteudo);
          NObj.AddPair('link', FLicense.Noticias[I].Link);
          NObj.AddPair('prioridade', FLicense.Noticias[I].Prioridade);
          NObj.AddPair('lida', TJSONBool.Create(FLicense.Noticias[I].Lida));
          NObj.AddPair('data', DateToISO8601(FLicense.Noticias[I].DataPublicacao));
          ArrNoticias.AddElement(NObj);
       end;
       Obj.AddPair('noticias_cache', ArrNoticias);
    end;
      
    JsonStr := Obj.ToJSON;
    Encrypted := TShieldSecurity.EncryptString(JsonStr);
    TFile.WriteAllText(GetCachePath, Encrypted);
  finally
    Obj.Free;
  end;
end;

function TShield.BuildCommonPayload: TJSONObject;
begin
  Result := TJSONObject.Create;
  Result.AddPair('api_key', FConfig.ApiKey);
  Result.AddPair('software_id', TJSONNumber.Create(FConfig.SoftwareId));
  if FConfig.SoftwareVersion <> '' then
    Result.AddPair('versao_software', FConfig.SoftwareVersion);
    
  Result.AddPair('mac_address', TShieldSecurity.GetMacAddress);
  Result.AddPair('nome_computador', GetEnvironmentVariable('COMPUTERNAME'));
  // Proteção contra Replay Attack (Necessário para todas as chamadas autenticadas)
  Result.AddPair('timestamp', DateToISO8601(Now, False));
end;

function TShield.Authenticate(const Email, Senha, InstalacaoID: string): Boolean;
var
  Payload, Resp: TJSONObject;
  Instalacao: string;
begin
  Result := False;
  Instalacao := InstalacaoID;
  if Instalacao = '' then Instalacao := GetMachineFingerprint;
  
  Payload := BuildCommonPayload;
  try
    Payload.AddPair('action', 'emitir_token');
    Payload.AddPair('email', Email);
    Payload.AddPair('senha', Senha);
    Payload.AddPair('codigo_instalacao', Instalacao);
    
    Resp := FAPI.PostRequest('emitir_token', Payload);
    try
      if (Resp.GetValue('success') <> nil) and (Resp.GetValue('success') is TJSONBool) and
         (Resp.GetValue<TJSONBool>('success').AsBoolean) then
      begin
    // Verificacao de nulo para 'token'
        if Resp.GetValue('token') <> nil then
           FSession.Token := Resp.GetValue('token').Value
        else
           FSession.Token := ''; // Ou raise Exception

        ProcessApiResponse(Resp);
        SaveCache;
        Result := True;
      end
      else
      begin
        // Verificacao de nulo para 'mensagem' ou 'error'
        if Resp.GetValue('mensagem') <> nil then
           raise Exception.Create(Resp.GetValue('mensagem').Value)
        else if Resp.GetValue('error') <> nil then
           raise Exception.Create(Resp.GetValue('error').Value)
        else if Resp.GetValue('message') <> nil then
           raise Exception.Create(Resp.GetValue('message').Value)
        else
           raise Exception.Create('Erro desconhecido. Resposta: ' + Resp.ToString);
      end;
    finally
      Resp.Free;
    end;
  finally
    Payload.Free;
  end;
end;

function TShield.CheckLicense(const Serial: string): Boolean;
var
  Payload, Resp: TJSONObject;
  LocalSerial: string;
begin
  Result := False;
  LocalSerial := Serial;
  if LocalSerial = '' then LocalSerial := FLicense.Serial;
  
  if (LocalSerial = '') and (FSession.Token = '') then
  begin
    FLicense.Status := stInvalid;
    FLicense.Mensagem := 'Licença não encontrada.';
    Exit(False);
  end;

  Payload := BuildCommonPayload;
  try
    Payload.AddPair('action', 'validar_serial');
    if LocalSerial <> '' then
      Payload.AddPair('serial', LocalSerial);
    
    if FSession.Token <> '' then
      Payload.AddPair('token', FSession.Token);
      
    Payload.AddPair('codigo_instalacao', GetMachineFingerprint);

    Resp := FAPI.PostRequest('validar_serial', Payload);
    try
      if Resp.GetValue('validacao') <> nil then
        ProcessApiResponse(Resp)
      else 
      begin
         // Se nao veio validacao, verifica se tem erro explicito
         if Resp.GetValue('error') <> nil then
           FLicense.Mensagem := Resp.GetValue('error').Value
         else if Resp.GetValue('mensagem') <> nil then
           FLicense.Mensagem := Resp.GetValue('mensagem').Value
         else if Resp.GetValue('message') <> nil then
           FLicense.Mensagem := Resp.GetValue('message').Value;
           
         if FLicense.Mensagem <> '' then
           FLicense.Status := stInvalid;  
      end;
        
      Result := FLicense.IsValid;
      SaveCache;
    finally
      Resp.Free;
    end;
  finally
    Payload.Free;
  end;
end;

procedure TShield.ProcessApiResponse(Json: TJSONObject);
var
  ValObj: TJSONObject;
  sDate: string;
begin
  if Json.GetValue('validacao') <> nil then
    ValObj := Json.GetValue('validacao') as TJSONObject
  else if Json.GetValue('licenca') <> nil then
    ValObj := Json.GetValue('licenca') as TJSONObject
  else
    Exit;

  if ValObj = nil then Exit;

  if ValObj.GetValue('serial') <> nil then
    FLicense.Serial := ValObj.GetValue('serial').Value;

  if ValObj.GetValue('valido') <> nil then
  begin
    var sVal := ValObj.GetValue('valido').Value.ToLower;
    if (sVal = 'true') or (sVal = '1') then
      FLicense.Status := stValid
    else
      FLicense.Status := stInvalid;
  end;
  
  // Tenta ler a string bruta
  if ValObj.GetValue('data_expiracao') <> nil then
    sDate := ValObj.GetValue('data_expiracao').Value
  else if ValObj.GetValue('expiracao') <> nil then
    sDate := ValObj.GetValue('expiracao').Value;

  if sDate <> '' then
  begin
      // Tenta parsing manual primeiro, pois eh o mais seguro contra Locale do Windows
      // Formato esperado da API: YYYY-MM-DD
      if (Length(sDate) >= 10) and (sDate[5] = '-') and (sDate[8] = '-') then
      begin
         try
           FLicense.DataExpiracao := EncodeDate(
             StrToInt(Copy(sDate, 1, 4)),
             StrToInt(Copy(sDate, 6, 2)),
             StrToInt(Copy(sDate, 9, 2))
           );
         except
           FLicense.DataExpiracao := 0;
         end;
      end;
      
      // Se falhou o manual, cai no metodo nativo do Delphi
      if FLicense.DataExpiracao = 0 then
      begin
         FLicense.DataExpiracao := ISO8601ToDate(sDate);
      end;
      end;  
  
  if ValObj.GetValue('data_inicio') <> nil then
  begin
      sDate := ValObj.GetValue('data_inicio').Value;
      if sDate <> '' then
      begin
         try
           FLicense.DataInicio := ISO8601ToDate(sDate);
         except
           FLicense.DataInicio := 0;
         end;
      end;
  end;
  
  if ValObj.GetValue('dias_restantes') <> nil then
    FLicense.DiasRestantes :=  StrToIntDef(ValObj.GetValue('dias_restantes').Value, 0)
  else if FLicense.DataExpiracao > 0 then
    FLicense.DiasRestantes := Trunc(FLicense.DataExpiracao) - Trunc(Now);

  if ValObj.GetValue('terminais_permitidos') <> nil then
    FLicense.TerminaisPermitidos := StrToIntDef(ValObj.GetValue('terminais_permitidos').Value, 0);

  if ValObj.GetValue('terminais_utilizados') <> nil then
    FLicense.TerminaisUtilizados := StrToIntDef(ValObj.GetValue('terminais_utilizados').Value, 0);

  if ValObj.GetValue('erro') <> nil then
    FLicense.Mensagem := ValObj.GetValue('erro').Value;

  if ValObj.GetValue('app_alerta_vencimento') <> nil then
    FLicense.AvisoAtivo := ValObj.GetValue<TJSONBool>('app_alerta_vencimento').AsBoolean
  else
    FLicense.AvisoAtivo := True;

  if ValObj.GetValue('app_dias_alerta') <> nil then
    FLicense.DiasAviso := StrToIntDef(ValObj.GetValue('app_dias_alerta').Value, 5)
  else
    FLicense.DiasAviso := 5;

  if ValObj.GetValue('aviso_licenca') <> nil then
    FLicense.AvisoMensagem := ValObj.GetValue('aviso_licenca').Value
  else
    FLicense.AvisoMensagem := '';

  // [UPDATE] Check Nova Versão
  if ValObj.GetValue('update') <> nil then
  begin
    var UpdateObj := ValObj.GetValue('update');
    // Em Delphi 10.3+ GetValue pode retornar TJSONValue que precisa de cast seguro
    if (UpdateObj <> nil) and (UpdateObj is TJSONObject) then
    begin
       var JUpd := TJSONObject(UpdateObj);
       if JUpd.GetValue('disponivel') <> nil then
          FLicense.UpdateAvailable := JUpd.GetValue<TJSONBool>('disponivel').AsBoolean;
       
       if FLicense.UpdateAvailable then
       begin
           if JUpd.GetValue('nova_versao') <> nil then
              FLicense.NovaVersao := JUpd.GetValue('nova_versao').Value;
           
           if JUpd.GetValue('mensagem') <> nil then
              FLicense.UpdateMessage := JUpd.GetValue('mensagem').Value;
       end;
    end;
  end;

  // Parse Noticias
  if ValObj.GetValue('noticias') <> nil then
  begin
     var JNoticias := ValObj.GetValue('noticias');
     if JNoticias is TJSONArray then
     begin
       // Backup dos estados 'Lida'
       var LidasDict := TDictionary<Integer, Boolean>.Create;
       try
         for var K := 0 to High(FLicense.Noticias) do
            if FLicense.Noticias[K].Lida then
               LidasDict.AddOrSetValue(FLicense.Noticias[K].Id, True);

         SetLength(FLicense.Noticias, TJSONArray(JNoticias).Count);
         for var I := 0 to TJSONArray(JNoticias).Count - 1 do
         begin
           var JItem := TJSONArray(JNoticias).Items[I] as TJSONObject;
           FLicense.Noticias[I].Id := StrToIntDef(JItem.GetValue('id').Value, 0);
           FLicense.Noticias[I].Titulo := JItem.GetValue('titulo').Value;
           FLicense.Noticias[I].Conteudo := JItem.GetValue('conteudo').Value;
           if JItem.GetValue('link') <> nil then
              FLicense.Noticias[I].Link := JItem.GetValue('link').Value;
              
           if JItem.GetValue('prioridade') <> nil then
              FLicense.Noticias[I].Prioridade := JItem.GetValue('prioridade').Value;
              
           // Restaura estado Lida
           if LidasDict.ContainsKey(FLicense.Noticias[I].Id) then
              FLicense.Noticias[I].Lida := True
           else
              FLicense.Noticias[I].Lida := False;
         end;
       finally
         LidasDict.Free;
       end;
     end;
  end;

  // Sanitização de Mensagens (Correção Visual)
  if (FLicense.Status = stExpired) or (FLicense.Status = stInvalid) then
  begin
      // Se expirou, não faz sentido mostrar "aviso de que vai vencer"
      if FLicense.Status = stExpired then
         FLicense.AvisoMensagem := 'Sua licença expirou. Por favor, renove sua assinatura.'
      else
         FLicense.AvisoMensagem := 'Licença inválida ou bloqueada.';
  end;
end;

function TShield.GenerateOfflineChallenge(const Serial, InstalacaoID: string): string;
var
  Obj: TJSONObject;
begin
  Obj := BuildCommonPayload;
  try
    Obj.AddPair('serial', Serial);
    Obj.AddPair('instalacao_id', InstalacaoID);
    Obj.AddPair('timestamp', DateToISO8601(Now, False));
    Result := Obj.ToJSON;
  finally
    Obj.Free;
  end;
end;

function TShield.ActivateOffline(const ActivationKeyString: string): Boolean;
var
  Parts: TArray<string>;
  PayloadB64, Signature, CalculatedHash, JsonStr: string;
  Obj: TJSONObject;
begin
  Result := False;
  // Formato: PAYLOAD_BASE64.SIGNATURE_HEX
  Parts := ActivationKeyString.Split(['.']);
  if Length(Parts) <> 2 then
    raise Exception.Create('Código de ativação inválido (Formato incorreto).');
    
  PayloadB64 := Parts[0];
  Signature := Parts[1];
  
  // Valida Assinatura
  CalculatedHash := TShieldSecurity.ComputeOfflineHash(PayloadB64, FConfig.OfflineSecret);
  
  if not SameText(CalculatedHash, Signature) then
     raise Exception.Create('Código de ativação inválido (Assinatura não confere).');
     
  // Decodifica Payload
  try
    JsonStr := TNetEncoding.Base64.Decode(PayloadB64);
    Obj := TJSONObject.ParseJSONValue(JsonStr) as TJSONObject;
    if Obj = nil then raise Exception.Create('Payload corrompido.');
    
    try
      // Valida se é para ESTA máquina
      if Obj.GetValue('instalacao_id') <> nil then
      begin
         if Obj.GetValue('instalacao_id').Value <> GetMachineFingerprint then
            raise Exception.Create('Este código de ativação não é para este computador.');
      end;
      
      // Aplica a licença
      ProcessApiResponse(Obj);
      
      // Força status valido se não vier explícito
      FLicense.Status := stValid;
      FLicense.Mensagem := 'Ativado Offline com Sucesso';
      
      SaveCache;
      Result := True;
    finally
      Obj.Free;
    end;
  except
    on E: Exception do
      raise Exception.Create('Erro ao processar ativação: ' + E.Message);
  end;
end;

procedure TShield.Logout;
begin
  FSession.Clear;
  FLicense.Clear;
  if TFile.Exists(GetCachePath) then
    TFile.Delete(GetCachePath);
end;

function TShield.GetAvailablePlans: TPlanArray;
var
  JsonArr: TJSONArray;
  I: Integer;
  Item: TJSONObject;
begin
  SetLength(Result, 0);

  JsonArr := FAPI.GetPlans(FConfig.SoftwareId, FSession.Token);
  try
    SetLength(Result, JsonArr.Count);
    for I := 0 to JsonArr.Count - 1 do
    begin
      Item := JsonArr.Items[I] as TJSONObject;
      Result[I].Id := StrToIntDef(Item.GetValue('id').Value, 0);
      Result[I].Nome := Item.GetValue('nome_plano').Value;
      
      // Garante parsing correto de decimal com Ponto (JSON) independente do Locale do Windows
      var ValStr := Item.GetValue('valor').Value;
      Result[I].Valor := StrToFloatDef(ValStr, 0.0, TFormatSettings.Invariant);
      
      Result[I].Descricao := Item.GetValue('recorrencia').Value; 
    end;
  finally
    JsonArr.Free;
  end;
end;

function TShield.CheckoutPlan(const PlanId: Integer): TPaymentInfo;
begin
  if (FSession.Token = '') and (FLicense.Serial = '') then
    raise Exception.Create('É necessário estar autenticado ou ter um serial para criar um pedido.');
    
  Result := FAPI.CreateOrder(PlanId, FLicense.Serial, FSession.Token);
  
  if Result.TransactionId = '' then
     raise Exception.Create('Erro ao criar pedido. API não retornou ID.');
end;

function TShield.CheckPaymentStatus(const TransactionId: string): string;
begin
   Result := FAPI.CheckPaymentStatus(TransactionId, FSession.Token);
end;

// Novos Métodos de Cadastro

function TShield.SolicitarCodigoCadastro(const Nome, Email, CNPJ, Razao: string): string;
var
  Payload, Resp: TJSONObject;
begin
  Payload := TJSONObject.Create;
  try
    Payload.AddPair('acao', 'solicitar_codigo');
    Payload.AddPair('nome', Nome);
    Payload.AddPair('email', Email);
    Payload.AddPair('cnpj', CNPJ);
    Payload.AddPair('razao', Razao);
    // Timestamp obrigatório para evitar Replay Attack
    Payload.AddPair('timestamp', DateToISO8601(Now, False));
    
    Resp := FAPI.RegisterUser(Payload);
    try
      if (Resp <> nil) and (Resp.GetValue('success') is TJSONTrue) then
      begin
        if Resp.GetValue('mensagem') <> nil then
        begin
          Result := Resp.GetValue('mensagem').Value;
          if Trim(Result) = '' then Result := 'Código de verificação enviado com sucesso.';
        end
        else
          Result := 'Código de verificação enviado.';
          
        if Resp.GetValue('debug_code') <> nil then
           Result := Result + #13#10 + 'CÓDIGO (TESTE): ' + Resp.GetValue('debug_code').Value;
      end
      else if Resp <> nil then
      begin
         if Resp.GetValue('error') <> nil then
           raise Exception.Create(Resp.GetValue('error').Value)
         else
           raise Exception.Create('Erro ao solicitar código.');
      end;
    finally
      Resp.Free;
    end;
  finally
    Payload.Free;
  end;
end;

function TShield.ConfirmarCadastro(const Nome, Email, Senha, CNPJ, Razao, WhatsApp, Codigo: string; const Parceiro: string = ''): Boolean;
var
  Payload, Resp: TJSONObject;
begin
  Result := False;
  Payload := TJSONObject.Create;
  try
    Payload.AddPair('acao', 'confirmar_cadastro');
    Payload.AddPair('nome', Nome);
    Payload.AddPair('email', Email);
    Payload.AddPair('senha', Senha);
    Payload.AddPair('cnpj', CNPJ);
    Payload.AddPair('razao', Razao);
    Payload.AddPair('whatsapp', WhatsApp);
    Payload.AddPair('codigo', Codigo);
    // Timestamp obrigatório
    Payload.AddPair('timestamp', DateToISO8601(Now, False));
    
    if Parceiro <> '' then
       Payload.AddPair('codigo_parceiro', Parceiro);

    // Enviar ID do Software para criar licença de avaliação
    Payload.AddPair('software_id', TJSONNumber.Create(FConfig.SoftwareId));
    
    Resp := FAPI.RegisterUser(Payload);
    try
      if (Resp <> nil) and (Resp.GetValue('success') is TJSONTrue) then
      begin
        if Resp.GetValue('token') <> nil then
        begin
           FSession.Token := Resp.GetValue('token').Value;
           FIsInitialized := True; // Considera autenticado
           Result := True;
        end;
      end
      else if Resp <> nil then
      begin
         if Resp.GetValue('error') <> nil then
           raise Exception.Create(Resp.GetValue('error').Value)
         else
           raise Exception.Create('Erro ao confirmar cadastro.');
      end;
    finally
      Resp.Free;
    end;
  finally
    Payload.Free;
  end;
end;

end.
