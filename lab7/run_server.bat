@echo off
chcp 65001 > nul
title Локальний сервер PHP — Практична №7 (Кулінарна книга)

where php >nul 2>nul
if %errorlevel% neq 0 (
    set "PATH=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe;%PATH%"
)

echo ========================================================
echo   Практична робота №7: Оптимізація та профілювання PHP
echo   Адреса: http://localhost:8000/index.php
echo   Майстер БД: http://localhost:8000/setup.php
echo ========================================================

start http://localhost:8000/index.php
php -S localhost:8000
pause
