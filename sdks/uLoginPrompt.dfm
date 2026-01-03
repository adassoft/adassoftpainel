object frmLoginPrompt: TfrmLoginPrompt
  Left = 0
  Top = 0
  BorderStyle = bsDialog
  Caption = 'Ativar Licença'
  ClientHeight = 200
  ClientWidth = 360
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -12
  Font.Name = 'Segoe UI'
  Font.Style = []
  Position = poScreenCenter
  TextHeight = 15
  object lblInstrucao: TLabel
    Left = 16
    Top = 16
    Width = 328
    Height = 33
    Caption = 'Informe o e-mail e a senha do portal para validar a licença deste ' +
      'computador.'
    WordWrap = True
  end
  object lblEmail: TLabel
    Left = 16
    Top = 64
    Width = 32
    Height = 15
    Caption = 'E-mail'
  end
  object lblSenha: TLabel
    Left = 16
    Top = 112
    Width = 32
    Height = 15
    Caption = 'Senha'
  end
  object edtEmail: TEdit
    Left = 16
    Top = 80
    Width = 328
    Height = 23
    TabOrder = 0
  end
  object edtSenha: TEdit
    Left = 16
    Top = 128
    Width = 328
    Height = 23
    PasswordChar = '*'
    TabOrder = 1
  end
  object chkLembrar: TCheckBox
    Left = 16
    Top = 160
    Width = 185
    Height = 17
    Caption = 'Lembrar credenciais neste computador'
    Checked = True
    State = cbChecked
    TabOrder = 2
  end
  object btnAtivar: TButton
    Left = 188
    Top = 160
    Width = 75
    Height = 25
    Caption = 'Ativar'
    Default = True
    TabOrder = 3
    OnClick = btnAtivarClick
  end
  object btnCancelar: TButton
    Left = 269
    Top = 160
    Width = 75
    Height = 25
    Cancel = True
    Caption = 'Cancelar'
    TabOrder = 4
    OnClick = btnCancelarClick
  end
end
