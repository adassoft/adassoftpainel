unit uMain;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.ExtCtrls, Vcl.StdCtrls, Vcl.Buttons,
  Vcl.Imaging.pngimage;

type
  TfrmMain = class(TForm)
    // Estrutura Principal
    pnlSidebar: TPanel;
    pnlContent: TPanel;
    pnlHeader: TPanel;
    
    // Header Elements
    lblTitle: TLabel;
    pnlUserInfo: TPanel;
    lblUserName: TLabel;
    imgUserAvatar: TImage;
    
    // Sidebar Elements
    pnlLogo: TPanel;
    imgLogo: TImage;
    lblAppName: TLabel;
    
    // Navegação (Sidebar Buttons Container)
    pnlMenuContainer: TPanel;
    btnMenuDashboard: TSpeedButton;
    btnMenuCadastros: TSpeedButton;
    btnMenuCaixa: TSpeedButton;
    btnMenuRelatorios: TSpeedButton;
    btnMenuConfig: TSpeedButton;
    btnMenuAjuda: TSpeedButton;
    
    // Conteúdo (Simulando Pages)
    pnlPageDashboard: TPanel;
    pnlPageCadastros: TPanel;
    
    // Elementos Dashboard
    lblWelcome: TLabel;
    pnlStats: TGridPanel; // Se disponivel, senao Panel normal com FlowPanel
    pnlCard1: TPanel;
    lblCard1Title: TLabel;
    lblCard1Value: TLabel;
    pnlCard2: TPanel;
    lblCard2Title: TLabel;
    lblCard2Value: TLabel;
    pnlCard3: TPanel;
    lblCard3Title: TLabel;
    lblCard3Value: TLabel;
    
    // Elementos Cadastros (Grid de Opções)
    FlowPanelCadastros: TFlowPanel;
    
    procedure FormCreate(Sender: TObject);
    procedure btnMenuClick(Sender: TObject);
    procedure FormResize(Sender: TObject);
    procedure btnMouseEnter(Sender: TObject);
    procedure btnMouseLeave(Sender: TObject);
  private
    { Private declarations }
    FActiveButton: TSpeedButton;
    procedure ShowPage(PageName: string);
    procedure CreateCadastroCard(const Title, Icon: string);
  public
    { Public declarations }
  end;

var
  frmMain: TfrmMain;

implementation

{$R *.dfm}

procedure TfrmMain.FormCreate(Sender: TObject);
begin
  // Configurações Iniciais
  Self.Caption := 'Super Carnê - Modern UI';
  
  // Simula estado inicial
  ShowPage('Dashboard');
  btnMenuDashboard.Down := True;
  FActiveButton := btnMenuDashboard;
  
  // Cria cards de exemplo na tela de cadastros
  CreateCadastroCard('Clientes', 'users');
  CreateCadastroCard('Vendedores', 'user-tie');
  CreateCadastroCard('Produtos', 'box');
  CreateCadastroCard('Fornecedores', 'truck');
  CreateCadastroCard('Contratos', 'file-contract');
end;

procedure TfrmMain.FormResize(Sender: TObject);
begin
  // Ajustes responsivos se necessário
end;

procedure TfrmMain.btnMenuClick(Sender: TObject);
var
  Btn: TSpeedButton;
begin
  Btn := TSpeedButton(Sender);
  
  // Lógica Visual de Seleção
  if FActiveButton <> nil then
    FActiveButton.Font.Style := [];
    
  Btn.Font.Style := [fsBold];
  FActiveButton := Btn;
  
  // Navegação
  if Btn = btnMenuDashboard then ShowPage('Dashboard')
  else if Btn = btnMenuCadastros then ShowPage('Cadastros')
  else ShowPage('WIP'); // Outras paginas
end;

procedure TfrmMain.ShowPage(PageName: string);
begin
  pnlPageDashboard.Visible := (PageName = 'Dashboard');
  pnlPageCadastros.Visible := (PageName = 'Cadastros');
  
  if PageName = 'Dashboard' then
    lblTitle.Caption := 'Visão Geral'
  else if PageName = 'Cadastros' then
    lblTitle.Caption := 'Cadastros e Registros'
  else
    lblTitle.Caption := PageName;
end;

procedure TfrmMain.CreateCadastroCard(const Title, Icon: string);
var
  Card: TPanel;
  Lbl: TLabel;
begin
  Card := TPanel.Create(FlowPanelCadastros);
  Card.Parent := FlowPanelCadastros;
  Card.Width := 150;
  Card.Height := 100;
  Card.BevelOuter := bvNone;
  Card.Color := clWhite;
  Card.ParentBackground := False;
  Card.Margins.SetBounds(10, 10, 10, 10);
  Card.AlignWithMargins := True;
  Card.Cursor := crHandPoint;
  
  // Efeito de Borda suave (Simulado)
  // Em VCL puro sem skins é limitado, mas podemos usar cores flat
  
  Lbl := TLabel.Create(Card);
  Lbl.Parent := Card;
  Lbl.Align := alClient;
  Lbl.Alignment := taCenter;
  Lbl.Layout := tlCenter;
  Lbl.Caption := Title;
  Lbl.Font.Size := 11;
  Lbl.Font.Name := 'Segoe UI';
end;

procedure TfrmMain.btnMouseEnter(Sender: TObject);
begin
  // Hover Effect
  (Sender as TSpeedButton).Font.Color := $00FF9933; // Laranja/Destaque
end;

procedure TfrmMain.btnMouseLeave(Sender: TObject);
begin
  if Sender <> FActiveButton then
     (Sender as TSpeedButton).Font.Color := clWhite;
end;

end.
