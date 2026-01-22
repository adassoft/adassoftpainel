unit uShortcut;

interface

uses
  Windows, SysUtils, Classes, ActiveX, ShlObj, ComObj;

type
  TShortcutLocation = (slDesktop, slStartMenu);

function CreateShortcut(const AppPath, AppArgs, ExeName, Description: string; Location: TShortcutLocation): Boolean;

implementation

function CreateShortcut(const AppPath, AppArgs, ExeName, Description: string; Location: TShortcutLocation): Boolean;
var
  IObject: IUnknown;
  ISLink: IShellLink;
  IPFile: IPersistFile;
  PIDL: PItemIDList;
  InFolder: array[0..MAX_PATH] of Char;
  LinkPath: string;
  LinkName: string;
begin
  Result := False;
  
  // Link Name = ExeName without extension + .lnk
  LinkName := ChangeFileExt(ExeName, '.lnk');

  IObject := CreateComObject(CLSID_ShellLink);
  ISLink := IObject as IShellLink;
  IPFile := IObject as IPersistFile;

  ISLink.SetPath(PChar(AppPath));
  ISLink.SetWorkingDirectory(PChar(ExtractFilePath(AppPath)));
  ISLink.SetArguments(PChar(AppArgs));
  ISLink.SetDescription(PChar(Description));
  
  // Determine Folder
  if Location = slDesktop then
     SHGetSpecialFolderLocation(0, CSIDL_DESKTOPDIRECTORY, PIDL)
  else
     SHGetSpecialFolderLocation(0, CSIDL_PROGRAMS, PIDL);
     
  SHGetPathFromIDList(PIDL, InFolder);
  
  // Combine
  LinkPath := IncludeTrailingPathDelimiter(InFolder) + LinkName;
  
  if SUCCEEDED(IPFile.Save(PChar(LinkPath), False)) then
     Result := True;
end;

end.
