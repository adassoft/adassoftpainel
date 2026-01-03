object frmCadastroExemplo: TfrmCadastroExemplo
  Left = 0
  Top = 0
  BorderStyle = bsDialog
  Caption = 'Cadastro de Clientes (Exemplo)'
  ClientHeight = 406
  ClientWidth = 528
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -12
  Font.Name = 'Segoe UI'
  Font.Style = []
  Position = poScreenCenter
  TextHeight = 15
  object PanelHeader: TPanel
    Left = 0
    Top = 0
    Width = 528
    Height = 56
    Align = alTop
    BevelOuter = bvNone
    Color = clHighlight
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clWhite
    Font.Height = -16
    Font.Name = 'Segoe UI'
    Font.Style = [fsBold]
    ParentBackground = False
    ParentFont = False
    TabOrder = 0
    ExplicitWidth = 500
    object lblTitulo: TLabel
      Left = 16
      Top = 16
      Width = 323
      Height = 21
      Caption = 'Cadastro de Clientes - Tela Exemplificativa'
    end
  end
  object PanelBody: TPanel
    Left = 0
    Top = 56
    Width = 528
    Height = 350
    Align = alClient
    BevelOuter = bvNone
    TabOrder = 1
    ExplicitWidth = 500
    ExplicitHeight = 264
    object lblResumo: TLabel
      Left = 16
      Top = 16
      Width = 165
      Height = 15
      Caption = 'Licen'#195#167'a atual: n'#195#163'o carregada'
    end
    object edtNome: TLabeledEdit
      Left = 16
      Top = 48
      Width = 220
      Height = 23
      EditLabel.Width = 33
      EditLabel.Height = 15
      EditLabel.Caption = 'Nome'
      TabOrder = 0
      Text = ''
    end
    object edtEmail: TLabeledEdit
      Left = 256
      Top = 48
      Width = 225
      Height = 23
      EditLabel.Width = 34
      EditLabel.Height = 15
      EditLabel.Caption = 'E-mail'
      TabOrder = 1
      Text = ''
    end
    object edtTelefone: TLabeledEdit
      Left = 16
      Top = 88
      Width = 220
      Height = 23
      EditLabel.Width = 45
      EditLabel.Height = 15
      EditLabel.Caption = 'Telefone'
      TabOrder = 2
      Text = ''
    end
    object edtCNPJ: TLabeledEdit
      Left = 256
      Top = 88
      Width = 225
      Height = 23
      EditLabel.Width = 27
      EditLabel.Height = 15
      EditLabel.Caption = 'CNPJ'
      TabOrder = 3
      Text = ''
    end
    object edtRazao: TLabeledEdit
      Left = 16
      Top = 128
      Width = 220
      Height = 23
      EditLabel.Width = 73
      EditLabel.Height = 15
      EditLabel.Caption = 'Raz'#195#163'o Social'
      TabOrder = 4
      Text = ''
    end
    object edtSenha: TLabeledEdit
      Left = 256
      Top = 128
      Width = 225
      Height = 23
      EditLabel.Width = 32
      EditLabel.Height = 15
      EditLabel.Caption = 'Senha'
      PasswordChar = '*'
      TabOrder = 5
      Text = ''
    end
    object edtUF: TLabeledEdit
      Left = 16
      Top = 168
      Width = 80
      Height = 23
      EditLabel.Width = 14
      EditLabel.Height = 15
      EditLabel.Caption = 'UF'
      TabOrder = 6
      Text = ''
    end
    object edtLogin: TLabeledEdit
      Left = 120
      Top = 168
      Width = 140
      Height = 23
      EditLabel.Width = 30
      EditLabel.Height = 15
      EditLabel.Caption = 'Login'
      TabOrder = 7
      Text = ''
    end
    object btnCadastrar: TButton
      Left = 256
      Top = 224
      Width = 225
      Height = 33
      Caption = 'Cadastrar'
      TabOrder = 8
      OnClick = btnCadastrarClick
    end
    object btnFechar: TButton
      Left = 16
      Top = 224
      Width = 150
      Height = 33
      Caption = 'Fechar'
      TabOrder = 9
      OnClick = btnFecharClick
    end
  end
end
