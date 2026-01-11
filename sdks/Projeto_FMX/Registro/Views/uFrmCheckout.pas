unit uFrmCheckout;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.ExtCtrls, Vcl.StdCtrls, Vcl.Imaging.pngimage,
  Vcl.Clipbrd, Shield.Core, Shield.Types;

type
  TFrmCheckout = class(TForm)
    pnlContainer: TPanel;
    lblTitle: TLabel;
    pnlQRCode: TPanel;
    imgQRCode: TImage;
    lblValor: TLabel;
    lblStatus: TLabel;
    edtCopyPaste: TEdit;
    btnCopy: TButton;
    tmrCheckStatus: TTimer;
    lblInstruction: TLabel;
    procedure FormCreate(Sender: TObject);
    procedure btnCopyClick(Sender: TObject);
    procedure tmrCheckStatusTimer(Sender: TObject);
    procedure FormClose(Sender: TObject; var Action: TCloseAction);
  private
    { Private declarations }
    FShield: TShield;
    FPlanId: Integer;
    FPaymentInfo: TPaymentInfo;
    FPagamentoConfirmado: Boolean;
    procedure LoadQRCode(const Base64: string);
  public
    { Public declarations }
    class function Execute(AShield: TShield; PlanId: Integer): Boolean;
    procedure IniciarPagamento;
  end;

var
  FrmCheckout: TFrmCheckout;

implementation

{$R *.dfm}

uses
  System.NetEncoding, Vcl.Imaging.jpeg;

class function TFrmCheckout.Execute(AShield: TShield; PlanId: Integer): Boolean;
begin
  FrmCheckout := TFrmCheckout.Create(nil);
  try
    FrmCheckout.FShield := AShield;
    FrmCheckout.FPlanId := PlanId;
    FrmCheckout.IniciarPagamento;
    FrmCheckout.ShowModal;
    Result := FrmCheckout.FPagamentoConfirmado;
  finally
    FrmCheckout.Free;
  end;
end;

procedure TFrmCheckout.FormCreate(Sender: TObject);
begin
  FPagamentoConfirmado := False;
  lblStatus.Caption := 'Aguardando pagamento...';
  lblStatus.Font.Color := clRed;
end;

procedure TFrmCheckout.IniciarPagamento;
begin
  try
    Screen.Cursor := crHourGlass;
    try
      FPaymentInfo := FShield.CheckoutPlan(FPlanId);
      
      LoadQRCode(FPaymentInfo.QrCodeBase64);
      edtCopyPaste.Text := FPaymentInfo.QrCodePayload;
      lblValor.Caption := Format('Valor: R$ %.2f', [FPaymentInfo.Valor]);
      lblInstruction.Caption := 'Escaneie o QR Code ou copie o código abaixo para pagar via Pix.';
      
      tmrCheckStatus.Enabled := True;
    except
      on E: Exception do
      begin
        ShowMessage('Erro ao gerar pagamento: ' + E.Message);
        Close;
      end;
    end;
  finally
    Screen.Cursor := crDefault;
  end;
end;

procedure TFrmCheckout.LoadQRCode(const Base64: string);
var
  Stream: TBytesStream;
  Bytes: TBytes;
  Png: TPngImage;
  Jpg: TJPEGImage;
begin
  if Base64 = '' then Exit;
  
  try
    Bytes := TNetEncoding.Base64.DecodeStringToBytes(Base64);
    Stream := TBytesStream.Create(Bytes);
    try
      // Tenta detectar se é PNG ou JPEG (Geralmente APIs retornam PNG ou JPEG)
      // Vamos tentar PNG primeiro
      try
        Png := TPngImage.Create;
        try
          Stream.Position := 0;
          Png.LoadFromStream(Stream);
          imgQRCode.Picture.Assign(Png);
        finally
          Png.Free;
        end;
      except
        // Se falhar, tenta JPEG
        try
           Jpg := TJPEGImage.Create;
           try
             Stream.Position := 0;
             Jpg.LoadFromStream(Stream);
             imgQRCode.Picture.Assign(Jpg);
           finally
             Jpg.Free;
           end;
        except
           // Silencia erro de imagem inválida
        end;
      end;
    finally
      Stream.Free;
    end;
  except
    // Erro de decode
  end;
end;

procedure TFrmCheckout.btnCopyClick(Sender: TObject);
begin
  Clipboard.AsText := edtCopyPaste.Text;
  ShowMessage('Código Pix copiado!');
end;

procedure TFrmCheckout.tmrCheckStatusTimer(Sender: TObject);
var
  Status: string;
begin
  tmrCheckStatus.Enabled := False; // Pausa para não encavalar
  try
    Status := FShield.CheckPaymentStatus(FPaymentInfo.TransactionId);
    
    if (Status = 'paid') or (Status = 'pago') or (Status = 'confirmed') then
    begin
       lblStatus.Caption := 'Pagamento Confirmado!';
       lblStatus.Font.Color := clGreen;
       Application.ProcessMessages;
       Sleep(1000); // UI Feedback
       
       // Tenta revalidar a licença imediatamente
       try
          FShield.CheckLicense; // Isso atualiza o cache local
       except
       end;
       
       FPagamentoConfirmado := True;
       ModalResult := mrOk;
    end
    else
    begin
       // Continua polling
       tmrCheckStatus.Enabled := True;
    end;
  except
    // Omitir erros de rede no timer, tenta de novo
    tmrCheckStatus.Enabled := True;
  end;
end;

procedure TFrmCheckout.FormClose(Sender: TObject; var Action: TCloseAction);
begin
  tmrCheckStatus.Enabled := False;
end;

end.
