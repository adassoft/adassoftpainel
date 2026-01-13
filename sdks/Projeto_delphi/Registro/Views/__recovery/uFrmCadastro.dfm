object frmCadastro: TfrmCadastro
  Left = 0
  Top = 0
  Caption = 'Criar Nova Conta Shield'
  ClientHeight = 520
  ClientWidth = 400
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Tahoma'
  Font.Style = []
  Position = poScreenCenter
  TextHeight = 13
  object pnlTopo: TPanel
    Left = 0
    Top = 0
    Width = 400
    Height = 41
    Align = alTop
    BevelOuter = bvNone
    Color = 16744448
    ParentBackground = False
    TabOrder = 0
    ExplicitWidth = 398
    object lblTitulo: TLabel
      Left = 16
      Top = 13
      Width = 131
      Height = 16
      Caption = 'Cadastro de Usu'#225'rio'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clWhite
      Font.Height = -13
      Font.Name = 'Tahoma'
      Font.Style = [fsBold]
      ParentFont = False
    end
  end
  object pnlBottom: TPanel
    Left = 0
    Top = 479
    Width = 400
    Height = 41
    Align = alBottom
    BevelOuter = bvNone
    TabOrder = 1
    ExplicitTop = 471
    ExplicitWidth = 398
    object btnCadastrar: TButton
      Left = 180
      Top = 6
      Width = 200
      Height = 30
      Caption = 'Enviar C'#243'digo de Valida'#231#227'o'
      TabOrder = 0
      OnClick = btnCadastrarClick
    end
    object btnCancelar: TButton
      Left = 90
      Top = 6
      Width = 80
      Height = 30
      Caption = 'Cancelar'
      TabOrder = 1
      OnClick = btnCancelarClick
    end
    object btnReenviar: TButton
      Left = 10
      Top = 6
      Width = 75
      Height = 30
      Caption = 'Reenviar'
      TabOrder = 2
      Visible = False
      OnClick = btnReenviarClick
    end
  end
  object pnlCentro: TPanel
    Left = 0
    Top = 41
    Width = 400
    Height = 438
    Align = alClient
    TabOrder = 2
    ExplicitWidth = 398
    ExplicitHeight = 430
    object Label1: TLabel
      Left = 20
      Top = 15
      Width = 81
      Height = 13
      Caption = 'Nome Completo*'
    end
    object Label2: TLabel
      Left = 20
      Top = 65
      Width = 34
      Height = 13
      Caption = 'E-mail*'
    end
    object Label3: TLabel
      Left = 20
      Top = 115
      Width = 36
      Height = 13
      Caption = 'Senha*'
    end
    object Label4: TLabel
      Left = 20
      Top = 165
      Width = 31
      Height = 13
      Caption = 'CNPJ*'
    end
    object Label5: TLabel
      Left = 20
      Top = 215
      Width = 119
      Height = 13
      Caption = 'Raz'#227'o Social da Empresa'
    end
    object Label6: TLabel
      Left = 20
      Top = 265
      Width = 50
      Height = 13
      Caption = 'WhatsApp'
    end
    object LabelParceiro: TLabel
      Left = 200
      Top = 265
      Width = 117
      Height = 13
      Caption = 'C'#243'd. Parceiro (Opcional)'
    end
    object LabelCodigo: TLabel
      Left = 20
      Top = 325
      Width = 199
      Height = 13
      Caption = 'C'#211'DIGO DE VERIFICA'#199#195'O (E-MAIL)*'
      Font.Charset = DEFAULT_CHARSET
      Font.Color = clRed
      Font.Height = -11
      Font.Name = 'Tahoma'
      Font.Style = [fsBold]
      ParentFont = False
      Visible = False
    end
    object edtNome: TEdit
      Left = 20
      Top = 32
      Width = 350
      Height = 21
      TabOrder = 0
    end
    object edtEmail: TEdit
      Left = 20
      Top = 82
      Width = 350
      Height = 21
      TabOrder = 1
    end
    object edtSenha: TEdit
      Left = 20
      Top = 132
      Width = 350
      Height = 21
      PasswordChar = '*'
      TabOrder = 2
    end
    object edtCNPJ: TEdit
      Left = 20
      Top = 182
      Width = 350
      Height = 21
      TabOrder = 3
    end
    object edtRazao: TEdit
      Left = 20
      Top = 232
      Width = 350
      Height = 21
      TabOrder = 4
    end
    object edtWhatsapp: TEdit
      Left = 20
      Top = 282
      Width = 160
      Height = 21
      TabOrder = 5
    end
    object edtParceiro: TEdit
      Left = 200
      Top = 282
      Width = 170
      Height = 21
      TabOrder = 6
    end
    object edtCodigo: TEdit
      Left = 20
      Top = 342
      Width = 150
      Height = 21
      TabOrder = 7
      Visible = False
    end
  end
end
