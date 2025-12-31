$source = "c:\xampp\htdocs\adassoft"
$destination = "c:\xampp\htdocs\adassoft_deploy"

# Limpa destino se existir
if (Test-Path $destination) {
    Remove-Item -Recurse -Force $destination
}
New-Item -ItemType Directory -Force -Path $destination | Out-Null

Write-Host "Iniciando cópia dos arquivos..." -ForegroundColor Cyan

# Lista de exclusões (como string array para passar ao robocopy ou lógica manual)
# Robocopy é mais rápido e robusto para isso no Windows
$excludeDirs = @(".git", ".github", "node_modules", "vendor", "tests")
$excludeFiles = @(".env", ".gitignore", "phpunit.xml", "README.md", "prepare_deploy.ps1")

# Robocopy command construction
# /MIR :: Espelha árvore de diretórios (equivalente a /E /PURGE).
# /XD :: Exclui diretórios correspondentes aos nomes e caminhos desginados.
# /XF :: Exclui arquivos correspondentes aos nomes e caminhos designados.
$cmdArgs = @($source, $destination, "/MIR", "/XD", $excludeDirs, "/XF", $excludeFiles)

# Executa Robocopy
# Nota: Robocopy retorna exit codes que PowerShell pode interpretar como falha se não tratado, mas aqui só queremos rodar.
& robocopy $source $destination /MIR /XD .git .github node_modules vendor tests /XF .env .env.example .gitignore phpunit.xml README.md prepare_deploy.ps1

Write-Host "Limpeza de arquivos temporários no destino..." -ForegroundColor Yellow

# Limpar conteúdo de storage/ mas manter estrutura
$storageDirs = @(
    "$destination\storage\framework\cache\data",
    "$destination\storage\framework\sessions",
    "$destination\storage\framework\testing",
    "$destination\storage\framework\views",
    "$destination\storage\logs"
)

foreach ($dir in $storageDirs) {
    if (Test-Path $dir) {
        Get-ChildItem -Path $dir -Recurse | Remove-Item -Force -Recurse
        # Cria arquivo .gitignore vazio para manter a pasta se estiver vazia (hack comum, ou apenas deixa vazia)
        New-Item -ItemType File -Path "$dir\.gitkeep" -Force | Out-Null
    }
}

Write-Host "Cópia concluída com sucesso!" -ForegroundColor Green
Write-Host "Os arquivos para envio estão em: $destination" -ForegroundColor Green
Write-Host "Não se esqueça de rodar 'composer install' e 'npm install && npm run build' no servidor (se aplicável), e configurar o .env!" -ForegroundColor White
