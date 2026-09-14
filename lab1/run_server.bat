@echo off
chcp 65001 > nul
title Локальний сервер PHP — Кулінарна книга (Варіант 6)

:: Перевірка чи доступний php у PATH
where php >nul 2>nul
if %errorlevel% neq 0 (
    set "PATH=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe;%PATH%"
)

echo ========================================================
echo   Локальний сервер PHP (Варіант №6: Кулінарна книга)
echo ========================================================
echo   Адреса: http://localhost:8000
echo.
echo   Для зупинки сервера натисніть Ctrl+C
echo ========================================================

start http://localhost:8000
php -S localhost:8000
pause
