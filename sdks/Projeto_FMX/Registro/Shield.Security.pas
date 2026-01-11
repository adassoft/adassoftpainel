unit Shield.Security;

interface

uses
  System.SysUtils,
  System.Hash,
  System.Classes,
  System.NetEncoding,
  FMX.Platform // Needed for IFMXDeviceService on all platforms now
  {$IFDEF MSWINDOWS}
  , System.Win.Registry, Winapi.Windows, Winapi.IpHlpApi, Winapi.IpTypes
  {$ENDIF}
  {$IFDEF ANDROID}
  , FMX.Device, Androidapi.Helpers, Androidapi.JNI.GraphicsContentViewText, Androidapi.JNI.JavaTypes
  {$ENDIF}
  {$IFDEF IOS}
  , FMX.Device
  {$ENDIF}
  {$IFDEF MACOS}
  , FMX.Device
  {$ENDIF}
  ;

type
  TShieldSecurity = class
  private
    {$IFDEF MSWINDOWS}
    class function ReadRegistryMachineGuid: string;
    class function ReadSystemVolumeSerial: string;
    class function DetectPrimaryMacAddress: string;
    class function HashToGuidPattern(const HexValue: string): string;
    {$ENDIF}
    
    // Cross-platform helpers
    class function GetDeviceIdentifier: string;
    class function SimpleEncrypt(const Input: string): string;
    class function SimpleDecrypt(const Input: string): string;
  public
    class function GenerateFingerprint: string;
    class function EncryptString(const PlainText: string): string;
    class function DecryptString(const EncryptedText: string): string;
    
    // Mantém compatibilidade com chamada antiga se alguém usar direto, 
    // mas no mobile retorna vazio ou DeviceID
    class function GetMacAddress: string; 
    
    class function ComputeOfflineHash(const Payload, Secret: string): string;
  end;

implementation

{ TShieldSecurity }

{$IFDEF MSWINDOWS}
// --- DPAPI IMPORTS (Windows Only) ---
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

function _GetVolumeInformationW(lpRootPathName: PWideChar; lpVolumeNameBuffer: PWideChar;
  nVolumeNameSize: DWORD; lpVolumeSerialNumber: PDWORD; lpMaximumComponentLength: PDWORD;
  lpFileSystemFlags: PDWORD; lpFileSystemNameBuffer: PWideChar; nFileSystemNameSize: DWORD): BOOL; stdcall;
  external 'kernel32.dll' name 'GetVolumeInformationW'; // Corrigido para especificar a DLL
{$ENDIF}

// ============================================================================
// PUBLIC METHODS
// ============================================================================

class function TShieldSecurity.EncryptString(const PlainText: string): string;
begin
  {$IFDEF MSWINDOWS}
    // Mantendo a criptografia original para Windows (DPAPI) para não quebrar compatibilidade
    var DataIn, DataOut: DATA_BLOB;
    if PlainText = '' then Exit('');
    
    DataIn.cbData := Length(PlainText) * SizeOf(Char);
    DataIn.pbData := PByte(PChar(PlainText));
    DataOut.cbData := 0;
    DataOut.pbData := nil;

    if CryptProtectData(@DataIn, nil, nil, nil, nil, 0, @DataOut) then
    begin
      try
        Result := TNetEncoding.Base64.EncodeBytesToString(
           TBytes(DataOut.pbData), DataOut.cbData);
        Result := StringReplace(Result, sLineBreak, '', [rfReplaceAll]);
      finally
        LocalFree(HLOCAL(DataOut.pbData));
      end;
    end
    else
      Result := '';
  {$ELSE}
    // Para Mobile/Mac, usamos uma criptografia simples baseada em XOR + Base64
    // (Pode ser substituído por AES se houver bibliotecas disponíveis)
    Result := SimpleEncrypt(PlainText);
  {$ENDIF}
end;

class function TShieldSecurity.DecryptString(const EncryptedText: string): string;
begin
  if EncryptedText = '' then Exit('');

  {$IFDEF MSWINDOWS}
    var DataIn, DataOut: DATA_BLOB;
    var Bytes: TBytes;
    
    try
      Bytes := TNetEncoding.Base64.DecodeStringToBytes(EncryptedText);
    except
      Exit('');
    end;

    DataIn.cbData := Length(Bytes);
    DataIn.pbData := @Bytes[0];
    DataOut.cbData := 0;
    DataOut.pbData := nil;

    if CryptUnprotectData(@DataIn, nil, nil, nil, nil, 0, @DataOut) then
    begin
      try
        SetString(Result, PChar(DataOut.pbData), DataOut.cbData div SizeOf(Char));
      finally
        LocalFree(HLOCAL(DataOut.pbData));
      end;
    end;
  {$ELSE}
    Result := SimpleDecrypt(EncryptedText);
  {$ENDIF}
end;

class function TShieldSecurity.GenerateFingerprint: string;
begin
  {$IFDEF MSWINDOWS}
    var Source, Hash, CompName: string;
    var Buffer: array [0..MAX_COMPUTERNAME_LENGTH] of Char;
    var Size: DWORD;
    
    // 1. MachineGuid
    Source := ReadRegistryMachineGuid + '|';
    // 2. VolumeSerial
    Source := Source + ReadSystemVolumeSerial + '|';
    // 3. Computer Name
    Size := MAX_COMPUTERNAME_LENGTH + 1;
    if GetComputerName(Buffer, Size) then
      SetString(CompName, Buffer, Size)
    else
      CompName := 'UNKNOWN';
    Source := Source + UpperCase(Trim(CompName));

    Hash := THashSHA2.GetHashString(Source);
    Result := HashToGuidPattern(Hash);
  {$ELSE}
    // Para Mobile, usamos o DeviceIdentifier fornecido pelo FMX
    Result := GetDeviceIdentifier;
    // Opcional: Hash para garantir tamanho padrão ou formato específico
    // Result := THashSHA2.GetHashString(Result);
  {$ENDIF}
end;

class function TShieldSecurity.GetMacAddress: string;
begin
  {$IFDEF MSWINDOWS}
  Result := DetectPrimaryMacAddress;
  {$ELSE}
  Result := 'MOBILE-DEVICE'; // MAC Address não é acessível de forma confiável em Android 11+/iOS
  {$ENDIF}
end;

class function TShieldSecurity.ComputeOfflineHash(const Payload, Secret: string): string;
begin
  Result := THashSHA2.GetHMAC(Payload, Secret);
end;

// ============================================================================
// CROSS-PLATFORM HELPERS
// ============================================================================

class function TShieldSecurity.GetDeviceIdentifier: string;
var
  DeviceService: IFMXDeviceService;
begin
  Result := 'UNKNOWN-DEVICE-ID';
  {$IF DEFINED(ANDROID) OR DEFINED(IOS) OR DEFINED(MACOS)}
  if TPlatformServices.Current.SupportsPlatformService(IFMXDeviceService, DeviceService) then
  begin
    Result := DeviceService.GetDeviceID;
  end;
  {$ENDIF}
end;

class function TShieldSecurity.SimpleEncrypt(const Input: string): string;
var
  I: Integer;
  Key: Byte;
  Bytes: TBytes;
begin
  // Algoritmo simples de XOR para ofuscação de cache local em mobile
  // Chave fixa simples (idealmente seria dinâmica ou derivada do DeviceID)
  Key := 123; 
  Bytes := TEncoding.UTF8.GetBytes(Input);
  for I := 0 to High(Bytes) do
    Bytes[I] := Bytes[I] xor Key;
    
  Result := TNetEncoding.Base64.EncodeBytesToString(Bytes);
  Result := StringReplace(Result, sLineBreak, '', [rfReplaceAll]);
end;

class function TShieldSecurity.SimpleDecrypt(const Input: string): string;
var
  I: Integer;
  Key: Byte;
  Bytes: TBytes;
begin
  Key := 123;
  try
    Bytes := TNetEncoding.Base64.DecodeStringToBytes(Input);
    for I := 0 to High(Bytes) do
      Bytes[I] := Bytes[I] xor Key;
    Result := TEncoding.UTF8.GetString(Bytes);
  except
    Result := '';
  end;
end;

// ============================================================================
// WINDOWS SPECIFIC IMPLEMENTATION (LEGACY SUPPORT)
// ============================================================================

{$IFDEF MSWINDOWS}
class function TShieldSecurity.DetectPrimaryMacAddress: string;
const
  MIB_IF_TYPE_ETHERNET = 6;
  MIB_IF_TYPE_IEEE80211 = 71;
var
  Buffer: PIP_ADAPTER_INFO;
  OutLen: ULONG;
  Adapter: PIP_ADAPTER_INFO;
begin
  Result := '';
  OutLen := 0;
  if GetAdaptersInfo(nil, OutLen) <> ERROR_BUFFER_OVERFLOW then Exit;

  GetMem(Buffer, OutLen);
  try
    if GetAdaptersInfo(Buffer, OutLen) <> ERROR_SUCCESS then Exit;
    Adapter := Buffer;
    while Adapter <> nil do
    begin
      if (Adapter^.AddressLength = 6) and
         ((Adapter^.Type_ = MIB_IF_TYPE_ETHERNET) or (Adapter^.Type_ = MIB_IF_TYPE_IEEE80211)) then
      begin
        Result := Format('%.2x:%.2x:%.2x:%.2x:%.2x:%.2x',
          [Adapter^.Address[0], Adapter^.Address[1], Adapter^.Address[2],
           Adapter^.Address[3], Adapter^.Address[4], Adapter^.Address[5]]);
        Result := UpperCase(Result);
        Break;
      end;
      Adapter := Adapter^.Next;
    end;
  finally
    FreeMem(Buffer);
  end;
end;

class function TShieldSecurity.ReadSystemVolumeSerial: string;
var
  Serial: DWORD;
  Drive: string;
begin
  Result := '00000000';
  Serial := 0;
  Drive := 'C:\';
  if _GetVolumeInformationW(PWideChar(Drive), nil, 0, @Serial, nil, nil, nil, 0) then
    Result := IntToHex(Serial, 8);
end;

class function TShieldSecurity.ReadRegistryMachineGuid: string;
var
  Reg: TRegistry;
begin
  Result := '';
  Reg := TRegistry.Create(KEY_READ);
  try
    Reg.RootKey := HKEY_LOCAL_MACHINE;
    if Reg.OpenKeyReadOnly('SOFTWARE\Microsoft\Cryptography') then
      Result := Trim(Reg.ReadString('MachineGuid'));
  finally
    Reg.Free;
  end;
end;

class function TShieldSecurity.HashToGuidPattern(const HexValue: string): string;
var
  Clean: string;
begin
  Clean := StringReplace(HexValue, '-', '', [rfReplaceAll]);
  while Length(Clean) < 32 do
    Clean := Clean + '0';
  Result := Format('{%s-%s-%s-%s-%s}',
    [Copy(Clean, 1, 8), Copy(Clean, 9, 4), Copy(Clean, 13, 4),
     Copy(Clean, 17, 4), Copy(Clean, 21, 12)]);
end;
{$ENDIF}

end.
