program project1;

{$mode objfpc}{$H+}

uses
  {$IFDEF UNIX}
  cthreads,
  {$ENDIF}
  Classes, SysUtils, Shield.Core, Shield.Config, Shield.Types;

var
  Config: TShieldConfig;
  Shield: TShield;
  Serial: String;
  Plans: TList;
  Plan: TPlan;
  i: Integer;
  PlanId: Integer;
  CheckoutUrl: String;

begin
  WriteLn('=== Shield SDK Lazarus Demo ===');
  
  // 1. Configuração
  Config := TShieldConfig.Create(
    'http://localhost/shield', // URL
    '123456',                  // API Key
    1,                         // Software ID
    '1.0.0'                    // Versão
  );
  
  Shield := TShield.Create(Config);
  
  try
    Write('Digite o Serial da Licença: ');
    ReadLn(Serial);
    
    if Serial = '' then
    begin
      WriteLn('Serial vazio. Saindo.');
      Exit;
    end;
    
    WriteLn('Validando licença...');
    Shield.CheckLicense(Serial);
    
    WriteLn('--- Resultado ---');
    if Shield.License.IsValid then
      WriteLn('Status: VALIDO')
    else
      WriteLn('Status: INVALIDO (' + Shield.License.Mensagem + ')');
      
    if Shield.License.IsValid then
    begin
      WriteLn('Expira em: ' + DateToStr(Shield.License.DataExpiracao));
      WriteLn('Dias Restantes: ' + IntToStr(Shield.License.DiasRestantes));
      
      if Shield.License.ShouldWarnExpiration then
        WriteLn('[ALERTA] Renove sua licença em breve!');
    end;
    
    WriteLn('');
    WriteLn('--- Planos Disponíveis ---');
    Plans := Shield.GetAvailablePlans;
    if Plans.Count = 0 then
      WriteLn('Nenhum plano encontrado.')
    else
    begin
      for i := 0 to Plans.Count - 1 do
      begin
        Plan := TPlan(Plans[i]);
        WriteLn(Format('ID: %d | %s - R$ %.2f', [Plan.Id, Plan.Nome, Plan.Valor]));
      end;
      
      WriteLn('');
      Write('Digite o ID do plano para gerar link (0 sair): ');
      ReadLn(PlanId);
      
      if PlanId > 0 then
      begin
        CheckoutUrl := Shield.CreateCheckoutUrl(PlanId);
        if CheckoutUrl <> '' then
          WriteLn('Link de Pagamento: ' + CheckoutUrl)
        else
          WriteLn('Erro ao gerar link de pagamento.');
      end;
    end;
    
    // Limpeza da lista de planos
    for i := 0 to Plans.Count - 1 do
      TObject(Plans[i]).Free;
    Plans.Free;

  finally
    Shield.Free;
    Config.Free;
  end;
  
  WriteLn('Pressione ENTER para sair...');
  ReadLn;
end.
