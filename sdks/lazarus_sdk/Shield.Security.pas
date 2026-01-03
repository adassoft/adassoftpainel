unit Shield.Security;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, Base64, Process;

type
  TShieldSecurity = class
  public
    class function GetHardwareId: String;
    class function Protect(const AData: String): String;
    class function Unprotect(const AData: String): String;
  end;

implementation

{ TShieldSecurity }

class function TShieldSecurity.GetHardwareId: String;
var
  S: String;
begin
  // Estratégia Multiplataforma "Good Enough"
  // Combina Nome do PC + Usuário + OS
  S := GetEnvironmentVariable('COMPUTERNAME'); // Win
  if S = '' then S := GetEnvironmentVariable('HOSTNAME'); // Linux/Unix
  
  S := S + '|' + GetEnvironmentVariable('USERNAME');
  if S = '|' then S := S + GetEnvironmentVariable('USER');
  
  S := S + '|' + {$I %FPCTARGETOS%}; // OS compilation target
  
  // Retorna hash Base64 simples dessa string para normalizar
  Result := EncodeStringBase64(S);
end;

// Criptografia Simples XOR (Ofuscação)
// Objetivo: Evitar edição casual do arquivo de cache.
// Não use para guardar senhas de banco de dados críticas.
const
  KEY_XOR = 'SHIELD_SDK_LAZARUS_KEY_MULTI';

class function TShieldSecurity.Protect(const AData: String): String;
var
  i: Integer;
  ResultStr: String;
begin
  ResultStr := AData;
  for i := 1 to Length(ResultStr) do
    ResultStr[i] := Char(Ord(ResultStr[i]) xor Ord(KEY_XOR[1 + ((i - 1) mod Length(KEY_XOR))]));
  Result := EncodeStringBase64(ResultStr);
end;

class function TShieldSecurity.Unprotect(const AData: String): String;
var
  i: Integer;
  Decoded: String;
begin
  try
    Decoded := DecodeStringBase64(AData);
    Result := Decoded;
    for i := 1 to Length(Result) do
      Result[i] := Char(Ord(Result[i]) xor Ord(KEY_XOR[1 + ((i - 1) mod Length(KEY_XOR))]));
  except
    Result := '';
  end;
end;

end.
