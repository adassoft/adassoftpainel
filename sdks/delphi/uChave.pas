unit uChave;

interface

uses
  System.SysUtils, System.Classes, System.StrUtils, System.JSON,
  System.NetEncoding, System.DateUtils, System.Hash, System.Math,
  System.IOUtils,
  Vcl.Controls, Vcl.Forms, Vcl.StdCtrls, Vcl.ExtCtrls, Vcl.Clipbrd, Vcl.Menus,
  Winapi.Windows, Winapi.ShellAPI,
  IdHTTP, IdSSLOpenSSL,
  Example.Config,
  uGerenciarTerminais;

type
  TPlanResumo = record
    Id: Integer;
    Nome: string;
    Valor: Double;
    Recorrencia: Integer;
    function Display: string;
  end;

  TfrmChave = class(TForm)
    Panel1: TPanel;
    grpStatus: TGroupBox;
    lblStatusResumo: TLabel;
    lblStatusExpira: TLabel;
    lblStatusMensagem: TLabel;
    lblAvisoVencimento: TLabel;
    btnRenovar: TButton;
    btnGerenciarTerminais: TButton;
    pnlLoginHint: TPanel;
    lblLoginHint: TLabel;
    lblInfo: TLabel;
    lblApiUrl: TLabel;
    edtApiUrl: TEdit;
    lblApiKey: TLabel;
    edtApiKey: TEdit;
    lblMac: TLabel;
    edtMac: TEdit;
    lblComputer: TLabel;
    edtComputer: TEdit;
    lblSerial: TLabel;
    edtSerial: TEdit;
    lblToken: TLabel;
    memToken: TMemo;
    btnValidar: TButton;
    btnLimpar: TButton;
    lblResultado: TLabel;
    memResultado: TMemo;
    lblEmail: TLabel;
    edtEmail: TEdit;
    lblSenha: TLabel;
    edtSenha: TEdit;
    lblSoftwareId: TLabel;
    edtSoftwareId: TEdit;
    lblVersao: TLabel;
    edtVersao: TEdit;
    lblInstalacao: TLabel;
    edtInstalacao: TEdit;
    btnGerarToken: TButton;
    lblOffline: TLabel;
    memChallenge: TMemo;
    btnGerarChallenge: TButton;
    btnCopiarChallenge: TButton;
    lblChallengeHint: TLabel;
    MainMenu1: TMainMenu;
    mnuSistema: TMenuItem;
    mnuForcarValidar: TMenuItem;
    mnuLimparCache: TMenuItem;
    mnuSair: TMenuItem;
    mnuCadastros: TMenuItem;
    mnuCadastroClientes: TMenuItem;
    btnSolicitarPedido: TButton;
    lblLicencaId: TLabel;
    edtLicencaId: TEdit;
    lblPlanos: TLabel;
    cbPlanos: TComboBox;
    btnCarregarPlanos: TButton;
    procedure FormCreate(Sender: TObject);
    procedure btnValidarClick(Sender: TObject);
    procedure btnLimparClick(Sender: TObject);
    procedure btnGerarTokenClick(Sender: TObject);
    procedure btnGerarChallengeClick(Sender: TObject);
    procedure btnCopiarChallengeClick(Sender: TObject);
    procedure mnuForcarValidarClick(Sender: TObject);
    procedure mnuLimparCacheClick(Sender: TObject);
    procedure mnuSairClick(Sender: TObject);
    procedure mnuCadastroClientesClick(Sender: TObject);
    procedure btnRenovarClick(Sender: TObject);
    procedure btnGerenciarTerminaisClick(Sender: TObject);
    procedure btnSolicitarPedidoClick(Sender: TObject);
    procedure btnCarregarPlanosClick(Sender: TObject);
  private
    FConfig: TExampleConfig;
    FCacheFile: string;
    FCredentialsFile: string;
    FLicencaAtiva: Boolean;
    FRenovacaoUrl: string;
    FStoredEmail: string;
    FStoredPassword: string;
    FTentouAutoLogin: Boolean;
    FLembrarCredenciais: Boolean;
    FPlanos: TArray<TPlanResumo>;
    FForcedCheck: Boolean;
    procedure ValidarSerialViaApi(const ASilent: Boolean = False);
    function BuildPayload: TJSONObject;
    function BuildLoginPayload: TJSONObject;
    function BuildOfflineChallengePayload: TJSONObject;
    procedure AppendLog(const Msg: string);
    procedure RenderResponse(const ResponseText: string);
    procedure HabilitarInterface(const AEnabled: Boolean);
    function NormalizeToken(const Value: string): string;
    procedure PrepareMemo;
    procedure GerarTokenViaApi(const ASilent: Boolean = False);
    procedure ApplyTokenResponse(const ResponseText: string);
    function CreateHttpClient: TIdHTTP;
    procedure GerarChallengeOffline;
    procedure EnsureOfflineSecret;
    function EncodeBase64Url(const Text: string): string;
    procedure SincronizarAoIniciar;
    procedure CarregarCache;
    procedure SalvarCache(const Token: string; const Licenca: TJSONObject);
    procedure LimparCache(const LimparCampos: Boolean = False; const RemoverCredenciais: Boolean = False);
    procedure ProcessarLicencaJson(const Root: TJSONObject; const TokenValue: string);
    procedure AtualizarEstadoLicenca(const Fonte: TJSONObject; const TokenValue: string);
    procedure AtualizarStatusVisual(const StatusTexto, Mensagem: string;
      const ExpiraEm: TDateTime; const DiasAviso, DiasRestantesOverride: Integer;
      const LicencaValida: Boolean; const ExpiraTexto: string = '');
    function CacheDirectory: string;
    procedure MostrarPainelLogin(const Mostrar: Boolean);
    function TryParseIsoDate(const Value: string; out DateValue: TDateTime): Boolean;
    function EnsureLicencaAtiva: Boolean;
    function SolicitarLoginEGerarToken: Boolean;
    function TentarLoginAutomatico: Boolean;
    function HasStoredCredentials: Boolean;
    procedure AbrirCadastroExemplo;
    function ProtectString(const AValue: string): string;
    function UnprotectString(const AValue: string): string;
    procedure CarregarCredenciais;
    procedure SalvarCredenciais;
    procedure LimparCredenciaisArmazenadas;
    function SolicitarListaTerminais(out Lista: TArray<TTerminalResumo>;
      out TotaisResumo: string): Boolean;
    function ExecutarRemocaoTerminal(const TerminalId: Integer;
      const InstalacaoId: string): Boolean;
    procedure AtualizarAvisoTerminais(const Permitidos, Utilizados: Integer;
      var Mensagem: string);
    function ApiBaseUrl: string;
    function HttpPostJson(const AUrl, ABody, ABearer: string): string;
    function HttpGetJson(const AUrl, ABearer: string): string;
    function LoginTokenPedido(out AToken: string): Boolean;
    function CriarPedidoApp(const AToken: string; APlanoId: Integer;
      const ALicencaId, ALicencaSerial, ASoftwareNome: string; out ACodTransacao: string): Boolean;
    function ObterPagamentoUrl(const AToken, ACodTransacao: string;
      out APaymentUrl: string; out APago: Boolean): Boolean;
    function ListarPlanosApp(const AToken, ASoftwareId, ALicencaId: string;
      out APlanos: TArray<TPlanResumo>): Boolean;
    procedure PopularPlanosCombo(const APlanos: TArray<TPlanResumo>);
    function PlanoSelecionadoId: Integer;
    procedure MaybeForceCheckVencimento;
  end;

var
  frmChave: TfrmChave;

implementation

{$R *.dfm}

uses
  System.Generics.Collections,
  Vcl.Dialogs,
  REST.Json,
  uCadastroExemplo,
  uLoginPrompt;

type
  DATA_BLOB = record
    cbData: DWORD;
    pbData: PByte;
  end;
  PDATA_BLOB = ^DATA_BLOB;

function CryptProtectData(pDataIn: PDATA_BLOB; szDataDescr: PWideChar;
  pOptionalEntropy: PDATA_BLOB; pvReserved: Pointer;
  pPromptStruct: Pointer; dwFlags: DWORD; pDataOut: PDATA_BLOB): BOOL; stdcall;
  external 'crypt32.dll';
function CryptUnprotectData(pDataIn: PDATA_BLOB; ppszDataDescr: PPWideChar;
  pOptionalEntropy: PDATA_BLOB; pvReserved: Pointer;
  pPromptStruct: Pointer; dwFlags: DWORD; pDataOut: PDATA_BLOB): BOOL; stdcall;
  external 'crypt32.dll';

function GetJsonStringValue(const Obj: TJSONObject; const Key: string): string;
var
  Value: TJSONValue;
begin
  Result := '';
  if Obj = nil then
    Exit;
  Value := Obj.Values[Key];
  if Value <> nil then
    Result := Value.Value;
end;

function GetJsonBoolValue(const Obj: TJSONObject; const Key: string;
  const DefaultValue: Boolean): Boolean;
var
  Value: TJSONValue;
begin
  Result := DefaultValue;
  if Obj = nil then
    Exit;
  Value := Obj.Values[Key];
  if Value = nil then
    Exit;
  if Value is TJSONBool then
    Result := TJSONBool(Value).AsBoolean
  else
    Result := SameText(Value.Value, 'true') or (Value.Value = '1');
end;

function GetJsonIntegerValue(const Obj: TJSONObject; const Key: string;
  const DefaultValue: Integer): Integer;
var
  Value: TJSONValue;
begin
  Result := DefaultValue;
  if Obj = nil then
    Exit;
  Value := Obj.Values[Key];
  if Value = nil then
    Exit;
  if Value is TJSONNumber then
    Result := Trunc(TJSONNumber(Value).AsDouble)
  else
    Result := StrToIntDef(Value.Value, DefaultValue);
end;

{ TfrmChave }

procedure TfrmChave.AppendLog(const Msg: string);
begin
  memResultado.Lines.Add(Msg);
end;

function TfrmChave.BuildPayload: TJSONObject;
var
  SerialValue: string;
  TokenValue: string;
  ApiUrl: string;
  ApiKey: string;
  SoftwareId: Integer;
begin
  ApiUrl := Trim(edtApiUrl.Text);
  ApiKey := Trim(edtApiKey.Text);
  if ApiUrl = '' then
    raise Exception.Create('Informe a URL da API.');
  if ApiKey = '' then
    raise Exception.Create('Informe a API KEY disponibilizada no portal.');

  SoftwareId := StrToIntDef(Trim(edtSoftwareId.Text), 0);
  if SoftwareId <= 0 then
    raise Exception.Create('Informe um Software ID num'#233'rico antes de validar um serial.');

  SerialValue := Trim(edtSerial.Text);
  TokenValue := NormalizeToken(memToken.Text);
  if (SerialValue = '') and (TokenValue = '') then
    raise Exception.Create('Informe o serial ou cole um token assinado antes de validar.');

  Result := TJSONObject.Create;
  Result.AddPair('action', 'validar_serial');
  if SerialValue <> '' then
    Result.AddPair('serial', SerialValue);
  if TokenValue <> '' then
    Result.AddPair('token', TokenValue);
  Result.AddPair('api_key', ApiKey);
  Result.AddPair('software_id', TJSONNumber.Create(SoftwareId));
  if Trim(edtVersao.Text) <> '' then
    Result.AddPair('versao_software', Trim(edtVersao.Text));
  if Trim(edtMac.Text) <> '' then
    Result.AddPair('mac_address', Trim(edtMac.Text));
  if Trim(edtComputer.Text) <> '' then
    Result.AddPair('nome_computador', Trim(edtComputer.Text));
  if Trim(edtInstalacao.Text) <> '' then
    Result.AddPair('codigo_instalacao', Trim(edtInstalacao.Text));
end;

function TfrmChave.BuildOfflineChallengePayload: TJSONObject;
var
  SerialValue: string;
  SoftwareId: Integer;
begin
  SerialValue := Trim(edtSerial.Text);
  if SerialValue = '' then
    raise Exception.Create('Informe o serial para gerar o challenge offline.');

  SoftwareId := StrToIntDef(Trim(edtSoftwareId.Text), 0);
  if SoftwareId <= 0 then
    raise Exception.Create('Informe um Software ID v'#225'lido antes de gerar o challenge offline.');

  if Trim(edtInstalacao.Text) = '' then
    raise Exception.Create('Informe o c'#243'digo da instala'#231#227'o (GUID) para o challenge offline.');

  Result := TJSONObject.Create;
  Result.AddPair('serial', SerialValue);
  Result.AddPair('software_id', TJSONNumber.Create(SoftwareId));
  Result.AddPair('instalacao_id', Trim(edtInstalacao.Text));
  if Trim(edtMac.Text) <> '' then
    Result.AddPair('mac_address', Trim(edtMac.Text));
  if Trim(edtComputer.Text) <> '' then
    Result.AddPair('nome_computador', Trim(edtComputer.Text));
  if Trim(edtVersao.Text) <> '' then
    Result.AddPair('versao_software', Trim(edtVersao.Text));
  Result.AddPair('timestamp', DateToISO8601(Now, False));
end;

procedure TfrmChave.btnLimparClick(Sender: TObject);
begin
  edtSerial.Clear;
  memToken.Clear;
  memChallenge.Text := 'Clique em "Gerar challenge offline" para produzir o texto que deve ser enviado ao suporte quando n'#227'o houver internet.';
  memResultado.Clear;
  LimparCache(False);
  PrepareMemo;
end;

procedure TfrmChave.btnGerarTokenClick(Sender: TObject);
begin
  try
    GerarTokenViaApi(False);
  except
    on E: Exception do
      ShowMessage('Falha ao solicitar token: ' + E.Message);
  end;
end;

procedure TfrmChave.btnGerarChallengeClick(Sender: TObject);
begin
  try
    GerarChallengeOffline;
  except
    on E: Exception do
      ShowMessage('Falha ao gerar challenge offline: ' + E.Message);
  end;
end;

procedure TfrmChave.btnCopiarChallengeClick(Sender: TObject);
begin
  if memChallenge.Text = '' then
    Exit;
  Clipboard.AsText := memChallenge.Text;
  AppendLog('Challenge copiado para a '#225'rea de transfer'#234'ncia.');
end;

procedure TfrmChave.btnCarregarPlanosClick(Sender: TObject);
var
  Token: string;
begin
  try
    HabilitarInterface(False);
    if not LoginTokenPedido(Token) then
    begin
      ShowMessage('Falha ao autenticar. Verifique e-mail e senha.');
      Exit;
    end;
    if ListarPlanosApp(Token, Trim(edtSoftwareId.Text), Trim(edtLicencaId.Text), FPlanos) then
      PopularPlanosCombo(FPlanos)
    else
      ShowMessage('Nenhum plano encontrado para o software/licen'#231'a.');
  finally
    HabilitarInterface(True);
  end;
end;

procedure TfrmChave.btnSolicitarPedidoClick(Sender: TObject);
var
  Token: string;
  PlanoId: Integer;
  CodTransacao: string;
  PaymentUrl: string;
  Pago: Boolean;
  LicId, LicSerial: string;
begin
  try
    HabilitarInterface(False);
    if not LoginTokenPedido(Token) then
    begin
      ShowMessage('Falha ao autenticar. Verifique e-mail e senha.');
      Exit;
    end;

    // Carrega planos se ainda não carregado ou se combo vazio
    if (cbPlanos.Items.Count = 0) then
    begin
      if ListarPlanosApp(Token, Trim(edtSoftwareId.Text), Trim(edtLicencaId.Text), FPlanos) then
        PopularPlanosCombo(FPlanos);
    end;

    PlanoId := PlanoSelecionadoId;
    if PlanoId <= 0 then
    begin
      ShowMessage('Selecione um plano antes de gerar o pedido.');
      Exit;
    end;

    LicId := Trim(edtLicencaId.Text);
    LicSerial := Trim(edtSerial.Text);

    if not CriarPedidoApp(Token, PlanoId, LicId, LicSerial, Trim(edtVersao.Text), CodTransacao) then
    begin
      ShowMessage('N'#227'o foi poss'#237'vel criar o pedido.');
      Exit;
    end;

    if not ObterPagamentoUrl(Token, CodTransacao, PaymentUrl, Pago) then
    begin
      ShowMessage('N'#227'o foi poss'#237'vel gerar a URL de pagamento.');
      Exit;
    end;

    AppendLog('Pedido criado: ' + CodTransacao);
    if Pago then
    begin
      AppendLog('Pedido j'#225' consta como pago.');
      ShowMessage('Pedido j'#225' pago: ' + CodTransacao);
      Exit;
    end;

    AppendLog('URL de pagamento: ' + PaymentUrl);
    if PaymentUrl <> '' then
      ShellExecute(0, 'open', PChar(PaymentUrl), nil, nil, SW_SHOWNORMAL);
    ShowMessage('Pedido criado com sucesso. Abra o link de pagamento para concluir.');
  finally
    HabilitarInterface(True);
  end;
end;

procedure TfrmChave.btnValidarClick(Sender: TObject);
begin
  try
    ValidarSerialViaApi;
  except
    on E: Exception do
      ShowMessage('Falha ao executar a requisi'#231#227'o: ' + E.Message);
  end;
end;

procedure TfrmChave.FormCreate(Sender: TObject);
begin
  FConfig := TExampleConfig.Default;
  FLicencaAtiva := False;
  FRenovacaoUrl := FConfig.RenewUrl;
  FLembrarCredenciais := True;
  SetLength(FPlanos, 0);
  FTentouAutoLogin := False;
  FCacheFile := TPath.Combine(CacheDirectory, 'license_cache.json');
  FCredentialsFile := TPath.Combine(CacheDirectory, 'credentials_cache.json');
  edtApiUrl.Text := FConfig.ApiUrl;
  edtApiKey.Text := FConfig.ApiKey;
  edtSerial.Text := FConfig.Serial;
  memToken.Text := FConfig.Token;
  edtMac.Text := FConfig.MacAddress;
  edtComputer.Text := FConfig.ComputerName;
  edtEmail.Text := FConfig.Email;
  edtSenha.Text := FConfig.Password;
  edtSoftwareId.Text := FConfig.SoftwareId;
  edtVersao.Text := FConfig.SoftwareVersion;
  edtInstalacao.Text := FConfig.InstalacaoId;
  memChallenge.Text := 'Clique em "Gerar challenge offline" para produzir o texto que deve ser enviado ao suporte quando n'#227'o houver internet.';
  lblStatusResumo.Caption := 'Situa'#231#227'o: aguardando valida'#231#227'o';
  lblStatusExpira.Caption := 'Vencimento: n'#227'o verificado';
  lblStatusMensagem.Caption := 'Aguardando sincroniza'#231#227'o com o servidor.';
  lblAvisoVencimento.Visible := False;
  btnRenovar.Visible := False;
  pnlLoginHint.Visible := False;
  // Carrega credenciais em cache para evitar digitar a cada abertura
  FLembrarCredenciais := True;
  CarregarCredenciais;
  CarregarCache;
  PrepareMemo;
  SincronizarAoIniciar;
end;

procedure TfrmChave.HabilitarInterface(const AEnabled: Boolean);
begin
  btnValidar.Enabled := AEnabled;
  btnLimpar.Enabled := AEnabled;
  if AEnabled then
    Screen.Cursor := crDefault
  else
    Screen.Cursor := crHourGlass;
end;

function TfrmChave.NormalizeToken(const Value: string): string;
begin
  Result := Trim(Value);
  Result := StringReplace(Result, sLineBreak, '', [rfReplaceAll]);
  Result := StringReplace(Result, #13, '', [rfReplaceAll]);
  Result := StringReplace(Result, #10, '', [rfReplaceAll]);
end;

procedure TfrmChave.PrepareMemo;
begin
  memResultado.Lines.Text := 'Aguardando opera'#231#245'es. O aplicativo verifica a licen'#231'a automaticamente ao iniciar.' + sLineBreak +
    'Use "Ativar licen'#231'a" apenas se for solicitado o e-mail e a senha. Para ativa'#231#245'es offline, gere o challenge e compartilhe com o suporte.';
end;

procedure TfrmChave.RenderResponse(const ResponseText: string);
var
  JsonValue: TJSONValue;
  JsonObject: TJSONObject;
  Validacao: TJSONObject;
  TokenFromResponse: string;
begin
  memResultado.Lines.BeginUpdate;
  try
    memResultado.Clear;
    JsonValue := TJSONObject.ParseJSONValue(ResponseText);
    try
      if JsonValue <> nil then
      begin
        memResultado.Lines.Text := JsonValue.ToJSON;
        if JsonValue is TJSONObject then
        begin
          JsonObject := TJSONObject(JsonValue);
          AppendLog('');
          AppendLog('Resumo:');
          AppendLog(' success = ' + BoolToStr(GetJsonBoolValue(JsonObject, 'success', False), True));
          if JsonObject.TryGetValue<TJSONObject>('validacao', Validacao) then
          begin
            AppendLog(' valido = ' + BoolToStr(GetJsonBoolValue(Validacao, 'valido', False), True));
            AppendLog(' erro   = ' + GetJsonStringValue(Validacao, 'erro'));
            AppendLog(' empresa = ' + GetJsonStringValue(Validacao, 'empresa'));
            AppendLog(' software = ' + GetJsonStringValue(Validacao, 'software'));
          end;
          TokenFromResponse := GetJsonStringValue(JsonObject, 'token');
          if TokenFromResponse = '' then
            TokenFromResponse := memToken.Text;
          ProcessarLicencaJson(JsonObject, TokenFromResponse);
        end;
      end
      else
        memResultado.Lines.Text := ResponseText;
    finally
      JsonValue.Free;
    end;
  finally
    memResultado.Lines.EndUpdate;
  end;
end;

procedure TfrmChave.ValidarSerialViaApi(const ASilent: Boolean);
var
  Payload: TJSONObject;
  Content: TStringStream;
  Http: TIdHTTP;
  ResponseText: string;
begin
  HabilitarInterface(False);
  Payload := nil;
  try
    Payload := BuildPayload;
    if ASilent then
      memResultado.Lines.Text := 'Validando licen'#231'a automaticamente...'
    else
      memResultado.Lines.Text := 'Enviando requisi'#231#227'o...';

    Http := CreateHttpClient;
    try
      AppendLog('POST ' + Trim(edtApiUrl.Text));
      Content := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
      try
        ResponseText := Http.Post(Trim(edtApiUrl.Text), Content);
      finally
        Content.Free;
      end;
      RenderResponse(ResponseText);
    finally
      Http.Free;
    end;
  finally
    Payload.Free;
    HabilitarInterface(True);
  end;
end;

function TfrmChave.BuildLoginPayload: TJSONObject;
var
  SoftwareId: Integer;
  EmailValue: string;
  ApiKeyValue: string;
  SenhaValue: string;
begin
  EmailValue := Trim(edtEmail.Text);
  SenhaValue := edtSenha.Text;

  // Usa cache se o usuario nao digitou nesta sessao
  if (EmailValue = '') and (FStoredEmail <> '') then
  begin
    EmailValue := FStoredEmail;
    edtEmail.Text := EmailValue;
  end;
  if (SenhaValue = '') and (FStoredPassword <> '') then
  begin
    SenhaValue := FStoredPassword;
    edtSenha.Text := SenhaValue;
  end;

  if EmailValue = '' then
    raise Exception.Create('Informe o e-mail cadastrado no portal.');
  if SenhaValue = '' then
    raise Exception.Create('Informe a senha do portal.');

  SoftwareId := StrToIntDef(Trim(edtSoftwareId.Text), 0);
  if SoftwareId <= 0 then
    raise Exception.Create('Informe um Software ID num'#233'rico.');
  if Trim(edtInstalacao.Text) = '' then
    raise Exception.Create('Informe o c'#243'digo da instala'#231#227'o (GUID ou hash do dispositivo).');

  ApiKeyValue := Trim(edtApiKey.Text);
  if ApiKeyValue = '' then
    raise Exception.Create('Informe a API KEY fornecida para este software.');

  Result := TJSONObject.Create;
  Result.AddPair('action', 'emitir_token');
  Result.AddPair('email', EmailValue);
  Result.AddPair('senha', SenhaValue);
  Result.AddPair('software_id', TJSONNumber.Create(SoftwareId));
  if Trim(edtVersao.Text) <> '' then
    Result.AddPair('versao_software', Trim(edtVersao.Text));
  Result.AddPair('codigo_instalacao', Trim(edtInstalacao.Text));
  Result.AddPair('api_key', ApiKeyValue);
end;

procedure TfrmChave.GerarTokenViaApi(const ASilent: Boolean);
var
  Payload: TJSONObject;
  Content: TStringStream;
  Http: TIdHTTP;
  ResponseText: string;
begin
  HabilitarInterface(False);
  Payload := nil;
  try
    Payload := BuildLoginPayload;
    if ASilent then
      memResultado.Lines.Text := 'Atualizando token de acesso automaticamente...'
    else
      memResultado.Lines.Text := 'Solicitando token via login...';

    Http := CreateHttpClient;
    try
      AppendLog('Autenticando ' + Trim(edtEmail.Text));
      Content := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
      try
        ResponseText := Http.Post(Trim(edtApiUrl.Text), Content);
      finally
        Content.Free;
      end;
      RenderResponse(ResponseText);
      ApplyTokenResponse(ResponseText);
      try
        ValidarSerialViaApi(True);
      except
        on E: Exception do
          AppendLog('Valida'#231#227'o ap'#243's login falhou: ' + E.Message);
      end;
      if (not FLicencaAtiva) and (not ASilent) then
        ShowMessage(lblStatusMensagem.Caption);

      if FLembrarCredenciais then
      begin
        if Trim(edtEmail.Text) <> '' then
          FStoredEmail := Trim(edtEmail.Text);
        if Trim(edtSenha.Text) <> '' then
          FStoredPassword := edtSenha.Text;
        if FStoredPassword <> '' then
          SalvarCredenciais;
      end
      else
        LimparCredenciaisArmazenadas;
    finally
      Http.Free;
    end;
  finally
    Payload.Free;
    HabilitarInterface(True);
  end;
end;

procedure TfrmChave.ApplyTokenResponse(const ResponseText: string);
var
  JsonValue: TJSONValue;
  Root: TJSONObject;
  Licenca: TJSONObject;
  TokenValue: string;
begin
  JsonValue := TJSONObject.ParseJSONValue(ResponseText);
  try
    if not (JsonValue is TJSONObject) then
      Exit;
    Root := TJSONObject(JsonValue);

    TokenValue := GetJsonStringValue(Root, 'token');
    if TokenValue <> '' then
    begin
      memToken.Text := TokenValue;
      AppendLog('Token atualizado a partir do login.');
    end;

    if Root.TryGetValue<TJSONObject>('licenca', Licenca) then
    begin
      if Trim(edtSerial.Text) = '' then
        edtSerial.Text := GetJsonStringValue(Licenca, 'serial');
      if Trim(edtSoftwareId.Text) = '' then
        edtSoftwareId.Text := GetJsonStringValue(Licenca, 'software_id');
      if Trim(edtVersao.Text) = '' then
        edtVersao.Text := GetJsonStringValue(Licenca, 'versao');
    end;

    ProcessarLicencaJson(Root, TokenValue);
  finally
    JsonValue.Free;
  end;
  MaybeForceCheckVencimento;
end;

function TfrmChave.CreateHttpClient: TIdHTTP;
var
  SSL: TIdSSLIOHandlerSocketOpenSSL;
begin
  Result := TIdHTTP.Create(nil);
  SSL := TIdSSLIOHandlerSocketOpenSSL.Create(Result);
  SSL.SSLOptions.Mode := sslmClient;
  SSL.SSLOptions.SSLVersions := [sslvTLSv1_2];
  Result.IOHandler := SSL;
  Result.ConnectTimeout := 15000;
  Result.ReadTimeout := 15000;
  Result.HandleRedirects := True;
  Result.Request.Accept := 'application/json';
  Result.Request.ContentType := 'application/json; charset=utf-8';
end;

function TPlanResumo.Display: string;
begin
  Result := Format('%d mes(es) - R$ %.2f (%s)', [Recorrencia, Valor, Nome]);
end;

function TfrmChave.ApiBaseUrl: string;
const
  VALID_PATH = 'api_validacao.php';
begin
  Result := Trim(edtApiUrl.Text);
  if Result.EndsWith(VALID_PATH, True) then
    Delete(Result, Length(Result) - Length(VALID_PATH) + 1, Length(VALID_PATH));
  if (Result <> '') and (Result[Length(Result)] <> '/') then
    Result := Result + '/';
end;

function TfrmChave.HttpPostJson(const AUrl, ABody, ABearer: string): string;
var
  Http: TIdHTTP;
  Stream: TStringStream;
begin
  Result := '';
  Http := CreateHttpClient;
  Stream := TStringStream.Create(ABody, TEncoding.UTF8);
  try
    if ABearer <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + ABearer;
    Result := Http.Post(AUrl, Stream);
  finally
    Stream.Free;
    Http.Free;
  end;
end;

function TfrmChave.HttpGetJson(const AUrl, ABearer: string): string;
var
  Http: TIdHTTP;
begin
  Result := '';
  Http := CreateHttpClient;
  try
    if ABearer <> '' then
      Http.Request.CustomHeaders.Values['Authorization'] := 'Bearer ' + ABearer;
    Result := Http.Get(AUrl);
  finally
    Http.Free;
  end;
end;

function TfrmChave.LoginTokenPedido(out AToken: string): Boolean;
var
  Url: string;
  Body, Resp: string;
  Json: TJSONObject;
  EmailLogin: string;
  SenhaLogin: string;
begin
  Result := False;
  AToken := '';
  EmailLogin := Trim(edtEmail.Text);
  SenhaLogin := edtSenha.Text;

  // Usa cache se o usuario nao digitou nesta sessao
  if (EmailLogin = '') and (FStoredEmail <> '') then
  begin
    EmailLogin := FStoredEmail;
    if Trim(edtEmail.Text) = '' then
      edtEmail.Text := EmailLogin;
  end;
  if (SenhaLogin = '') and (FStoredPassword <> '') then
  begin
    SenhaLogin := FStoredPassword;
    if edtSenha.Text = '' then
      edtSenha.Text := SenhaLogin;
  end;

  // Usa credenciais de servico configuradas apenas se nada mais estiver disponivel
  if EmailLogin = '' then
    EmailLogin := Trim(FConfig.PedidoLogin);
  if SenhaLogin = '' then
    SenhaLogin := FConfig.PedidoSenha;

  if EmailLogin = '' then
    raise Exception.Create('Configure o e-mail de servi'#231'o para gerar o pedido.');
  if SenhaLogin = '' then
    raise Exception.Create('Configure a senha de servi'#231'o para gerar o pedido.');

  Url := ApiBaseUrl + 'api_login_token.php';
  Body := Format('{"login":"%s","senha":"%s"}',
    [StringReplace(EmailLogin, '"', '""', [rfReplaceAll]),
     StringReplace(SenhaLogin, '"', '""', [rfReplaceAll])]);
  AppendLog('Autenticando pedido com login ' + EmailLogin);
  try
    Resp := HttpPostJson(Url, Body, '');
  except
    on E: EIdHTTPProtocolException do
    begin
      AppendLog('Falha ao autenticar pedido: ' + E.Message);
      if E.ErrorMessage <> '' then
        AppendLog('Resposta: ' + E.ErrorMessage);
      Exit(False);
    end;
    on E: Exception do
    begin
      AppendLog('Falha ao autenticar pedido: ' + E.Message);
      Exit(False);
    end;
  end;

  Json := TJSONObject.ParseJSONValue(Resp) as TJSONObject;
  try
    if Json <> nil then
    begin
      AToken := GetJsonStringValue(Json, 'token');
      Result := AToken <> '';
      if not Result then
        AppendLog('Resposta sem token: ' + Resp);
    end
    else
      AppendLog('Resposta inesperada ao autenticar pedido: ' + Resp);
  finally
    Json.Free;
  end;
end;

function TfrmChave.CriarPedidoApp(const AToken: string; APlanoId: Integer;
  const ALicencaId, ALicencaSerial, ASoftwareNome: string; out ACodTransacao: string): Boolean;
var
  Url, Body, Resp: string;
  Json: TJSONObject;
begin
  Result := False;
  ACodTransacao := '';
  Url := ApiBaseUrl + 'api_pedido.php';
  Body := '{"plan_id":' + APlanoId.ToString;
  if Trim(ALicencaId) <> '' then
    Body := Body + ',"licenca_id":"' + StringReplace(ALicencaId, '"', '\"', [rfReplaceAll]) + '"';
  if Trim(ALicencaSerial) <> '' then
    Body := Body + ',"licenca_serial":"' + StringReplace(ALicencaSerial, '"', '\"', [rfReplaceAll]) + '"';
  if Trim(ASoftwareNome) <> '' then
    Body := Body + ',"software":"' + StringReplace(ASoftwareNome, '"', '\"', [rfReplaceAll]) + '"';
  Body := Body + '}';

  Resp := HttpPostJson(Url, Body, AToken);
  Json := TJSONObject.ParseJSONValue(Resp) as TJSONObject;
  try
    if Json <> nil then
    begin
      ACodTransacao := GetJsonStringValue(Json, 'cod_transacao');
      Result := ACodTransacao <> '';
    end;
  finally
    Json.Free;
  end;
end;

function TfrmChave.ObterPagamentoUrl(const AToken, ACodTransacao: string;
  out APaymentUrl: string; out APago: Boolean): Boolean;
var
  Url, Resp: string;
  Json: TJSONObject;
begin
  Result := False;
  APaymentUrl := '';
  APago := False;
  Url := ApiBaseUrl + 'api_pagamento.php?cod_transacao=' + TNetEncoding.URL.Encode(ACodTransacao);
  Resp := HttpGetJson(Url, AToken);
  Json := TJSONObject.ParseJSONValue(Resp) as TJSONObject;
  try
    if Json <> nil then
    begin
      if SameText(GetJsonStringValue(Json, 'status'), 'pago') then
      begin
        APago := True;
        Result := True;
        Exit;
      end;
      APaymentUrl := GetJsonStringValue(Json, 'payment_url');
      Result := APaymentUrl <> '';
    end;
  finally
    Json.Free;
  end;
end;

function TfrmChave.ListarPlanosApp(const AToken, ASoftwareId, ALicencaId: string;
  out APlanos: TArray<TPlanResumo>): Boolean;
var
  Url, Resp: string;
  Json: TJSONObject;
  Arr: TJSONArray;
  I: Integer;
  Item: TJSONObject;
  tmp: TList<TPlanResumo>;
  P: TPlanResumo;
begin
  Result := False;
  APlanos := [];
  Url := ApiBaseUrl + 'api_planos.php';
  if ALicencaId <> '' then
    Url := Url + '?licenca_id=' + TNetEncoding.URL.Encode(ALicencaId)
  else if ASoftwareId <> '' then
    Url := Url + '?software_id=' + TNetEncoding.URL.Encode(ASoftwareId);

  Resp := HttpGetJson(Url, AToken);
  Json := TJSONObject.ParseJSONValue(Resp) as TJSONObject;
  tmp := TList<TPlanResumo>.Create;
  try
    if (Json <> nil) and Json.TryGetValue<TJSONArray>('planos', Arr) then
    begin
      for I := 0 to Arr.Count - 1 do
        if Arr.Items[I] is TJSONObject then
        begin
          Item := TJSONObject(Arr.Items[I]);
          P.Id := GetJsonIntegerValue(Item, 'id', 0);
          P.Recorrencia := GetJsonIntegerValue(Item, 'recorrencia', 0);
          P.Valor := StrToFloatDef(GetJsonStringValue(Item, 'valor'), 0);
          P.Nome := GetJsonStringValue(Item, 'nome_plano');
          if P.Id > 0 then
            tmp.Add(P);
        end;
      APlanos := tmp.ToArray;
      Result := Length(APlanos) > 0;
    end;
  finally
    tmp.Free;
    Json.Free;
  end;
end;

procedure TfrmChave.PopularPlanosCombo(const APlanos: TArray<TPlanResumo>);
var
  P: TPlanResumo;
begin
  cbPlanos.Items.BeginUpdate;
  try
    cbPlanos.Items.Clear;
    for P in APlanos do
      cbPlanos.Items.AddObject(P.Display, TObject(P.Id));
  finally
    cbPlanos.Items.EndUpdate;
  end;
  if cbPlanos.Items.Count > 0 then
    cbPlanos.ItemIndex := 0;
end;

function TfrmChave.PlanoSelecionadoId: Integer;
begin
  Result := 0;
  if (cbPlanos.ItemIndex >= 0) and (cbPlanos.ItemIndex < cbPlanos.Items.Count) then
    Result := Integer(cbPlanos.Items.Objects[cbPlanos.ItemIndex]);
end;

procedure TfrmChave.EnsureOfflineSecret;
begin
  if Trim(FConfig.OfflineSecret) = '' then
    raise Exception.Create('Defina um segredo offline em Example.Config para assinar o challenge.');
end;

procedure TfrmChave.GerarChallengeOffline;
var
  Payload: TJSONObject;
  PayloadJson: string;
  Encoded: string;
  Signature: string;
begin
  EnsureOfflineSecret;
  Payload := nil;
  try
    Payload := BuildOfflineChallengePayload;
    PayloadJson := Payload.ToJSON;
    Encoded := EncodeBase64Url(PayloadJson);
    Signature := THashSHA2.GetHMAC(Encoded, FConfig.OfflineSecret, THashSHA2.TSHA2Version.SHA256);
    memChallenge.Text := Encoded + '.' + Signature;
    AppendLog('Challenge offline gerado. Envie o texto acima ao suporte para obter o token.');
  finally
    Payload.Free;
  end;
end;

function TfrmChave.EncodeBase64Url(const Text: string): string;
var
  Encoded: string;
begin
  Encoded := TNetEncoding.Base64.Encode(Text);
  Encoded := StringReplace(Encoded, '+', '-', [rfReplaceAll]);
  Encoded := StringReplace(Encoded, '/', '_', [rfReplaceAll]);
  while (Length(Encoded) > 0) and (Encoded[Length(Encoded)] = '=') do
    SetLength(Encoded, Length(Encoded) - 1);
  Result := Encoded;
end;

procedure TfrmChave.SincronizarAoIniciar;
begin
  FTentouAutoLogin := False;
  FForcedCheck := False;
  if (Trim(memToken.Text) <> '') or (Trim(edtSerial.Text) <> '') then
  begin
    try
      ValidarSerialViaApi(True);
      if not FLicencaAtiva then
        if not TentarLoginAutomatico then
          MostrarPainelLogin(True);
    except
      on E: Exception do
      begin
        AppendLog('Valida'#231#227'o autom'#225'tica falhou: ' + E.Message);
        if not TentarLoginAutomatico then
          MostrarPainelLogin(True);
      end;
    end;
  end
  else if not TentarLoginAutomatico then
    MostrarPainelLogin(True);

  MaybeForceCheckVencimento;
end;

procedure TfrmChave.MaybeForceCheckVencimento;
begin
  if FForcedCheck then
    Exit;
  if AnsiContainsText(lblStatusExpira.Caption, 'n'#227'o verificado') or
     AnsiContainsText(lblStatusExpira.Caption, 'nao verificado') then
  begin
    FForcedCheck := True;
    try
      ValidarSerialViaApi(True);
    except
      on E: Exception do
        AppendLog('Verifica'#231#227'o for'#231'ada falhou: ' + E.Message);
    end;
  end;
end;

procedure TfrmChave.CarregarCache;
var
  JsonText: string;
  Root: TJSONObject;
  EmailCache: string;
begin
  if (FCacheFile = '') or not TFile.Exists(FCacheFile) then
    Exit;
  try
    JsonText := TFile.ReadAllText(FCacheFile, TEncoding.UTF8);
    Root := TJSONObject.ParseJSONValue(JsonText) as TJSONObject;
    try
      if Root = nil then
        Exit;
      EmailCache := GetJsonStringValue(Root, 'email');
      if (FStoredEmail = '') and (EmailCache <> '') then
        FStoredEmail := EmailCache;
      if (Trim(edtEmail.Text) = '') and (EmailCache <> '') then
        edtEmail.Text := EmailCache;
      if Trim(edtSerial.Text) = '' then
        edtSerial.Text := GetJsonStringValue(Root, 'serial');
      if Trim(edtSoftwareId.Text) = '' then
        edtSoftwareId.Text := GetJsonStringValue(Root, 'software_id');
      if Trim(memToken.Text) = '' then
        memToken.Text := GetJsonStringValue(Root, 'token');
      FRenovacaoUrl := GetJsonStringValue(Root, 'renovacao_url');
      if FRenovacaoUrl = '' then
        FRenovacaoUrl := FConfig.RenewUrl;

      // Garante campos preenchidos apos salvar/recuperar
      if (FStoredEmail <> '') and (Trim(edtEmail.Text) = '') then
        edtEmail.Text := FStoredEmail;
      if (FStoredPassword <> '') and (edtSenha.Text = '') then
        edtSenha.Text := FStoredPassword;
    finally
      Root.Free;
    end;
  except
    on E: Exception do
      AppendLog('Falha ao carregar o cache da licen'#231'a: ' + E.Message);
  end;
end;

procedure TfrmChave.CarregarCredenciais;
var
  JsonText: string;
  Root: TJSONObject;
  SenhaProtegida: string;
begin
  if (FCredentialsFile = '') or not TFile.Exists(FCredentialsFile) then
    Exit;
  try
    JsonText := TFile.ReadAllText(FCredentialsFile, TEncoding.UTF8);
    Root := TJSONObject.ParseJSONValue(JsonText) as TJSONObject;
    try
      if Root = nil then
        Exit;
      FLembrarCredenciais := GetJsonBoolValue(Root, 'lembrar', True);
      FStoredEmail := GetJsonStringValue(Root, 'email');
      if (Trim(edtEmail.Text) = '') and (FStoredEmail <> '') then
        edtEmail.Text := FStoredEmail;
      if FLembrarCredenciais then
      begin
        SenhaProtegida := GetJsonStringValue(Root, 'senha');
        if SenhaProtegida <> '' then
        begin
          try
            FStoredPassword := UnprotectString(SenhaProtegida);
            if (edtSenha.Text = '') and (FStoredPassword <> '') then
              edtSenha.Text := FStoredPassword;
          except
            on E: Exception do
            begin
              AppendLog('Falha ao descriptografar credenciais: ' + E.Message);
              FStoredPassword := '';
            end;
          end;
        end;
      end
      else
        FStoredPassword := '';
    finally
      Root.Free;
    end;
  except
    on E: Exception do
      AppendLog('Falha ao carregar credenciais: ' + E.Message);
  end;
end;

procedure TfrmChave.SalvarCache(const Token: string; const Licenca: TJSONObject);
var
  CacheObj: TJSONObject;
  ExpiraTexto: string;
  EmailCache: string;
begin
  if (Token = '') or (FCacheFile = '') then
    Exit;
  CacheObj := TJSONObject.Create;
  try
    if Trim(edtEmail.Text) <> '' then
      EmailCache := edtEmail.Text
    else
      EmailCache := FStoredEmail;
    CacheObj.AddPair('email', EmailCache);
    CacheObj.AddPair('serial', edtSerial.Text);
    CacheObj.AddPair('software_id', edtSoftwareId.Text);
    CacheObj.AddPair('token', Token);
    ExpiraTexto := GetJsonStringValue(Licenca, 'data_expiracao');
    if ExpiraTexto = '' then
      ExpiraTexto := GetJsonStringValue(Licenca, 'expira_em');
    if ExpiraTexto = '' then
      ExpiraTexto := GetJsonStringValue(Licenca, 'vencimento');
    if ExpiraTexto = '' then
      ExpiraTexto := GetJsonStringValue(Licenca, 'data_vencimento');
    if ExpiraTexto = '' then
      ExpiraTexto := GetJsonStringValue(Licenca, 'data_validade');
    CacheObj.AddPair('expira_em', ExpiraTexto);
    CacheObj.AddPair('status', GetJsonStringValue(Licenca, 'status'));
    CacheObj.AddPair('renovacao_url', FRenovacaoUrl);
    ForceDirectories(ExtractFilePath(FCacheFile));
    TFile.WriteAllText(FCacheFile, CacheObj.ToJSON, TEncoding.UTF8);
  finally
    CacheObj.Free;
  end;

  if FLembrarCredenciais then
    SalvarCredenciais
  else
    LimparCredenciaisArmazenadas;
end;

  procedure TfrmChave.SalvarCredenciais;
  var
    Root: TJSONObject;
  begin
    if FCredentialsFile = '' then
      Exit;
    if not FLembrarCredenciais then
    begin
      LimparCredenciaisArmazenadas;
      Exit;
    end;

    Root := TJSONObject.Create;
    try
      if FStoredEmail <> '' then
        Root.AddPair('email', FStoredEmail);
      Root.AddPair('lembrar', TJSONBool.Create(True));
      if FStoredPassword <> '' then
      begin
        try
          Root.AddPair('senha', ProtectString(FStoredPassword));
        except
          on E: Exception do
          begin
            AppendLog('Falha ao proteger credenciais: ' + E.Message);
            Root.AddPair('senha', '');
          end;
        end;
      end;
      ForceDirectories(ExtractFilePath(FCredentialsFile));
      TFile.WriteAllText(FCredentialsFile, Root.ToJSON, TEncoding.UTF8);
    finally
      Root.Free;
    end;
  end;

procedure TfrmChave.LimparCache(const LimparCampos: Boolean;
  const RemoverCredenciais: Boolean);
begin
  if (FCacheFile <> '') and TFile.Exists(FCacheFile) then
  begin
    try
      TFile.Delete(FCacheFile);
    except
      on E: Exception do
        AppendLog('Falha ao remover cache: ' + E.Message);
    end;
  end;

  if RemoverCredenciais then
  begin
    FLembrarCredenciais := True;
    LimparCredenciaisArmazenadas;
  end;
  if LimparCampos then
  begin
    memToken.Clear;
    edtSerial.Clear;
  end;
end;

procedure TfrmChave.LimparCredenciaisArmazenadas;
begin
  if (FCredentialsFile <> '') and TFile.Exists(FCredentialsFile) then
  begin
    try
      TFile.Delete(FCredentialsFile);
    except
      on E: Exception do
        AppendLog('Falha ao remover credenciais salvas: ' + E.Message);
    end;
  end;
  FStoredEmail := '';
  FStoredPassword := '';
end;

procedure TfrmChave.ProcessarLicencaJson(const Root: TJSONObject;
  const TokenValue: string);
var
  ValidacaoObj: TJSONObject;
  LicencaObj: TJSONObject;
begin
  if Root = nil then
    Exit;
  if Root.TryGetValue<TJSONObject>('validacao', ValidacaoObj) then
  begin
    AtualizarEstadoLicenca(ValidacaoObj, TokenValue);
    if ValidacaoObj.TryGetValue<TJSONObject>('licenca', LicencaObj) then
      AtualizarEstadoLicenca(LicencaObj, TokenValue);
  end;
  if Root.TryGetValue<TJSONObject>('licenca', LicencaObj) then
    AtualizarEstadoLicenca(LicencaObj, TokenValue);
end;

procedure TfrmChave.AtualizarEstadoLicenca(const Fonte: TJSONObject;
  const TokenValue: string);
var
  StatusTexto: string;
  ExpiraTexto: string;
  Mensagem: string;
  ExpiraData: TDateTime;
  DiasAviso: Integer;
  DiasRestantesOverride: Integer;
  TokenParaSalvar: string;
  LicencaValida: Boolean;
  RenovacaoUrl: string;
  TerminaisPermitidos: Integer;
  TerminaisUtilizados: Integer;
begin
  if Fonte = nil then
    Exit;
  StatusTexto := GetJsonStringValue(Fonte, 'status');
  LicencaValida := GetJsonBoolValue(Fonte, 'valido', SameText(StatusTexto, 'ativo'));
  if StatusTexto = '' then
    StatusTexto := IfThen(LicencaValida, 'ativo', 'inativo');
  ExpiraTexto := GetJsonStringValue(Fonte, 'data_expiracao');
  if ExpiraTexto = '' then
    ExpiraTexto := GetJsonStringValue(Fonte, 'expira_em');
  if ExpiraTexto = '' then
    ExpiraTexto := GetJsonStringValue(Fonte, 'vencimento');
  if ExpiraTexto = '' then
    ExpiraTexto := GetJsonStringValue(Fonte, 'data_vencimento');
  if ExpiraTexto = '' then
    ExpiraTexto := GetJsonStringValue(Fonte, 'data_validade');
  if not TryParseIsoDate(ExpiraTexto, ExpiraData) then
    ExpiraData := 0;
  Mensagem := GetJsonStringValue(Fonte, 'mensagem');
  if Mensagem = '' then
    Mensagem := GetJsonStringValue(Fonte, 'mensagem_status');
  if Mensagem = '' then
    Mensagem := GetJsonStringValue(Fonte, 'erro');
  if Mensagem = '' then
  begin
    if LicencaValida then
      Mensagem := 'Licen'#231'a validada com sucesso.'
    else
      Mensagem := 'Licen'#231'a inativa. Revise o acesso.';
  end;

  DiasAviso := GetJsonIntegerValue(Fonte, 'dias_aviso', FConfig.AlertDays);
  if DiasAviso <= 0 then
    DiasAviso := FConfig.AlertDays;
  DiasRestantesOverride := GetJsonIntegerValue(Fonte, 'dias_restantes', -1);
  RenovacaoUrl := GetJsonStringValue(Fonte, 'renovacao_url');
  if RenovacaoUrl <> '' then
    FRenovacaoUrl := RenovacaoUrl
  else if FRenovacaoUrl = '' then
    FRenovacaoUrl := FConfig.RenewUrl;

  TerminaisPermitidos := GetJsonIntegerValue(Fonte, 'terminais_permitidos', 0);
  TerminaisUtilizados := GetJsonIntegerValue(Fonte, 'terminais_utilizados', 0);

  AtualizarStatusVisual(StatusTexto, Mensagem, ExpiraData, DiasAviso, DiasRestantesOverride, LicencaValida, ExpiraTexto);
  AtualizarAvisoTerminais(TerminaisPermitidos, TerminaisUtilizados, Mensagem);

  TokenParaSalvar := TokenValue;
  if TokenParaSalvar = '' then
    TokenParaSalvar := memToken.Text;

  if LicencaValida and (TokenParaSalvar <> '') then
    SalvarCache(TokenParaSalvar, Fonte)
  else if not LicencaValida then
    LimparCache(False);
end;

procedure TfrmChave.AtualizarStatusVisual(const StatusTexto, Mensagem: string;
  const ExpiraEm: TDateTime; const DiasAviso, DiasRestantesOverride: Integer;
  const LicencaValida: Boolean; const ExpiraTexto: string);
var
  DiasRestantes: Integer;
  StatusDisplay: string;
  VencimentoTexto: string;
begin
  FLicencaAtiva := LicencaValida;
  if LicencaValida then
    StatusDisplay := 'ATIVO'
  else if StatusTexto <> '' then
    StatusDisplay := UpperCase(StatusTexto)
  else
    StatusDisplay := 'INATIVO';
  lblStatusResumo.Caption := 'Situa'#231#227'o: ' + StatusDisplay;
  if ExpiraEm > 0 then
  begin
    VencimentoTexto := FormatDateTime('dd/mm/yyyy', ExpiraEm);
    DiasRestantes := Trunc(ExpiraEm - Now);
  end
  else
  begin
    VencimentoTexto := Trim(ExpiraTexto);
    DiasRestantes := DiasRestantesOverride;
  end;

  if VencimentoTexto <> '' then
    lblStatusExpira.Caption := 'Vencimento: ' + VencimentoTexto
  else
    lblStatusExpira.Caption := 'Vencimento: n'#227'o informado';

  if (DiasRestantesOverride >= 0) and (ExpiraEm > 0) then
    DiasRestantes := Max(DiasRestantes, DiasRestantesOverride);

  lblStatusMensagem.Caption := Mensagem;
  lblAvisoVencimento.Visible := False;
  btnRenovar.Visible := False;

  if FLicencaAtiva and (DiasAviso > 0) and (DiasRestantes >= 0) and (DiasRestantes <= DiasAviso) then
  begin
    lblAvisoVencimento.Caption := Format('Aten'#231#227'o: faltam %d dia(s) para o vencimento. Clique em Renovar.', [DiasRestantes]);
    lblAvisoVencimento.Visible := True;
    btnRenovar.Visible := True;
  end
  else if not FLicencaAtiva then
  begin
    if DiasRestantes < 0 then
      lblStatusExpira.Caption := 'Vencimento: expirado';
    lblAvisoVencimento.Caption := Mensagem;
    lblAvisoVencimento.Visible := True;
    btnRenovar.Visible := True;
  end;

  MostrarPainelLogin(not FLicencaAtiva);
end;

procedure TfrmChave.AtualizarAvisoTerminais(const Permitidos,
  Utilizados: Integer; var Mensagem: string);
begin
  btnGerenciarTerminais.Visible := True;
  btnGerenciarTerminais.Enabled := FLicencaAtiva or (Permitidos > 0) or (Utilizados > 0);

  if (Permitidos <= 0) then
    Exit;

  if Utilizados < Permitidos then
    Exit;

  if Pos('terminal', AnsiLowerCase(Mensagem)) = 0 then
    Mensagem := Mensagem + ' Limite de terminais atingido.';

  lblAvisoVencimento.Caption := 'Limite de terminais atingido. Use "Gerenciar instala'#231#245'es" para remover um dispositivo.';
  lblAvisoVencimento.Visible := True;
  btnRenovar.Visible := False;
end;

function TfrmChave.CacheDirectory: string;
begin
  Result := TPath.Combine(TPath.GetHomePath, 'ShieldLicenca');
  ForceDirectories(Result);
end;

procedure TfrmChave.MostrarPainelLogin(const Mostrar: Boolean);
begin
  pnlLoginHint.Visible := Mostrar;
  if Mostrar and HandleAllocated then
    ActiveControl := edtEmail;
end;

function TfrmChave.TryParseIsoDate(const Value: string;
  out DateValue: TDateTime): Boolean;
var
  S: string;
begin
  Result := False;
  DateValue := 0;
  S := Trim(Value);
  if S = '' then
    Exit;
  try
    DateValue := ISO8601ToDate(S, False);
    Exit(True);
  except
    on E: EConvertError do
      Result := False;
  end;

  if Pos(' ', S) > 0 then
  begin
    try
      DateValue := ISO8601ToDate(StringReplace(S, ' ', 'T', [rfReplaceAll]), False);
      Exit(True);
    except
      on E: EConvertError do
        Result := False;
    end;
  end;

  Result := TryStrToDateTime(S, DateValue) or TryStrToDate(S, DateValue);
end;

function TfrmChave.ProtectString(const AValue: string): string;
var
  DataIn, DataOut: DATA_BLOB;
  InputBytes: TBytes;
  ProtectedBytes: TBytes;
begin
  Result := '';
  if AValue = '' then
    Exit;
  InputBytes := TEncoding.UTF8.GetBytes(AValue);
  if Length(InputBytes) = 0 then
    Exit;
  DataIn.cbData := Length(InputBytes);
  DataIn.pbData := @InputBytes[0];
  DataOut.cbData := 0;
  DataOut.pbData := nil;
  if not CryptProtectData(@DataIn, 'ShieldLicenca', nil, nil, nil, 0, @DataOut) then
    raise Exception.CreateFmt('Falha ao proteger dados (%s).', [SysErrorMessage(GetLastError)]);
  try
    SetLength(ProtectedBytes, DataOut.cbData);
    Move(DataOut.pbData^, ProtectedBytes[0], DataOut.cbData);
    Result := TNetEncoding.Base64.EncodeBytesToString(ProtectedBytes);
  finally
    if DataOut.pbData <> nil then
      LocalFree(HLOCAL(DataOut.pbData));
  end;
end;

function TfrmChave.UnprotectString(const AValue: string): string;
var
  DataIn, DataOut: DATA_BLOB;
  Buffer: TBytes;
  PlainBytes: TBytes;
begin
  Result := '';
  if AValue = '' then
    Exit;
  Buffer := TNetEncoding.Base64.DecodeStringToBytes(AValue);
  if Length(Buffer) = 0 then
    Exit;
  DataIn.cbData := Length(Buffer);
  DataIn.pbData := @Buffer[0];
  DataOut.cbData := 0;
  DataOut.pbData := nil;
  if not CryptUnprotectData(@DataIn, nil, nil, nil, nil, 0, @DataOut) then
    raise Exception.CreateFmt('Falha ao descriptografar dados (%s).', [SysErrorMessage(GetLastError)]);
  try
    SetLength(PlainBytes, DataOut.cbData);
    Move(DataOut.pbData^, PlainBytes[0], DataOut.cbData);
    Result := TEncoding.UTF8.GetString(PlainBytes);
  finally
    if DataOut.pbData <> nil then
      LocalFree(HLOCAL(DataOut.pbData));
  end;
end;

procedure TfrmChave.btnRenovarClick(Sender: TObject);
var
  Url: string;
begin
  Url := FRenovacaoUrl;
  if Url = '' then
    Url := FConfig.RenewUrl;
  if Url = '' then
    Url := 'https://express.adassoft.com/licencas/renovar';
  ShellExecute(0, 'open', PChar(Url), nil, nil, SW_SHOWNORMAL);
end;

procedure TfrmChave.btnGerenciarTerminaisClick(Sender: TObject);
var
  Lista: TArray<TTerminalResumo>;
  TotaisResumo: string;
  Selecionado: TTerminalResumo;
begin
  if not EnsureLicencaAtiva then
    Exit;

  if NormalizeToken(memToken.Text) = '' then
  begin
    ShowMessage('Gere um token atual antes de gerenciar as instala'#231#245'es (use "Ativar licen'#231'a").');
    Exit;
  end;

  if not SolicitarListaTerminais(Lista, TotaisResumo) then
    Exit;

  if Length(Lista) = 0 then
  begin
    ShowMessage('Nenhuma instala'#231#227'o ativa foi encontrada para esta licen'#231'a.');
    Exit;
  end;

  if TfrmGerenciarTerminais.Executar(Lista, TotaisResumo, Selecionado) then
  begin
    if ExecutarRemocaoTerminal(Selecionado.TerminalId, Selecionado.InstalacaoId) then
    begin
      ShowMessage('Instala'#231#227'o removida. Valide a licen'#231'a novamente para utilizar este computador.');
      ValidarSerialViaApi(True);
    end;
  end;
end;

function TfrmChave.SolicitarListaTerminais(out Lista: TArray<TTerminalResumo>;
  out TotaisResumo: string): Boolean;
var
  Payload: TJSONObject;
  Http: TIdHTTP;
  Content: TStringStream;
  ResponseText: string;
  JsonValue: TJSONValue;
  Root: TJSONObject;
  TerminaisArray: TJSONArray;
  Item: TJSONObject;
  Registro: TTerminalResumo;
  I: Integer;
  Utilizados: Integer;
  Permitidos: Integer;
  MensagemErro: string;
begin
  Result := False;
  SetLength(Lista, 0);
  TotaisResumo := '';

  try
    Payload := TJSONObject.Create;
    try
      Payload.AddPair('action', 'listar_terminais');
      Payload.AddPair('token', NormalizeToken(memToken.Text));
      Payload.AddPair('api_key', Trim(edtApiKey.Text));
      Payload.AddPair('software_id', TJSONNumber.Create(StrToIntDef(Trim(edtSoftwareId.Text), 0)));

      Http := CreateHttpClient;
      try
        Content := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
        try
          ResponseText := Http.Post(Trim(edtApiUrl.Text), Content);
        finally
          Content.Free;
        end;
      finally
        Http.Free;
      end;
    finally
      Payload.Free;
    end;

    JsonValue := TJSONObject.ParseJSONValue(ResponseText);
    try
      if not (JsonValue is TJSONObject) then
        raise Exception.Create('Resposta inesperada da API ao listar instala'#231#245'es.');

      Root := TJSONObject(JsonValue);
      if not GetJsonBoolValue(Root, 'success', False) then
      begin
        MensagemErro := GetJsonStringValue(Root, 'error');
        if MensagemErro = '' then
          MensagemErro := GetJsonStringValue(Root, 'mensagem');
        if MensagemErro = '' then
          MensagemErro := 'N'#227'o foi poss'#237'vel listar as instala'#231#245'es vinculadas.';
        raise Exception.Create(MensagemErro);
      end;

      TerminaisArray := Root.GetValue<TJSONArray>('terminais');
      if TerminaisArray <> nil then
      begin
        SetLength(Lista, TerminaisArray.Count);
        for I := 0 to TerminaisArray.Count - 1 do
        begin
          Registro := Default(TTerminalResumo);
          Item := TerminaisArray.Items[I] as TJSONObject;
          Registro.TerminalId := GetJsonIntegerValue(Item, 'terminal_id', 0);
          Registro.InstalacaoId := GetJsonStringValue(Item, 'instalacao_id');
          Registro.NomeComputador := GetJsonStringValue(Item, 'nome_computador');
          if Registro.NomeComputador = '' then
            Registro.NomeComputador := GetJsonStringValue(Item, 'hostname');
          Registro.MacAddress := GetJsonStringValue(Item, 'mac_address');
          Registro.UltimaAtividade := GetJsonStringValue(Item, 'ultima_atividade');
          Registro.UltimoRegistro := GetJsonStringValue(Item, 'ultimo_registro');
          if Registro.UltimaAtividade = '' then
            Registro.UltimaAtividade := Registro.UltimoRegistro;
          Registro.Ativo := GetJsonBoolValue(Item, 'ativo', True);
          Lista[I] := Registro;
        end;
      end;

      Utilizados := GetJsonIntegerValue(Root, 'terminais_utilizados', 0);
      Permitidos := GetJsonIntegerValue(Root, 'terminais_permitidos', 0);
      if Permitidos > 0 then
        TotaisResumo := Format('Terminais em uso: %d de %d', [Utilizados, Permitidos])
      else
        TotaisResumo := Format('Terminais em uso: %d', [Utilizados]);

      Result := True;
    finally
      JsonValue.Free;
    end;
  except
    on E: Exception do
      ShowMessage('N'#227'o foi poss'#237'vel listar as instala'#231#245'es: ' + E.Message);
  end;
end;

function TfrmChave.ExecutarRemocaoTerminal(const TerminalId: Integer;
  const InstalacaoId: string): Boolean;
var
  Payload: TJSONObject;
  Http: TIdHTTP;
  Content: TStringStream;
  ResponseText: string;
  JsonValue: TJSONValue;
  Root: TJSONObject;
  Mensagem: string;
begin
  Result := False;
  try
    Payload := TJSONObject.Create;
    try
      Payload.AddPair('action', 'remover_terminal');
      Payload.AddPair('token', NormalizeToken(memToken.Text));
      Payload.AddPair('api_key', Trim(edtApiKey.Text));
      Payload.AddPair('software_id', TJSONNumber.Create(StrToIntDef(Trim(edtSoftwareId.Text), 0)));
      if TerminalId > 0 then
        Payload.AddPair('terminal_id', TJSONNumber.Create(TerminalId));
      if Trim(InstalacaoId) <> '' then
        Payload.AddPair('instalacao_id', Trim(InstalacaoId));

      Http := CreateHttpClient;
      try
        Content := TStringStream.Create(Payload.ToJSON, TEncoding.UTF8);
        try
          ResponseText := Http.Post(Trim(edtApiUrl.Text), Content);
        finally
          Content.Free;
        end;
      finally
        Http.Free;
      end;
    finally
      Payload.Free;
    end;

    JsonValue := TJSONObject.ParseJSONValue(ResponseText);
    try
      if not (JsonValue is TJSONObject) then
        raise Exception.Create('Resposta inesperada ao remover a instala'#231#227'o.');

      Root := TJSONObject(JsonValue);
      if not GetJsonBoolValue(Root, 'success', False) then
      begin
        Mensagem := GetJsonStringValue(Root, 'error');
        if Mensagem = '' then
          Mensagem := GetJsonStringValue(Root, 'mensagem');
        if Mensagem = '' then
          Mensagem := 'N'#227'o foi poss'#237'vel remover a instala'#231#227'o selecionada.';
        raise Exception.Create(Mensagem);
      end;

      Mensagem := GetJsonStringValue(Root, 'mensagem');
      if Mensagem <> '' then
        AppendLog(Mensagem);

      Result := True;
    finally
      JsonValue.Free;
    end;
  except
    on E: Exception do
      ShowMessage('Remo'#231#227'o n'#227'o conclu'#237'da: ' + E.Message);
  end;
end;

function TfrmChave.HasStoredCredentials: Boolean;
begin
  Result := FLembrarCredenciais and (FStoredEmail <> '') and (FStoredPassword <> '');
end;

function TfrmChave.TentarLoginAutomatico: Boolean;
begin
  Result := False;
  if FTentouAutoLogin or not HasStoredCredentials then
    Exit;
  FTentouAutoLogin := True;
  edtEmail.Text := FStoredEmail;
  edtSenha.Text := FStoredPassword;
  try
    GerarTokenViaApi(True);
    Result := FLicencaAtiva;
  except
    on E: Exception do
      AppendLog('Login autom'#225'tico falhou: ' + E.Message);
  end;
end;

function TfrmChave.SolicitarLoginEGerarToken: Boolean;
var
  Email, Senha: string;
  Lembrar: Boolean;
begin
  Result := False;
  if not TfrmLoginPrompt.Executar(FStoredEmail, Email, Senha, Lembrar) then
    Exit;
  edtEmail.Text := Email;
  edtSenha.Text := Senha;
  FLembrarCredenciais := Lembrar;
  FStoredEmail := Email;
  if not FLembrarCredenciais then
    FStoredPassword := '';
  try
    GerarTokenViaApi(False);
    if FLembrarCredenciais then
    begin
      FStoredPassword := Senha;
      if FStoredPassword <> '' then
        SalvarCredenciais;
    end
    else
      LimparCredenciaisArmazenadas;
    Result := FLicencaAtiva;
  except
    on E: Exception do
    begin
      ShowMessage('N'#227'o foi poss'#237'vel ativar a licen'#231'a: ' + E.Message);
      Result := False;
    end;
  end;
end;

function TfrmChave.EnsureLicencaAtiva: Boolean;
begin
  Result := FLicencaAtiva;
  if Result then
    Exit;
  if TentarLoginAutomatico then
    Exit(True);
  Result := SolicitarLoginEGerarToken;
end;

procedure TfrmChave.AbrirCadastroExemplo;
var
  LForm: TfrmCadastroExemplo;
begin
  if not EnsureLicencaAtiva then
  begin
    ShowMessage('Ative a licen'#231'a antes de acessar esta funcionalidade.');
    Exit;
  end;
  LForm := TfrmCadastroExemplo.Create(Self);
  try
    LForm.AtualizarContexto(edtEmail.Text, lblStatusResumo.Caption, lblStatusExpira.Caption);
    LForm.ShowModal;
  finally
    LForm.Free;
  end;
end;

procedure TfrmChave.mnuForcarValidarClick(Sender: TObject);
begin
  try
    ValidarSerialViaApi(False);
  except
    on E: Exception do
      ShowMessage('Falha ao validar: ' + E.Message);
  end;
end;

procedure TfrmChave.mnuLimparCacheClick(Sender: TObject);
begin
  LimparCache(True, True);
  ShowMessage('Cache apagado. Informe os dados novamente para gerar uma nova licen'#231'a.');
  FLicencaAtiva := False;
  lblStatusResumo.Caption := 'Situa'#231#227'o: INATIVO';
  lblStatusExpira.Caption := 'Vencimento: pendente';
  lblStatusMensagem.Caption := 'Cache limpo. Ative a licen'#231'a antes de continuar.';
  lblAvisoVencimento.Caption := lblStatusMensagem.Caption;
  lblAvisoVencimento.Visible := True;
  btnRenovar.Visible := False;
  MostrarPainelLogin(True);
end;

procedure TfrmChave.mnuSairClick(Sender: TObject);
begin
  Close;
end;

procedure TfrmChave.mnuCadastroClientesClick(Sender: TObject);
begin
  AbrirCadastroExemplo;
end;

end.
