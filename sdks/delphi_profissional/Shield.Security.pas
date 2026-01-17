unit Shield.Security;

interface

uses
  System.SysUtils, System.Hash, System.Win.Registry, Winapi.Windows,
  Winapi.IpHlpApi, Winapi.IpTypes, System.Classes, System.NetEncoding;

type
  TShieldSecurity = class
  private
    class function ReadRegistryMachineGuid: string;
    class function ReadSystemVolumeSerial: string;
    class function HashToGuidPattern(const HexValue: string): string;
  public
    class function GenerateFingerprint: string;
    class function EncryptString(const PlainText: string): string;
    class function DecryptString(const EncryptedText: string): string;
    class function DetectPrimaryMacAddress: string;
    class function ComputeOfflineHash(const Input, MsgSecret: string): string;
  end;

implementation

{ TShieldSecurity }

// --- DPAPI IMPORTS ---
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

// --- IMPLEMENTATION ---

class function TShieldSecurity.EncryptString(const PlainText: string): string;
var
  DataIn, DataOut: DATA_BLOB;
  S: TStringStream;
begin
  Result := '';
  if PlainText = '' then Exit;
  
  DataIn.cbData := Length(PlainText) * SizeOf(Char);
  DataIn.pbData := PByte(PChar(PlainText));
  DataOut.cbData := 0;
  DataOut.pbData := nil;

  if CryptProtectData(@DataIn, nil, nil, nil, nil, 0, @DataOut) then
  begin
    try
      S := TStringStream.Create;
      try
        // Simple Base64 of the binary blob
        Result := System.NetEncoding.TNetEncoding.Base64.EncodeBytesToString(
           TBytes(DataOut.pbData), DataOut.cbData);
        // Clean up newlines
        Result := StringReplace(Result, sLineBreak, '', [rfReplaceAll]);
      finally
        S.Free;
      end;
    finally
      LocalFree(HLOCAL(DataOut.pbData));
    end;
  end;
end;

class function TShieldSecurity.DecryptString(const EncryptedText: string): string;
var
  DataIn, DataOut: DATA_BLOB;
  Bytes: TBytes;
begin
  Result := '';
  if EncryptedText = '' then Exit;

  try
    Bytes := System.NetEncoding.TNetEncoding.Base64.DecodeStringToBytes(EncryptedText);
  except
    Exit;
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
end;

class function TShieldSecurity.GenerateFingerprint: string;
var
  Source, Hash, CompName: string;
  Buffer: array [0..MAX_COMPUTERNAME_LENGTH] of Char;
  Size: DWORD;
begin
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

  // Generate SHA256 Hash
  Hash := THashSHA2.GetHashString(Source);
  Result := HashToGuidPattern(Hash);
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
      // Priority to Ethernet or Wi-Fi, valid mac length
      if (Adapter^.AddressLength = 6) and
         ((Adapter^.Type_ = MIB_IF_TYPE_ETHERNET) or (Adapter^.Type_ = MIB_IF_TYPE_IEEE80211)) then
      begin
        Result := Format('%.2x:%.2x:%.2x:%.2x:%.2x:%.2x',
          [Adapter^.Address[0], Adapter^.Address[1], Adapter^.Address[2],
           Adapter^.Address[3], Adapter^.Address[4], Adapter^.Address[5]]);
        Result := UpperCase(Result);
        Break; // Pick first valid physical
      end;
      Adapter := Adapter^.Next;
    end;
  finally
    FreeMem(Buffer);
  end;
end;

function _GetVolumeInformationW(lpRootPathName: PWideChar; lpVolumeNameBuffer: PWideChar;
  nVolumeNameSize: DWORD; lpVolumeSerialNumber: PDWORD; lpMaximumComponentLength: PDWORD;
  lpFileSystemFlags: PDWORD; lpFileSystemNameBuffer: PWideChar; nFileSystemNameSize: DWORD): BOOL; stdcall;
  external kernel32 name 'GetVolumeInformationW';

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

class function TShieldSecurity.ComputeOfflineHash(const Input, MsgSecret: string): string;
begin
  // Gera HMAC SHA-256
  Result := THashSHA2.GetHMAC(Input, MsgSecret);
end;

end.
