@echo off
echo Compilando Recursos do AdaBackup...
brcc32 adabackup.rc -fo config.res

echo Compilando Instalador...
dcc32 Atualizador.dpr

if exist Atualizador.exe (
    echo Renomeando para AdaBackup_Setup_Web.exe...
    copy /Y Atualizador.exe AdaBackup_Setup_Web.exe
    del Atualizador.exe
    echo Sucesso! Gerado AdaBackup_Setup_Web.exe
) else (
    echo Erro na compilacao!
)
pause
