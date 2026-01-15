unit uFrmAlert;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.ExtCtrls, Vcl.StdCtrls;

type
  TfrmAlert = class(TForm)
    Shape1: TShape;
    lblTitulo: TLabel;
    lblMensagem: TLabel;
    btnRenovar: TPanel;
    btnFechar: TPanel;
    procedure btnRenovarClick(Sender: TObject);
    procedure btnFecharClick(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure btnRenovarMouseEnter(Sender: TObject);
    procedure btnRenovarMouseLeave(Sender: TObject);
    procedure FormMouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
  private
    { Private declarations }
    FResult: Boolean;
  public
    { Public declarations }
    { Public declarations }
    class function Execute(const Msg: string; const Title: string = 'Renovação Necessária'; const BtnText: string = 'Renovar Agora'; const CancelText: string = 'Fechar'): Boolean;
  end;

var
  frmAlert: TfrmAlert;

implementation

{$R *.dfm}

class function TfrmAlert.Execute(const Msg: string; const Title: string = 'Renovação Necessária'; const BtnText: string = 'Renovar Agora'; const CancelText: string = 'Fechar'): Boolean;
var
  Form: TfrmAlert;
begin
  Form := TfrmAlert.Create(nil);
  try
    Form.lblTitulo.Caption := Title;
    Form.lblMensagem.Caption := Msg;
    Form.btnRenovar.Caption := BtnText;
    
    // Suporte a Label dentro do Panel ou Caption do Panel
    // Como btnFechar é um TPanel, assumimos que ele tem um Caption direto ou Label interno.
    // Se for TPanel padrão:
    Form.btnFechar.Caption := CancelText;
    Form.ShowModal;
    Result := Form.FResult;
  finally
    Form.Free;
  end;
end;

procedure TfrmAlert.FormCreate(Sender: TObject);
begin
  FResult := False;
end;

procedure TfrmAlert.btnFecharClick(Sender: TObject);
begin
  FResult := False;
  Close;
end;

procedure TfrmAlert.btnRenovarClick(Sender: TObject);
begin
  FResult := True;
  Close;
end;

procedure TfrmAlert.btnRenovarMouseEnter(Sender: TObject);
begin
   // Opcional: Efeito hover
   TPanel(Sender).Color := $0055AA55; // Cor mais clara
end;

procedure TfrmAlert.btnRenovarMouseLeave(Sender: TObject);
begin
   TPanel(Sender).Color := $00558C4C; // Cor original
end;

// Permite arrastar a janela
procedure TfrmAlert.FormMouseDown(Sender: TObject; Button: TMouseButton;
  Shift: TShiftState; X, Y: Integer);
begin
  if Button = mbLeft then
  begin
    ReleaseCapture;
    Perform(WM_SYSCOMMAND, $F012, 0);
  end;
end;

end.
