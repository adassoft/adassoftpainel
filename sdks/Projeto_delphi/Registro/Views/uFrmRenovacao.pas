unit uFrmRenovacao;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.StdCtrls, Vcl.ExtCtrls, Vcl.ComCtrls,
  Shield.Core, Shield.Types;

type
  TfrmRenovacao = class(TForm)
    pnlTopo: TPanel;
    lblTitulo: TLabel;
    Label1: TLabel;
    lstPlanos: TListView;
    pnlBottom: TPanel;
    btnPagar: TButton;
    btnCancelar: TButton;
    procedure btnCancelarClick(Sender: TObject);
    procedure FormShow(Sender: TObject);
    procedure btnPagarClick(Sender: TObject);
  private
    FShield: TShield;
    FPlanos: TPlanArray;
    procedure CarregarPlanos;
  public
    class procedure Executar(AShield: TShield);
  end;

var
  frmRenovacao: TfrmRenovacao;

implementation

{$R *.dfm}

uses
  ShellAPI;

{ TfrmRenovacao }

class procedure TfrmRenovacao.Executar(AShield: TShield);
var
  Form: TfrmRenovacao;
begin
  Form := TfrmRenovacao.Create(nil);
  try
    Form.FShield := AShield;
    Form.ShowModal;
  finally
    Form.Free;
  end;
end;

procedure TfrmRenovacao.FormShow(Sender: TObject);
begin
  CarregarPlanos;
end;

procedure TfrmRenovacao.CarregarPlanos;
var
  I: Integer;
  Item: TListItem;
begin
  lstPlanos.Items.BeginUpdate;
  try
    lstPlanos.Items.Clear;
    try
      FPlanos := FShield.GetAvailablePlans;
    except
      on E: Exception do
      begin
        ShowMessage('Erro ao buscar planos: ' + E.Message + '. Tente fazer login novamente.');
        Close;
        Exit;
      end;
    end;
    
    if Length(FPlanos) = 0 then
    begin
      ShowMessage('Nenhum plano disponível ou sessão expirada. Faça login novamente.');
      Close;
      Exit;
    end;

    for I := 0 to High(FPlanos) do
    begin
      Item := lstPlanos.Items.Add;
      Item.Caption := FPlanos[I].Nome;
      Item.SubItems.Add(FormatFloat('R$ #,##0.00', FPlanos[I].Valor));
      Item.SubItems.Add(FPlanos[I].Descricao); // Ex: MENSAL
      Item.Data := Pointer(FPlanos[I].Id);
    end;
    
    if lstPlanos.Items.Count > 0 then
      lstPlanos.ItemIndex := 0;
  finally
    lstPlanos.Items.EndUpdate;
  end;
end;

procedure TfrmRenovacao.btnPagarClick(Sender: TObject);
var
  PlanID: Integer;
  Url: string;
begin
  if lstPlanos.ItemIndex < 0 then
  begin
    ShowMessage('Selecione um plano.');
    Exit;
  end;

  PlanID := Integer(lstPlanos.Selected.Data);
  
  Screen.Cursor := crHourGlass;
  try
    try
      Url := FShield.CheckoutPlan(PlanID);
      ShellExecute(0, 'open', PChar(Url), nil, nil, SW_SHOWNORMAL);
      Close; // Fecha pois o navegador vai abrir
    except
      on E: Exception do
        ShowMessage('Erro ao gerar pagamento: ' + E.Message);
    end;
  finally
    Screen.Cursor := crDefault;
  end;
end;

procedure TfrmRenovacao.btnCancelarClick(Sender: TObject);
begin
  Close;
end;

end.
