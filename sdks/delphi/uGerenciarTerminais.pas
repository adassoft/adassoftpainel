unit uGerenciarTerminais;

interface

uses
  System.SysUtils, System.Classes,
  Vcl.Forms, Vcl.StdCtrls, Vcl.ComCtrls, Vcl.Controls;

type
  TTerminalResumo = record
    TerminalId: Integer;
    InstalacaoId: string;
    NomeComputador: string;
    MacAddress: string;
    UltimaAtividade: string;
    UltimoRegistro: string;
    Ativo: Boolean;
  end;

  TfrmGerenciarTerminais = class(TForm)
    lblResumo: TLabel;
    lvTerminais: TListView;
    btnRemover: TButton;
    btnCancelar: TButton;
    procedure lvTerminaisSelectItem(Sender: TObject; Item: TListItem;
      Selected: Boolean);
    procedure btnRemoverClick(Sender: TObject);
    procedure lvTerminaisDblClick(Sender: TObject);
  private
    FLista: TArray<TTerminalResumo>;
    FSelecionado: Integer;
    procedure PreencherLista;
    procedure AtualizarBotoes;
  public
    class function Executar(const Lista: TArray<TTerminalResumo>;
      const TotaisResumo: string; out Selecionado: TTerminalResumo): Boolean;
  end;

implementation

{$R *.dfm}

{ TfrmGerenciarTerminais }

procedure TfrmGerenciarTerminais.AtualizarBotoes;
begin
  btnRemover.Enabled := Assigned(lvTerminais.Selected);
end;

procedure TfrmGerenciarTerminais.btnRemoverClick(Sender: TObject);
begin
  if not Assigned(lvTerminais.Selected) then
    Exit;
  FSelecionado := lvTerminais.Selected.Index;
  ModalResult := mrOk;
end;

class function TfrmGerenciarTerminais.Executar(
  const Lista: TArray<TTerminalResumo>; const TotaisResumo: string;
  out Selecionado: TTerminalResumo): Boolean;
begin
  Result := False;
  Selecionado := Default(TTerminalResumo);
  with TfrmGerenciarTerminais.Create(nil) do
  try
    FLista := Copy(Lista);
    lblResumo.Caption := TotaisResumo;
    PreencherLista;
    AtualizarBotoes;
    if ShowModal = mrOk then
    begin
      if (FSelecionado >= 0) and (FSelecionado < Length(FLista)) then
      begin
        Selecionado := FLista[FSelecionado];
        Result := True;
      end;
    end;
  finally
    Free;
  end;
end;

procedure TfrmGerenciarTerminais.lvTerminaisDblClick(Sender: TObject);
begin
  btnRemoverClick(Sender);
end;

procedure TfrmGerenciarTerminais.lvTerminaisSelectItem(Sender: TObject;
  Item: TListItem; Selected: Boolean);
begin
  AtualizarBotoes;
end;

procedure TfrmGerenciarTerminais.PreencherLista;
var
  I: Integer;
  Item: TListItem;
  Terminal: TTerminalResumo;
  StatusTexto: string;
begin
  lvTerminais.Items.BeginUpdate;
  try
    lvTerminais.Items.Clear;
    for I := 0 to High(FLista) do
    begin
      Terminal := FLista[I];
      if Terminal.Ativo then
        StatusTexto := 'Ativo'
      else
        StatusTexto := 'Liberado';
      Item := lvTerminais.Items.Add;
      Item.Caption := StatusTexto;
      Item.SubItems.Add(Terminal.NomeComputador);
      Item.SubItems.Add(Terminal.MacAddress);
      Item.SubItems.Add(Terminal.UltimaAtividade);
      Item.SubItems.Add(Terminal.InstalacaoId);
    end;
  finally
    lvTerminais.Items.EndUpdate;
  end;
end;

end.
