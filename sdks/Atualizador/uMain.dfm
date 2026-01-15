object frmMain: TfrmMain
  Left = 0
  Top = 0
  BorderIcons = [biSystemMenu, biMinimize]
  BorderStyle = bsNone
  Caption = 'Update - Super Carn'#234' Escola'
  ClientHeight = 450
  ClientWidth = 600
  Color = 3812898
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Segoe UI'
  Font.Style = []
  Position = poScreenCenter
  OnCreate = FormCreate
  OnDestroy = FormDestroy
  TextHeight = 13
  object pnlBorder: TPanel
    Left = 0
    Top = 0
    Width = 600
    Height = 450
    Align = alClient
    BevelOuter = bvNone
    Color = 2829099
    ParentBackground = False
    TabOrder = 0
    object pnlBack: TPanel
      Left = 0
      Top = 0
      Width = 600
      Height = 450
      Align = alClient
      BevelOuter = bvNone
      Color = 3812898
      ParentBackground = False
      TabOrder = 0
      object lblVersion: TLabel
        Left = 24
        Top = 425
        Width = 69
        Height = 13
        Caption = '1.0.8483.3476'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clSilver
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
      end
      object lblStatusTitle: TLabel
        Left = 30
        Top = 170
        Width = 68
        Height = 17
        Caption = 'Aguarde ...'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = 16768392
        Font.Height = -13
        Font.Name = 'Segoe UI'
        Font.Style = [fsBold]
        ParentFont = False
        Visible = False
      end
      object lblStatusDetail: TLabel
        Left = 30
        Top = 190
        Width = 119
        Height = 17
        Caption = 'Realizando backup...'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -13
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        Visible = False
      end
      object pnlTitle: TPanel
        Left = 0
        Top = 0
        Width = 600
        Height = 35
        Align = alTop
        BevelOuter = bvNone
        Color = 2631720
        ParentBackground = False
        TabOrder = 0
        OnMouseDown = pnlTitleMouseDown
        object lblTitle: TLabel
          Left = 0
          Top = 0
          Width = 550
          Height = 35
          Align = alLeft
          AutoSize = False
          Caption = '   Atualiza'#231#227'o Super Carn'#234
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -16
          Font.Name = 'Segoe UI'
          Font.Style = [fsBold]
          ParentFont = False
          Layout = tlCenter
          OnMouseDown = pnlTitleMouseDown
          ExplicitLeft = 10
        end
        object btnCloseX: TSpeedButton
          Left = 565
          Top = 0
          Width = 35
          Height = 35
          Align = alRight
          Caption = 'X'
          Flat = True
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = [fsBold]
          ParentFont = False
          OnClick = btnFecharClick
          ExplicitLeft = 561
        end
      end
      object chkServidor: TCheckBox
        Left = 40
        Top = 60
        Width = 500
        Height = 17
        Caption = 
          'Servidor (selecione esta op'#231#227'o caso esteja atualizando o program' +
          'a no computador principal)'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        TabOrder = 1
      end
      object chkEstacao: TCheckBox
        Left = 40
        Top = 85
        Width = 530
        Height = 17
        Caption = 
          'Esta'#231#227'o de trabalho (selecione esta op'#231#227'o caso esteja utilizando' +
          ' o programa em rede e este n'#227'o seja o servidor)'
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clWhite
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        TabOrder = 2
      end
      object chkBackupDB: TCheckBox
        Left = 40
        Top = 115
        Width = 300
        Height = 17
        Caption = 'Fazer backup do banco de dados'
        Checked = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clSilver
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        State = cbChecked
        TabOrder = 3
      end
      object chkBackupExe: TCheckBox
        Left = 40
        Top = 140
        Width = 450
        Height = 17
        Caption = 
          'Fazer c'#243'pia dos execut'#225'veis (servir'#227' para voltar para a vers'#227'o a' +
          'nterior)'
        Checked = True
        Font.Charset = DEFAULT_CHARSET
        Font.Color = clSilver
        Font.Height = -11
        Font.Name = 'Segoe UI'
        Font.Style = []
        ParentFont = False
        State = cbChecked
        TabOrder = 4
      end
      object pnlInfoBox: TPanel
        Left = 30
        Top = 215
        Width = 540
        Height = 155
        BevelOuter = bvNone
        Color = 4473924
        ParentBackground = False
        TabOrder = 5
        object lblInstructions: TLabel
          Left = 0
          Top = 0
          Width = 540
          Height = 155
          Align = alClient
          Caption = 
            'N'#227'o '#233' obrigat'#243'rio atualizar o Super Carn'#234', por'#233'm '#233' recomendo man' +
            'ter o programa atualizado.'#13#10#13#10'O Assistente de atualiza'#231#227'o ir'#225' lh' +
            'e ajudar fazendo backup de todos os bancos de dados utilizados p' +
            'elo programa, bem como, fazendo c'#243'pia dos arquivos necess'#225'rio pa' +
            'ra retornar a vers'#227'o anterior, caso seja necess'#225'rio.'#13#10#13#10'Caso pre' +
            'cise de ajuda entre em contato atrav'#233's do e-mail adailton@adasso' +
            'ft.com ou do WhatsApp 38999349155.'
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = []
          ParentFont = False
          WordWrap = True
          ExplicitWidth = 537
          ExplicitHeight = 153
        end
        object memLog: TMemo
          Left = 0
          Top = 0
          Width = 540
          Height = 155
          Align = alClient
          BevelInner = bvNone
          BevelOuter = bvNone
          Color = 4473924
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = []
          ParentFont = False
          ReadOnly = True
          ScrollBars = ssVertical
          TabOrder = 0
          Visible = False
        end
        object pnlProgress: TPanel
          Left = 0
          Top = 0
          Width = 540
          Height = 155
          Align = alClient
          BevelOuter = bvNone
          Color = 3812898
          ParentBackground = False
          TabOrder = 1
          Visible = False
          object lblProgFile: TLabel
            Left = 0
            Top = 10
            Width = 131
            Height = 17
            Caption = 'Progresso do arquivo:'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = []
            ParentFont = False
          end
          object lblFilePct: TLabel
            Left = 518
            Top = 10
            Width = 22
            Height = 17
            Alignment = taRightJustify
            Caption = '0 %'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
          object lblProgTotal: TLabel
            Left = 0
            Top = 70
            Width = 97
            Height = 17
            Caption = 'Progresso geral:'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = []
            ParentFont = False
          end
          object lblTotalPct: TLabel
            Left = 518
            Top = 70
            Width = 22
            Height = 17
            Alignment = taRightJustify
            Caption = '0 %'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = [fsBold]
            ParentFont = False
          end
          object pbFile: TProgressBar
            Left = 0
            Top = 30
            Width = 540
            Height = 25
            TabOrder = 0
          end
          object pbTotal: TProgressBar
            Left = 0
            Top = 90
            Width = 540
            Height = 25
            TabOrder = 1
          end
        end
        object pnlDownload: TPanel
          Left = 0
          Top = 0
          Width = 540
          Height = 155
          Align = alClient
          BevelOuter = bvNone
          Color = 3812898
          ParentBackground = False
          TabOrder = 2
          Visible = False
          object lblDlInstr1: TLabel
            Left = 0
            Top = 10
            Width = 403
            Height = 17
            Caption = 
              '1. Ao concluir o download o assistente de instala'#231#227'o ser'#225' execut' +
              'ado.'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = []
            ParentFont = False
          end
          object lblDlInstr2: TLabel
            Left = 0
            Top = 30
            Width = 504
            Height = 17
            Caption = 
              '2. Para concluir a atualiza'#231#227'o basta ir avan'#231'ando nas telas do a' +
              'ssistente de instala'#231#227'o.'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = []
            ParentFont = False
          end
          object lblDownloadStatus: TLabel
            Left = 0
            Top = 90
            Width = 66
            Height = 17
            Caption = 'Baixando ...'
            Font.Charset = DEFAULT_CHARSET
            Font.Color = clWhite
            Font.Height = -13
            Font.Name = 'Segoe UI'
            Font.Style = []
            ParentFont = False
          end
          object pbDownload: TProgressBar
            Left = 0
            Top = 110
            Width = 540
            Height = 25
            TabOrder = 0
          end
        end
      end
      object pnlBtnAtualizar: TPanel
        Left = 250
        Top = 390
        Width = 110
        Height = 40
        BevelOuter = bvNone
        Color = 2631720
        ParentBackground = False
        TabOrder = 8
        object btnAtualizar: TSpeedButton
          Left = 0
          Top = 0
          Width = 110
          Height = 40
          Align = alClient
          Caption = 'Atualizar'
          Flat = True
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = [fsBold]
          ParentFont = False
          OnClick = btnAtualizarClick
        end
      end
      object pnlBtnCancelar: TPanel
        Left = 370
        Top = 390
        Width = 100
        Height = 40
        BevelOuter = bvNone
        Color = 3812898
        ParentBackground = False
        TabOrder = 6
        object btnCancelar: TSpeedButton
          Left = 0
          Top = 0
          Width = 100
          Height = 40
          Align = alClient
          Caption = 'Cancelar'
          Flat = True
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = []
          ParentFont = False
          OnClick = btnCancelarClick
        end
      end
      object pnlBtnFechar: TPanel
        Left = 480
        Top = 390
        Width = 90
        Height = 40
        BevelOuter = bvNone
        Color = 3812898
        ParentBackground = False
        TabOrder = 7
        object btnFechar: TSpeedButton
          Left = 0
          Top = 0
          Width = 90
          Height = 40
          Align = alClient
          Caption = 'Fechar'
          Flat = True
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWhite
          Font.Height = -13
          Font.Name = 'Segoe UI'
          Font.Style = []
          ParentFont = False
          OnClick = btnFecharClick
        end
      end
    end
  end
end
