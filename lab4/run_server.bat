@echo off
chcp 65001 > nul
title Локальний сервер PHP — Практична №4 (Варіант 6)

where php >nul 2>nul
if %errorlevel% neq 0 (
    set "PATH=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe;%PATH%"
)

echo ========================================================
echo   Практична №4: PDO / MySQL (Кулінарна книга)
echo   Адреса: http://localhost:8000/index.php
echo   Ініціалізація БД: http://localhost:8000/setup.php
echo   Для зупинки сервера натисніть Ctrl+C
echo ========================================================

start http://localhost:8000/index.php
php -S localhost:8000
pause
