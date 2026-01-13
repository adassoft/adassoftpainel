object FrmCheckout: TFrmCheckout
  Left = 0
  Top = 0
  Caption = 'Pagamento via Pix'
  ClientHeight = 450
  ClientWidth = 400
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Tahoma'
  Font.Style = []
  Position = poScreenCenter
  OnClose = FormClose
  OnCreate = FormCreate
  TextHeight = 13
  object pnlContainer: TPanel
    Left = 0
    Top = 0
    Width = 400
    Height = 450
    Align = alClient
    TabOrder = 0
    ExplicitWidth = 398
    ExplicitHeight = 442
    object lblTitle: TLabel
      Left = 1
      Top = 1
      Width = 398
      Height = 23
      Align = alTop
      Alignment = taCenter
      Caption = 'Finalizar Pagamento'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -19
      Font.Name = 'Tahoma'
      Font.Style = [fsBold]
      ParentFont = False
      ExplicitWidth = 194
    end
    object lblValor: TLabel
      Left = 1
      Top = 411
      Width = 398
      Height = 19
      Align = alBottom
      Alignment = taCenter
      Caption = 'Valor: R$ 0,00'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -16
      Font.Name = 'Tahoma'
      Font.Style = [fsBold]
      ParentFont = False
      ExplicitTop = 380
      ExplicitWidth = 116
    end
    object lblStatus: TLabel
      Left = 1
      Top = 430
      Width = 398
      Height = 19
      Align = alBottom
      Alignment = taCenter
      Caption = 'Aguardando Pagamento...'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clRed
      Font.Height = -16
      Font.Name = 'Tahoma'
      Font.Style = [fsBold]
      ParentFont = False
      ExplicitTop = 410
      ExplicitWidth = 212
    end
    object lblInstruction: TLabel
      Left = 24
      Top = 40
      Width = 353
      Height = 13
      Alignment = taCenter
      AutoSize = False
      Caption = 'Escaneie o QR Code abaixo com seu aplicativo de banco.'
    end
    object pnlQRCode: TPanel
      Left = 75
      Top = 70
      Width = 250
      Height = 250
      BevelOuter = bvNone
      Color = clWhite
      ParentBackground = False
      TabOrder = 0
      object imgQRCode: TImage
        Left = 0
        Top = 0
        Width = 250
        Height = 250
        Align = alClient
        Center = True
        Proportional = True
        Stretch = True
        ExplicitLeft = 88
        ExplicitTop = 112
        ExplicitWidth = 105
        ExplicitHeight = 105
      end
    end
    object edtCopyPaste: TEdit
      Left = 40
      Top = 340
      Width = 320
      Height = 21
      TabOrder = 1
      Text = '...'
    end
    object btnCopy: TButton
      Left = 366
      Top = 338
      Width = 25
      Height = 25
      Caption = 'Cp'
      TabOrder = 2
      OnClick = btnCopyClick
    end
  end
  object tmrCheckStatus: TTimer
    Enabled = False
    Interval = 3000
    OnTimer = tmrCheckStatusTimer
    Left = 328
    Top = 24
  end
end
