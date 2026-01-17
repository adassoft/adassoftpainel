object frmMain: TfrmMain
  Left = 0
  Top = 0
  Caption = 'Super Carnê - Modern UI'
  ClientHeight = 720
  ClientWidth = 1080
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -12
  Font.Name = 'Segoe UI'
  Font.Style = []
  OldCreateOrder = False
  Position = poScreenCenter
  OnCreate = FormCreate
  OnResize = FormResize
  PixelsPerInch = 96
  TextHeight = 15
  object pnlSidebar: TPanel
    Left = 0
    Top = 0
    Width = 240
    Height = 720
    Align = alLeft
    BevelOuter = bvNone
    Color = 3092271
    ParentBackground = False
    TabOrder = 0
    object pnlLogo: TPanel
      Left = 0
      Top = 0
      Width = 240
      Height = 70
      Align = alTop
      BevelOuter = bvNone
      Color = 2302755
      ParentBackground = False
      TabOrder = 0
      object lblAppName: TLabel
        Left = 24
        Top = 22
        Width = 118
        Height = 25
        Caption = 'SUPER CARNÊ'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -19
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
      end
    end
    object pnlMenuContainer: TPanel
      Left = 0
      Top = 70
      Width = 240
      Height = 650
      Align = alClient
      BevelOuter = bvNone
      Color = 3092271
      ParentBackground = False
      TabOrder = 1
      object btnMenuDashboard: TSpeedButton
        Left = 0
        Top = 0
        Width = 240
        Height = 50
        Align = alTop
        Caption = '   Visão Geral'
        Flat = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -15
        Font.Name = 'Segoe UI'
        Font.Style = []
        Margin = 20
        ParentFont = False
        OnClick = btnMenuClick
        OnMouseEnter = btnMouseEnter
        OnMouseLeave = btnMouseLeave
        ExplicitLeft = 8
        ExplicitTop = 8
      end
      object btnMenuCadastros: TSpeedButton
        Left = 0
        Top = 50
        Width = 240
        Height = 50
        Align = alTop
        Caption = '   Cadastros'
        Flat = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -15
        Font.Name = 'Segoe UI'
        Font.Style = []
        Margin = 20
        ParentFont = False
        OnClick = btnMenuClick
        OnMouseEnter = btnMouseEnter
        OnMouseLeave = btnMouseLeave
      end
      object btnMenuCaixa: TSpeedButton
        Left = 0
        Top = 100
        Width = 240
        Height = 50
        Align = alTop
        Caption = '   Financeiro / Caixa'
        Flat = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -15
        Font.Name = 'Segoe UI'
        Font.Style = []
        Margin = 20
        ParentFont = False
        OnClick = btnMenuClick
        OnMouseEnter = btnMouseEnter
        OnMouseLeave = btnMouseLeave
      end
      object btnMenuRelatorios: TSpeedButton
        Left = 0
        Top = 150
        Width = 240
        Height = 50
        Align = alTop
        Caption = '   Relatórios'
        Flat = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -15
        Font.Name = 'Segoe UI'
        Font.Style = []
        Margin = 20
        ParentFont = False
        OnClick = btnMenuClick
        OnMouseEnter = btnMouseEnter
        OnMouseLeave = btnMouseLeave
      end
      object btnMenuAjuda: TSpeedButton
        Left = 0
        Top = 200
        Width = 240
        Height = 50
        Align = alTop
        Caption = '   Ajuda e Suporte'
        Flat = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -15
        Font.Name = 'Segoe UI'
        Font.Style = []
        Margin = 20
        ParentFont = False
        OnClick = btnMenuClick
        OnMouseEnter = btnMouseEnter
        OnMouseLeave = btnMouseLeave
      end
    end
  end
  object pnlHeader: TPanel
    Left = 240
    Top = 0
    Width = 840
    Height = 70
    Align = alTop
    BevelOuter = bvNone
    Color = clWhite
    ParentBackground = False
    TabOrder = 1
    object lblTitle: TLabel
      Left = 32
      Top = 22
      Width = 109
      Height = 25
      Caption = 'Visão Geral'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = 4539717
      Font.Height = -19
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object lblUserName: TLabel
      Left = 680
      Top = 26
      Width = 99
      Height = 17
      Alignment = taRightJustify
      Anchors = [akTop, akRight]
      Caption = 'Usuário: MASTER'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clGray
      Font.Height = -13
      Font.Name = 'Segoe UI'
      Font.Style = []
      ParentFont = False
    end
  end
  object pnlContent: TPanel
    Left = 240
    Top = 70
    Width = 840
    Height = 650
    Align = alClient
    BevelOuter = bvNone
    Color = 16119285
    Padding.Left = 20
    Padding.Top = 20
    Padding.Right = 20
    Padding.Bottom = 20
    ParentBackground = False
    TabOrder = 2
    object pnlPageDashboard: TPanel
      Left = 20
      Top = 20
      Width = 800
      Height = 610
      Align = alClient
      BevelOuter = bvNone
      Color = 16119285
      ParentBackground = False
      TabOrder = 0
      object lblWelcome: TLabel
        Left = 0
        Top = 0
        Width = 286
        Height = 25
        Align = alTop
        Caption = 'Bem-vindo ao Sistema Super Carnê'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = 4539717
        Font.Height = -19
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        ExplicitWidth = 204
      end
      object pnlStats: TGridPanel
        Left = 0
        Top = 40
        Width = 800
        Height = 120
        Align = alTop
        BevelOuter = bvNone
        ColumnCollection = <
          item
            Value = 33.333333333333330000
          end
          item
            Value = 33.333333333333330000
          end
          item
            Value = 33.333333333333330000
          end>
        ControlCollection = <
          item
            Column = 0
            Control = pnlCard1
            Row = 0
          end
          item
            Column = 1
            Control = pnlCard2
            Row = 0
          end
          item
            Column = 2
            Control = pnlCard3
            Row = 0
          end>
        Padding.Top = 10
        Padding.Bottom = 10
        ParentBackground = False
        RowCollection = <
          item
            Value = 100.000000000000000000
          end>
        TabOrder = 0
        object pnlCard1: TPanel
          AlignWithMargins = True
          Left = 0
          Top = 10
          Width = 260
          Height = 100
          Margins.Left = 0
          Margins.Top = 0
          Margins.Right = 20
          Margins.Bottom = 0
          Align = alClient
          BevelKind = bkNone
          BevelOuter = bvNone
          Color = clWhite
          ParentBackground = False
          TabOrder = 0
          object lblCard1Title: TLabel
            Left = 20
            Top = 20
            Width = 95
            Height = 17
            Caption = 'Vendas Hoje'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clGray
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
          object lblCard1Value: TLabel
            Left = 20
            Top = 45
            Width = 91
            Height = 32
            Caption = 'R$ 1.250'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = 16744448
            Font.Height = -24
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
        end
        object pnlCard2: TPanel
          AlignWithMargins = True
          Left = 280
          Top = 10
          Width = 260
          Height = 100
          Margins.Left = 0
          Margins.Top = 0
          Margins.Right = 20
          Margins.Bottom = 0
          Align = alClient
          BevelOuter = bvNone
          Color = clWhite
          ParentBackground = False
          TabOrder = 1
          object lblCard2Title: TLabel
            Left = 20
            Top = 20
            Width = 109
            Height = 17
            Caption = 'Carnês Pendentes'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clGray
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
          object lblCard2Value: TLabel
            Left = 20
            Top = 45
            Width = 28
            Height = 32
            Caption = '14'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = 33023
            Font.Height = -24
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
        end
        object pnlCard3: TPanel
          AlignWithMargins = True
          Left = 560
          Top = 10
          Width = 240
          Height = 100
          Margins.Left = 0
          Margins.Top = 0
          Margins.Right = 0
          Margins.Bottom = 0
          Align = alClient
          BevelOuter = bvNone
          Color = clWhite
          ParentBackground = False
          TabOrder = 2
          object lblCard3Title: TLabel
            Left = 20
            Top = 20
            Width = 87
            Height = 17
            Caption = 'Novos Clientes'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clGray
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
          object lblCard3Value: TLabel
            Left = 20
            Top = 45
            Width = 14
            Height = 32
            Caption = '3'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = 10053120
            Font.Height = -24
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
        end
      end
    end
    object pnlPageCadastros: TPanel
      Left = 20
      Top = 20
      Width = 800
      Height = 610
      Align = alClient
      BevelOuter = bvNone
      Color = 16119285
      ParentBackground = False
      TabOrder = 1
      Visible = False
      object FlowPanelCadastros: TFlowPanel
        Left = 0
        Top = 0
        Width = 800
        Height = 610
        Align = alClient
        BevelOuter = bvNone
        Color = 16119285
        ParentBackground = False
        TabOrder = 0
      end
    end
  end
end
