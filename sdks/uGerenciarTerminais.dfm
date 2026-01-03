object frmGerenciarTerminais: TfrmGerenciarTerminais
  Left = 0
  Top = 0
  BorderStyle = bsDialog
  Caption = 'Gerenciar instala'#231#245'es'
  ClientHeight = 360
  ClientWidth = 520
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -12
  Font.Name = 'Segoe UI'
  Font.Style = []
  Position = poScreenCenter
  TextHeight = 15
  object lblResumo: TLabel
    Left = 16
    Top = 16
    Width = 488
    Height = 17
    AutoSize = False
    Caption = 'Terminais em uso:'
  end
  object lvTerminais: TListView
    Left = 16
    Top = 40
    Width = 488
    Height = 248
    Columns = <
      item
        Caption = 'Status'
        Width = 70
      end
      item
        Caption = 'Computador'
        Width = 140
      end
      item
        Caption = 'MAC'
        Width = 110
      end
      item
        Caption = 'Última atividade'
        Width = 120
      end
      item
        Caption = 'Instala'#231#227'o'
        Width = 180
      end>
    HideSelection = False
    ReadOnly = True
    RowSelect = True
    TabOrder = 0
    ViewStyle = vsReport
    OnDblClick = lvTerminaisDblClick
    OnSelectItem = lvTerminaisSelectItem
  end
  object btnRemover: TButton
    Left = 16
    Top = 304
    Width = 185
    Height = 33
    Caption = 'Remover instala'#231#227'o'
    TabOrder = 1
    OnClick = btnRemoverClick
  end
  object btnCancelar: TButton
    Left = 319
    Top = 304
    Width = 185
    Height = 33
    Caption = 'Cancelar'
    ModalResult = 2
    TabOrder = 2
  end
end
