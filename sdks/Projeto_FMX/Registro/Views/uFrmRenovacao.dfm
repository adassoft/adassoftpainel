object frmRenovacao: TfrmRenovacao
  Left = 0
  Top = 0
  BorderIcons = [biSystemMenu]
  BorderStyle = bsSingle
  Caption = 'Renova'#231#227'o de Licen'#231'a'
  ClientHeight = 350
  ClientWidth = 500
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Segoe UI'
  Font.Style = []
  OldCreateOrder = False
  Position = poScreenCenter
  OnShow = FormShow
  PixelsPerInch = 96
  TextHeight = 13
  object pnlTopo: TPanel
    Left = 0
    Top = 0
    Width = 500
    Height = 57
    Align = alTop
    BevelOuter = bvNone
    Color = clWhite
    ParentBackground = False
    TabOrder = 0
    object lblTitulo: TLabel
      Left = 16
      Top = 13
      Width = 111
      Height = 17
      Caption = 'Escolha um Plano'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -13
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object Label1: TLabel
      Left = 16
      Top = 32
      Width = 286
      Height = 13
      Caption = 'Selecione a melhor op'#231#227'o para continuar usando o sistema.'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clGrayText
      Font.Height = -11
      Font.Name = 'Segoe UI'
      Font.Style = []
      ParentFont = False
    end
  end
  object lstPlanos: TListView
    Left = 0
    Top = 57
    Width = 500
    Height = 242
    Align = alClient
    Columns = <
      item
        Caption = 'Plano'
        Width = 250
      end
      item
        Caption = 'Valor'
        Width = 100
      end
      item
        Caption = 'Recorr'#234'ncia'
        Width = 120
      end>
    GridLines = True
    ReadOnly = True
    RowSelect = True
    TabOrder = 1
    ViewStyle = vsReport
  end
  object pnlBottom: TPanel
    Left = 0
    Top = 299
    Width = 500
    Height = 51
    Align = alBottom
    BevelOuter = bvNone
    TabOrder = 2
    object btnPagar: TButton
      Left = 320
      Top = 8
      Width = 161
      Height = 33
      Caption = 'Ir para Pagamento'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -11
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
      TabOrder = 0
      OnClick = btnPagarClick
    end
    object btnCancelar: TButton
      Left = 239
      Top = 8
      Width = 75
      Height = 33
      Caption = 'Cancelar'
      TabOrder = 1
      OnClick = btnCancelarClick
    end
  end
end
