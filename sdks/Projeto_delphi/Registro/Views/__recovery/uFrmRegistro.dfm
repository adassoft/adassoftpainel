object frmRegistro: TfrmRegistro
  Left = 0
  Top = 0
  BorderIcons = [biSystemMenu]
  BorderStyle = bsSingle
  Caption = 'Registro - Sistema'
  ClientHeight = 500
  ClientWidth = 500
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Segoe UI'
  Font.Style = []
  Position = poScreenCenter
  OnCreate = FormCreate
  TextHeight = 13
  object Bevel1: TBevel
    Left = 0
    Top = 448
    Width = 500
    Height = 2
    Align = alBottom
    Shape = bsBottomLine
    ExplicitTop = 388
  end
  object pnlHeader: TPanel
    Left = 0
    Top = 0
    Width = 500
    Height = 41
    Align = alTop
    BevelOuter = bvNone
    Color = clWhite
    ParentBackground = False
    TabOrder = 0
    object lblInstalacaoID: TLabel
      Left = 248
      Top = 13
      Width = 236
      Height = 15
      Alignment = taRightJustify
      AutoSize = False
      Caption = 'C'#243'digo da Instala'#231#227'o: ...'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = 33023
      Font.Height = -12
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object Label1: TLabel
      Left = 16
      Top = 11
      Width = 119
      Height = 17
      Caption = 'Registro de Licen'#231'a'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -13
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
  end
  object pnlLogin: TPanel
    Left = 0
    Top = 41
    Width = 500
    Height = 407
    Align = alClient
    BevelOuter = bvNone
    TabOrder = 1
    object GroupBox1: TGroupBox
      Left = 16
      Top = 6
      Width = 468
      Height = 163
      Caption = ' J'#225' possuo conta / Login '
      TabOrder = 0
      object Label2: TLabel
        Left = 24
        Top = 56
        Width = 35
        Height = 13
        Caption = 'E-mail:'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWindowText
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
      end
      object Label3: TLabel
        Left = 24
        Top = 88
        Width = 35
        Height = 13
        Caption = 'Senha:'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWindowText
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
      end
      object lblEsqueciSenha: TLabel
        Left = 67
        Top = 115
        Width = 97
        Height = 13
        Cursor = crHandPoint
        Caption = 'Esqueceu a senha?'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clBlue
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = [fsUnderline]
        ParentFont = False
        OnClick = lblEsqueciSenhaClick
      end
      object Label4: TLabel
        Left = 24
        Top = 27
        Width = 290
        Height = 13
        Caption = 'Por favor, digite seus dados de login e clique em [Entrar]'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clGrayText
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
      end
      object edtEmail: TEdit
        Left = 67
        Top = 53
        Width = 270
        Height = 21
        TabOrder = 0
      end
      object edtSenha: TEdit
        Left = 67
        Top = 85
        Width = 270
        Height = 21
        PasswordChar = '*'
        TabOrder = 1
      end
      object btnAtivar: TButton
        Left = 352
        Top = 51
        Width = 97
        Height = 57
        Caption = 'Entrar'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWindowText
        Font.Height = -12
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
        TabOrder = 2
        OnClick = btnAtivarClick
      end
    end
    object GroupBoxCadastro: TGroupBox
      Left = 16
      Top = 185
      Width = 468
      Height = 136
      Caption = ' Ainda n'#227'o tem cadastro? '
      Color = clWhite
      ParentBackground = False
      ParentColor = False
      TabOrder = 1
      object LabelInfo: TLabel
        Left = 24
        Top = 32
        Width = 377
        Height = 13
        Caption = 
          'Para ativar ou adquirir uma licen'#231'a, '#233' necess'#225'rio criar uma cont' +
          'a primeiro.'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWindowText
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
      end
      object LabelInfo2: TLabel
        Left = 24
        Top = 51
        Width = 269
        Height = 13
        Caption = #201' r'#225'pido, f'#225'cil e voc'#234' j'#225' sai logado automaticamente.'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clGrayText
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
      end
      object btnCriarConta: TButton
        Left = 24
        Top = 80
        Width = 161
        Height = 41
        Caption = 'Criar Nova Conta'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clGreen
        Font.Height = -13
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
        TabOrder = 0
        OnClick = lblCriarContaClick
      end
    end
  end
  object pnlStatus: TPanel
    Left = 0
    Top = 41
    Width = 500
    Height = 407
    Align = alClient
    BevelOuter = bvNone
    TabOrder = 2
    Visible = False
    object lblStatusTexto: TLabel
      Left = 8
      Top = 38
      Width = 476
      Height = 18
      Alignment = taCenter
      AutoSize = False
      Caption = 'Status: ATIVO'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clGreen
      Font.Height = -13
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object lblDiasRestantes: TLabel
      Left = 112
      Top = 96
      Width = 307
      Height = 19
      Alignment = taCenter
      AutoSize = False
      Caption = '14 dias restantes'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clNavy
      Font.Height = -11
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object lblDataInicio: TLabel
      Left = 24
      Top = 81
      Width = 56
      Height = 13
      Caption = '01/01/2025'
    end
    object lblDataFim: TLabel
      Left = 417
      Top = 81
      Width = 56
      Height = 13
      Alignment = taRightJustify
      Caption = '01/01/2026'
    end
    object lblInfoTerminais: TLabel
      Left = 0
      Top = 118
      Width = 500
      Height = 13
      Alignment = taCenter
      AutoSize = False
      Caption = 'Voc'#234' anexou 1 m'#225'quinas de 10 dispon'#237'veis'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clBlue
      Font.Height = -11
      Font.Name = 'Segoe UI'
      Font.Style = []
      ParentFont = False
    end
    object pnlStatusColor: TPanel
      Left = 144
      Top = 67
      Width = 249
      Height = 7
      BevelOuter = bvNone
      Color = clLime
      ParentBackground = False
      TabOrder = 0
    end
    object pbTempo: TProgressBar
      Left = 112
      Top = 80
      Width = 299
      Height = 15
      TabOrder = 1
    end
    object pbTerminais: TProgressBar
      Left = 112
      Top = 137
      Width = 299
      Height = 15
      TabOrder = 2
    end
    object Memo1: TMemo
      Left = 8
      Top = 175
      Width = 483
      Height = 210
      Lines.Strings = (
        'Memo1')
      TabOrder = 3
    end
  end
  object pnlFooter: TPanel
    Left = 0
    Top = 450
    Width = 500
    Height = 50
    Align = alBottom
    BevelOuter = bvNone
    TabOrder = 3
    object lblSuporteZap: TLabel
      Left = 8
      Top = 8
      Width = 140
      Height = 13
      Caption = 'Suporte Zap: 3899999-9999'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWindowText
      Font.Height = -11
      Font.Name = 'Segoe UI'
      Font.Style = [fsBold]
      ParentFont = False
    end
    object lblSuporteEmail: TLabel
      Left = 8
      Top = 27
      Width = 156
      Height = 13
      Caption = 'E-mail: suporte@adassoft.com'
    end
    object btnFechar: TButton
      Left = 416
      Top = 8
      Width = 75
      Height = 33
      Caption = 'Fechar'
      TabOrder = 0
      OnClick = btnFecharClick
    end
    object btnComprar: TButton
      Left = 328
      Top = 8
      Width = 82
      Height = 33
      Caption = 'Comprar'
      TabOrder = 1
      OnClick = btnComprarClick
    end
    object btnDesvincular: TButton
      Left = 208
      Top = 8
      Width = 114
      Height = 33
      Caption = 'Desabilitar m'#225'quina'
      TabOrder = 2
      OnClick = btnDesvincularClick
    end
  end
end
