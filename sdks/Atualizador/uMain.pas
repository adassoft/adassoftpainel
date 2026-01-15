unit uMain;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Variants, System.Classes, Vcl.Graphics,
  Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.StdCtrls, Vcl.ExtCtrls, Vcl.Buttons, Vcl.ComCtrls,
  Shield.Core, Shield.Config, Shield.Types, IdHTTP, IdSSLOpenSSL, IdComponent,
  System.Zip, System.IOUtils, ShellAPI, System.IniFiles, System.Win.Registry;

type
  TfrmMain = class(TForm)
    pnlBorder: TPanel;
    pnlBack: TPanel;
    pnlTitle: TPanel;
    lblTitle: TLabel;
    btnCloseX: TSpeedButton;
    lblVersion: TLabel;
    lblStatusTitle: TLabel;
    lblStatusDetail: TLabel;
    pnlProgress: TPanel;
    lblProgFile: TLabel;
    lblFilePct: TLabel;
    pbFile: TProgressBar;
    lblProgTotal: TLabel;
    lblTotalPct: TLabel;
    pbTotal: TProgressBar;
    pnlDownload: TPanel;
    lblDlInstr1: TLabel;
    lblDlInstr2: TLabel;
    lblDownloadStatus: TLabel;
    pbDownload: TProgressBar;
    chkServidor: TCheckBox;
    chkEstacao: TCheckBox;
    chkBackupDB: TCheckBox;
    chkBackupExe: TCheckBox;
    pnlInfoBox: TPanel;
    lblInstructions: TLabel;
    memLog: TMemo;
    pnlBtnAtualizar: TPanel;
    btnAtualizar: TSpeedButton;
    pnlBtnCancelar: TPanel;
    btnCancelar: TSpeedButton;
    pnlBtnFechar: TPanel;
    btnFechar: TSpeedButton;
    procedure btnFecharClick(Sender: TObject);
    procedure btnCancelarClick(Sender: TObject);
    procedure btnAtualizarClick(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure pnlTitleMouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
  private
    { Private declarations }
    FShield: TShield;
    FConfig: TShieldConfig;
    
    procedure Log(const Msg: string);
    procedure PrepareFilesForUpdate(const BackupFolder: string);
    function FindFirebirdTool(const ToolName: string): string;
    function FindGbak: string;
    function RunGbak(const Source, Target: string): Boolean;
    function RunIsql(const DbPath, ScriptPath: string): Boolean;
    function BackupDatabases(const BackupFolder: string): Boolean;
    function UpdateDatabases: Boolean;
    function DownloadFile(const Url, DestFile: string): Boolean;
    procedure ExtractUpdate(const ZipFile: string);
    procedure IdHTTPWorkBegin(ASender: TObject; AWorkMode: TWorkMode; AWorkCountMax: Int64);
    procedure IdHTTPWork(ASender: TObject; AWorkMode: TWorkMode; AWorkCount: Int64);
  public
    { Public declarations }
  end;

var
  frmMain: TfrmMain;

implementation

{$R *.dfm}

const
  API_BASE = 'https://adassoft.com/api/v1/adassoft';
  SOFTWARE_ID = 1; 
  API_KEY = '5c8'+'59c8'+'72'+'e798'+'b747'+'1b'+'80e17'+'6fba'+'2f'+'7599ed'+'d2f'+'596c'+'a47'+'9'+'418bf'+'21'+'c495'+'5c8'+'7a'+'7';

procedure TfrmMain.FormCreate(Sender: TObject);
begin
  // Initialize Shield to check for updates
  // Only minimal config needed for Update Check
  FConfig := TShieldConfig.Create(API_BASE, API_KEY, SOFTWARE_ID, '0.0.0.0', '');
  FShield := TShield.Create(FConfig);
  
  chkServidor.Checked := True;
  Log('Assistente de Atualização iniciado.');
  Log('Selecione as opções acima e clique em "Atualizar".');
end;

procedure TfrmMain.FormDestroy(Sender: TObject);
begin
  FShield.Free;
end;

procedure TfrmMain.Log(const Msg: string);
begin
  memLog.Lines.Add(Msg);
  SendMessage(memLog.Handle, EM_SCROLL, SB_BOTTOM, 0);
end;

procedure TfrmMain.btnFecharClick(Sender: TObject);
begin
  Close;
end;

procedure TfrmMain.btnCancelarClick(Sender: TObject);
begin
  Close;
end;

procedure TfrmMain.btnAtualizarClick(Sender: TObject);
var
  Info: TUpdateInfo;
  ZipPath: string;
  BackupFolder: string;
begin
  // Switch to Log View
  lblInstructions.Visible := False;
  memLog.Visible := True;

  btnAtualizar.Enabled := False;
  btnCancelar.Enabled := False;
  btnFechar.Enabled := False;
  
  try
    Log('--------------------------------------------------');
    Log('Verificando atualizações...');
    
    Info := FShield.CheckForUpdate('0.0.0.0');
    
    if not Info.UpdateAvailable then
    begin
      Log('Erro: Nenhuma atualização disponível no servidor.');
      ShowMessage('Nenhuma atualização encontrada.');
      btnAtualizar.Enabled := True;
      btnCancelar.Enabled := True;
      btnFechar.Enabled := True;
      Exit;
    end;
    
    Log('Versão encontrada: ' + Info.Version);
    Log('Tamanho: ' + Info.Size);
    
    // Define Pasta de Backup Única
    BackupFolder := TPath.Combine(GetCurrentDir, 'olderversao_' + FormatDateTime('yyyymmdd_hhmmss', Now));
    if not TDirectory.Exists(BackupFolder) then
       TDirectory.CreateDirectory(BackupFolder);

    // 1. Backup Banco de Dados (Configuração SYSEMP.DAT)
    if chkBackupDB.Checked then
    begin
       if not BackupDatabases(BackupFolder) then
       begin
         Log('--------------------------------------------------');
         Log('ERRO CRÍTICO: O backup do banco de dados falhou.');
         Log('A atualização foi abortada para segurança dos seus dados.');
         ShowMessage('Erro no backup do Banco de Dados.' + sLineBreak + 'Verifique os logs para mais detalhes.');
         
         btnAtualizar.Enabled := True;
         btnCancelar.Enabled := True;
         btnFechar.Enabled := True;
         Exit;
       end;
    end;
       
    // 2. Download
    ZipPath := TPath.Combine(TPath.GetTempPath, 'update_pkg.zip');
    if TFile.Exists(ZipPath) then TFile.Delete(ZipPath);
    
    Log('Baixando pacote de atualização...');
    if DownloadFile(Info.DownloadURL, ZipPath) then
    begin
       Log('Download concluído.');
       
       // 3. Prepare (Move Executáveis para Backup)
       Log('Preparando arquivos (Movendo antigos)...');
       PrepareFilesForUpdate(BackupFolder);

       Log('Extraindo nova versão...');
       ExtractUpdate(ZipPath);
       
       // 4. Update Databases
       Log('Atualizando banco de dados...');
       UpdateDatabases;
       
       Log('--------------------------------------------------');
       Log('ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!');
       ShowMessage('Atualização concluída com sucesso! O sistema será reiniciado.');
       
       var MainExe := 'ProjetoTest.exe';
       if FileExists(MainExe) then
         ShellExecute(0, 'open', PChar(MainExe), nil, nil, SW_SHOWNORMAL);
       
       Close;
    end
    else
    begin
       Log('Erro ao baixar o arquivo.');
       ShowMessage('Falha no download.');
    end;

  except
    on E: Exception do
    begin
      Log('ERRO CRÍTICO: ' + E.Message);
      ShowMessage('Ocorreu um erro: ' + E.Message);
    end;
  end;
  
  btnAtualizar.Enabled := True;
  btnCancelar.Enabled := True;
  btnFechar.Enabled := True;
end;

procedure TfrmMain.PrepareFilesForUpdate(const BackupFolder: string);
var
  Files: TStringList;
  TotalSize, CurrentTotal: Int64;
  I: Integer;
  SelfName: string;
 
  procedure AddFiles(const Pattern: string);
  var
    SR: TSearchRec;
  begin
    if FindFirst(Pattern, faAnyFile, SR) = 0 then
    begin
      repeat
        if (SR.Name <> '.') and (SR.Name <> '..') and (not SameText(SR.Name, SelfName)) then
        begin
          Files.Add(SR.Name);
          TotalSize := TotalSize + SR.Size;
        end;
      until FindNext(SR) <> 0;
      FindClose(SR);
    end;
  end;

  procedure CopyWithProgress(const FileName: string);
  var
    Src, Dest: string;
    SourceStream, DestStream: TFileStream;
    Buffer: array[0..64*1024-1] of Byte; // 64KB
    BytesRead: Integer;
    FileSize, Copied: Int64;
  begin
    Src := FileName;
    Dest := TPath.Combine(BackupFolder, FileName);
    
    lblProgFile.Caption := 'Progresso do arquivo "' + FileName + '":';
    pbFile.Position := 0;
    lblFilePct.Caption := '0 %';
    Application.ProcessMessages;

    FileSize := 0;
    Copied := 0;

    try
      SourceStream := TFileStream.Create(Src, fmOpenRead or fmShareDenyNone);
      try
         FileSize := SourceStream.Size;
         DestStream := TFileStream.Create(Dest, fmCreate);
         try
            repeat
               BytesRead := SourceStream.Read(Buffer, SizeOf(Buffer));
               if BytesRead > 0 then
               begin
                  DestStream.WriteBuffer(Buffer, BytesRead);
                  Copied := Copied + BytesRead;
                  CurrentTotal := CurrentTotal + BytesRead;
                  
                  if FileSize > 0 then
                  begin
                     pbFile.Position := Round((Copied / FileSize) * 100);
                     lblFilePct.Caption := FormatFloat('0.00 %', (Copied / FileSize) * 100);
                  end;
                  
                  if TotalSize > 0 then
                  begin
                     pbTotal.Position := Round((CurrentTotal / TotalSize) * 100);
                     lblTotalPct.Caption := FormatFloat('0.00 %', (CurrentTotal / TotalSize) * 100);
                  end;
                  
                  Application.ProcessMessages;
               end;
            until BytesRead = 0;
         finally
            DestStream.Free;
         end;
      finally
         SourceStream.Free;
      end;
      
      if not DeleteFile(Src) then
      begin
         if FileExists(Src + '.old') then DeleteFile(Src + '.old');
         RenameFile(Src, Src + '.old');
      end;

    except
      on E: Exception do
         Log('Erro copiando ' + FileName + ': ' + E.Message);
    end;
  end;

begin
  SelfName := ExtractFileName(ParamStr(0));
  Files := TStringList.Create;
  
  // Setup UI Progress
  memLog.Visible := False;
  pnlProgress.Visible := True;
  lblStatusTitle.Caption := 'Aguarde ...';
  lblStatusDetail.Caption := 'Fazendo cópia dos arquivos da versão antiga';
  pbTotal.Position := 0;
  lblTotalPct.Caption := '0 %';
  Application.ProcessMessages;

  try
    TotalSize := 0;
    CurrentTotal := 0;
    
    AddFiles('*.dll');
    AddFiles('*.exe');
    
    for I := 0 to Files.Count - 1 do
    begin
       CopyWithProgress(Files[I]);
    end;
    
  finally
    Files.Free;
    pnlProgress.Visible := False;
    memLog.Visible := True;
  end;
end;

function TfrmMain.FindFirebirdTool(const ToolName: string): string;
var
  Reg: TRegistry;
  InstallPath: string;
  Candidate, P: string;
  Paths: TStringList;
begin
  Result := ToolName; 
  
  if FileExists(GetCurrentDir + '\' + ToolName) then Exit(GetCurrentDir + '\' + ToolName);
  
  Reg := TRegistry.Create(KEY_READ);
  try
    Reg.RootKey := HKEY_LOCAL_MACHINE;
    if Reg.OpenKeyReadOnly('SOFTWARE\Firebird Project\Firebird Server\Instances') then
    begin
       InstallPath := Reg.ReadString('DefaultInstance');
       if (InstallPath <> '') and FileExists(TPath.Combine(InstallPath, 'bin\' + ToolName)) then
           Exit(TPath.Combine(InstallPath, 'bin\' + ToolName));
       if (InstallPath <> '') and FileExists(TPath.Combine(InstallPath, ToolName)) then
           Exit(TPath.Combine(InstallPath, ToolName));
    end;
  finally
    Reg.Free;
  end;
  
  Paths := TStringList.Create;
  try
    Paths.Add('C:\Program Files\Firebird\Firebird_5_0');
    Paths.Add('C:\Program Files\Firebird\Firebird_4_0');
    Paths.Add('C:\Program Files\Firebird\Firebird_3_0');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_5_0');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_4_0');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_3_0');
    Paths.Add('C:\Program Files\Firebird\Firebird_2_5');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_2_5');
    Paths.Add('C:\Program Files\Firebird\Firebird_2_1');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_2_1');
    Paths.Add('C:\Program Files\Firebird\Firebird_2_0');
    Paths.Add('C:\Program Files\Firebird\Firebird_1_5');
    Paths.Add('C:\Program Files (x86)\Firebird\Firebird_1_5');
    
    for P in Paths do
    begin
       Candidate := TPath.Combine(P, 'bin\' + ToolName);
       if FileExists(Candidate) then Exit(Candidate);
       Candidate := TPath.Combine(P, ToolName);
       if FileExists(Candidate) then Exit(Candidate);
    end;
  finally
    Paths.Free;
  end;
end;

function TfrmMain.FindGbak: string;
begin
  Result := FindFirebirdTool('gbak.exe');
end;

function TfrmMain.RunGbak(const Source, Target: string): Boolean;
var
  CmdLine: string;
  Security: TSecurityAttributes;
  ReadPipe, WritePipe: THandle;
  Start: TStartupInfo;
  ProcessInfo: TProcessInformation;
  BytesRead, ExitCode: DWORD;
  Buffer: array[0..1023] of AnsiChar;
  TotalOutput: string;
  GbakPath: string;
  Line: string;
begin
  Result := False;
  GbakPath := FindGbak;
  if GbakPath = '' then GbakPath := 'gbak.exe';
  
  Security.nLength := SizeOf(TSecurityAttributes);
  Security.bInheritHandle := True;
  Security.lpSecurityDescriptor := nil;

  if not CreatePipe(ReadPipe, WritePipe, @Security, 0) then
  begin
    Log('Erro interno: Falha ao criar pipe.');
    Exit;
  end;

  try
    FillChar(Start, SizeOf(Start), 0);
    Start.cb := SizeOf(Start);
    Start.dwFlags := STARTF_USESTDHANDLES or STARTF_USESHOWWINDOW;
    Start.hStdOutput := WritePipe;
    Start.hStdError := WritePipe;
    Start.wShowWindow := SW_HIDE;

    CmdLine := Format('"%s" -b -v -user SYSDBA -password masterkey "%s" "%s"', 
                      [GbakPath, Source, Target]);

    if CreateProcess(nil, PChar(CmdLine), nil, nil, True, 0, nil, nil, Start, ProcessInfo) then
    begin
      CloseHandle(WritePipe);
      WritePipe := 0;

      TotalOutput := '';
      while ReadFile(ReadPipe, Buffer, SizeOf(Buffer) - 1, BytesRead, nil) do
      begin
        if BytesRead > 0 then
        begin
          Buffer[BytesRead] := #0;
          TotalOutput := TotalOutput + string(Buffer);
          Application.ProcessMessages;
        end;
      end;

      WaitForSingleObject(ProcessInfo.hProcess, INFINITE);
      if GetExitCodeProcess(ProcessInfo.hProcess, ExitCode) then
         Result := (ExitCode = 0);
         
      CloseHandle(ProcessInfo.hProcess);
      CloseHandle(ProcessInfo.hThread);
      
      var Lines := TotalOutput.Split([#13, #10]);
      for Line in Lines do
      begin
         if Trim(Line) <> '' then
         begin
            if (Pos('gbak:transportable backup', Line) > 0) or 
               (Pos('gbak:backup file is', Line) > 0) or
               (Pos('gbak:closing file', Line) > 0) or
               (Pos('error', LowerCase(Line)) > 0) then
               Log('   > ' + Trim(Line));
         end;
      end;
    end
    else
    begin
      Log('Erro ao executar GBAK: ' + SysErrorMessage(GetLastError));
    end;
  finally
    if WritePipe <> 0 then CloseHandle(WritePipe);
    CloseHandle(ReadPipe);
  end;
end;

function TfrmMain.RunIsql(const DbPath, ScriptPath: string): Boolean;
var
  CmdLine: string;
  Security: TSecurityAttributes;
  ReadPipe, WritePipe: THandle;
  Start: TStartupInfo;
  ProcessInfo: TProcessInformation;
  BytesRead, ExitCode: DWORD;
  Buffer: array[0..1023] of AnsiChar;
  TotalOutput: string;
  IsqlPath: string;
  Line: string;
begin
  Result := False;
  IsqlPath := FindFirebirdTool('isql.exe');
  
  Security.nLength := SizeOf(TSecurityAttributes);
  Security.bInheritHandle := True;
  Security.lpSecurityDescriptor := nil;

  if not CreatePipe(ReadPipe, WritePipe, @Security, 0) then
  begin
    Log('Erro interno: Falha ao criar pipe para ISQL.');
    Exit;
  end;

  try
    FillChar(Start, SizeOf(Start), 0);
    Start.cb := SizeOf(Start);
    Start.dwFlags := STARTF_USESTDHANDLES or STARTF_USESHOWWINDOW;
    Start.hStdOutput := WritePipe;
    Start.hStdError := WritePipe;
    Start.wShowWindow := SW_HIDE;

    CmdLine := Format('"%s" -user SYSDBA -password masterkey -input "%s" "%s"', 
                      [IsqlPath, ScriptPath, DbPath]);

    if CreateProcess(nil, PChar(CmdLine), nil, nil, True, 0, nil, nil, Start, ProcessInfo) then
    begin
      CloseHandle(WritePipe);
      WritePipe := 0;

      TotalOutput := '';
      while ReadFile(ReadPipe, Buffer, SizeOf(Buffer) - 1, BytesRead, nil) do
      begin
        if BytesRead > 0 then
        begin
          Buffer[BytesRead] := #0;
          TotalOutput := TotalOutput + string(Buffer);
          Application.ProcessMessages;
        end;
      end;

      WaitForSingleObject(ProcessInfo.hProcess, INFINITE);
      if GetExitCodeProcess(ProcessInfo.hProcess, ExitCode) then
         Result := (ExitCode = 0);
         
      CloseHandle(ProcessInfo.hProcess);
      CloseHandle(ProcessInfo.hThread);
      
      var Lines := TotalOutput.Split([#13, #10]);
      // Silencioso: não logar output detalhado
      {
      for Line in Lines do
      begin
         if Trim(Line) <> '' then Log('   > ' + Trim(Line));
      end;
      }
    end
    else
    begin
      Log('Erro ao executar ISQL: ' + SysErrorMessage(GetLastError));
    end;
  finally
    if WritePipe <> 0 then CloseHandle(WritePipe);
    CloseHandle(ReadPipe);
  end;
end;

function TfrmMain.UpdateDatabases: Boolean;
var
  Ini: TMemIniFile;
  Sections: TStringList;
  I: Integer;
  Section, DBPath: string;
  ScriptPath: string;
begin
  Result := True;
  ScriptPath := TPath.Combine(GetCurrentDir, 'atualiza.sql');
  
  if not FileExists(ScriptPath) then
  begin
    Log('Info: Script "atualiza.sql" não encontrado. Pulei atualização de banco.');
    Exit;
  end;

  lblStatusTitle.Caption := 'Aguarde ...';
  lblStatusDetail.Caption := 'Atualizando bancos de dados...';
  memLog.Lines.Clear;
  memLog.Visible := True;
  lblInstructions.Visible := False;
  pnlDownload.Visible := False;
  pnlProgress.Visible := False;
  Application.ProcessMessages;

  if not FileExists('SYSEMP.DAT') then Exit;

  Ini := TMemIniFile.Create(GetCurrentDir + '\SYSEMP.DAT');
  Sections := TStringList.Create;
  try
    Ini.ReadSections(Sections);
    for I := 0 to Sections.Count - 1 do
    begin
       Section := Sections[I];
       DBPath := Ini.ReadString(Section, 'Base 0000', '');
       
       if (DBPath <> '') and FileExists(DBPath) then
       begin
          lblStatusDetail.Caption := 'Atualizando banco ' + Section;
          Log('Atualizando banco [' + Section + '] com script SQL...');
          Application.ProcessMessages;
          
          // Executa ISQL ignorando erros (para scripts não idempotentes)
          RunIsql(DBPath, ScriptPath);
          Log('   -> Processo concluído.');
       end;
    end;
  finally
    Sections.Free;
    Ini.Free;
  end;
end;

function TfrmMain.BackupDatabases(const BackupFolder: string): Boolean;
var
  Ini: TMemIniFile;
  Sections: TStringList;
  I: Integer;
  Section, DBPath, Dest: string;
  GbakPath: string;
begin
  Result := True;
  
  lblStatusTitle.Visible := True;
  lblStatusDetail.Visible := True;
  memLog.Lines.Clear; 
  
  if not FileExists('SYSEMP.DAT') then
  begin
    Log('Arquivo de configuração SYSEMP.DAT não encontrado.');
    Exit;
  end;

  GbakPath := FindGbak;
  if GbakPath = '' then
  begin
     Log('ERRO: Não encontrei utility gbak.exe (Firebird).');
     Log('Instale o Firebird ou verifique o PATH.');
     Result := False;
     Exit;
  end;
  Log('Usando Firebird Tool: ' + GbakPath);

  Ini := TMemIniFile.Create(GetCurrentDir + '\SYSEMP.DAT');
  Sections := TStringList.Create;
  try
    Ini.ReadSections(Sections);
    for I := 0 to Sections.Count - 1 do
    begin
       Section := Sections[I];
       DBPath := Ini.ReadString(Section, 'Base 0000', '');
       
       if (DBPath <> '') and FileExists(DBPath) then
       begin
          lblStatusDetail.Caption := 'Realizando backup ' + Section;
          Application.ProcessMessages;
          
          Dest := TPath.Combine(BackupFolder, ChangeFileExt(ExtractFileName(DBPath), '.gbk'));
          Log('Backup GBAK [' + Section + ']: ' + ExtractFileName(DBPath));
          
          if not RunGbak(DBPath, Dest) then
          begin
             Log('   -> FALHA ao executar GBAK. Verifique erros acima.');
             Result := False; 
             Break;
          end
          else
             Log('   -> Backup OK.');
             
          Sleep(500);
       end;
    end;
  finally
    Sections.Free;
    Ini.Free;
  end;
end;

function TfrmMain.DownloadFile(const Url, DestFile: string): Boolean;
var
  Http: TIdHTTP;
  SSL: TIdSSLIOHandlerSocketOpenSSL;
  Stream: TFileStream;
begin
  Result := False;
  
  // Setup UI
  memLog.Visible := False;
  pnlDownload.Visible := True;
  lblStatusTitle.Caption := 'Aguarde ...';
  lblStatusDetail.Caption := 'Realizando download do arquivo de instalação';
  lblDownloadStatus.Caption := 'Iniciando...';
  pbDownload.Position := 0;
  
  Application.ProcessMessages;

  Http := TIdHTTP.Create(nil);
  SSL := TIdSSLIOHandlerSocketOpenSSL.Create(Http);
  Stream := nil;
  try
    SSL.SSLOptions.Mode := sslmClient;
    SSL.SSLOptions.SSLVersions := [sslvTLSv1_2];
    Http.IOHandler := SSL;
    Http.HandleRedirects := True; 
    
    // Attach Events
    Http.OnWorkBegin := IdHTTPWorkBegin;
    Http.OnWork := IdHTTPWork;

    Stream := TFileStream.Create(DestFile, fmCreate);
    Http.Get(Url, Stream);
    Result := True;
  except
    on E: Exception do
      Log('Erro Download: ' + E.Message);
  end;
  Http.Free;
  Stream.Free;
  
  // Restore UI
  pnlDownload.Visible := False;
  memLog.Visible := True;
  lblTitle.Caption := '   Atualização Super Carnê'; // Restore Title
end;

procedure TfrmMain.IdHTTPWorkBegin(ASender: TObject; AWorkMode: TWorkMode; AWorkCountMax: Int64);
begin
  if AWorkMode = wmRead then
  begin
     pbDownload.Max := Integer(AWorkCountMax); // Cast to Integer
     pbDownload.Position := 0;
  end;
end;

procedure TfrmMain.IdHTTPWork(ASender: TObject; AWorkMode: TWorkMode; AWorkCount: Int64);
var
  Pct: Integer;
begin
  if (AWorkMode = wmRead) and (pbDownload.Max > 0) then
  begin
     pbDownload.Position := Integer(AWorkCount);
     Pct := Round((AWorkCount / pbDownload.Max) * 100);
     
     lblDownloadStatus.Caption := Format('Baixando ... %s KBs', [FormatFloat('#,##0', AWorkCount / 1024)]);
     lblTitle.Caption := Format('   Download em ... %d%%', [Pct]);
     
     Application.ProcessMessages;
  end;
end;

procedure TfrmMain.ExtractUpdate(const ZipFile: string);
begin
  // Extract to current directory (overwriting files)
  // Note: Main app must be closed.
  try
    TZipFile.ExtractZipFile(ZipFile, GetCurrentDir);
  except
    on E: Exception do
       Log('Erro na extração: ' + E.Message);
  end;
end;

procedure TfrmMain.pnlTitleMouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
begin
  if Button = mbLeft then
  begin
    ReleaseCapture;
    Perform(WM_SYSCOMMAND, $F012, 0);
  end;
end;

end.
