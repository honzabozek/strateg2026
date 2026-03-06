# Lokální deploy na Forpsi přes VS Code SFTP
# Spusť: .\deploy.ps1

Write-Host "🚀 DEPLOY STRATEG2026 NA FORPSI" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Zkontroluj git stav
Write-Host "📋 Kontrola git stavu..." -ForegroundColor Yellow
$gitStatus = git status --porcelain

if ($gitStatus) {
    Write-Host "⚠️  Máš neuložené změny:" -ForegroundColor Red
    git status --short
    Write-Host ""
    $continue = Read-Host "Chceš pokračovat v deployi? (A/N)"
    if ($continue -ne "A") {
        Write-Host "❌ Deploy zrušen" -ForegroundColor Red
        exit
    }
}

# Commit a push na GitHub
Write-Host ""
Write-Host "📤 Push na GitHub..." -ForegroundColor Yellow
git push origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️  Push selhal, pokračuji v lokálním deployi..." -ForegroundColor Yellow
}

# Otevře VS Code command palette pro SFTP upload
Write-Host ""
Write-Host "📁 Spouštím SFTP upload..." -ForegroundColor Yellow
Write-Host ""
Write-Host "✅ V otevřeném VS Code okně:" -ForegroundColor Green
Write-Host "   1. Stiskni F1" -ForegroundColor White
Write-Host "   2. Vyber: SFTP: Upload Project" -ForegroundColor White
Write-Host "   3. Počkej na dokončení uploadu" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Po uploadu otevři: https://www.janbozek.cz/strateg2026/" -ForegroundColor Cyan
Write-Host ""

# Otevři VS Code s příkazem
code . 

Write-Host "✨ Deploy připraven!" -ForegroundColor Green
