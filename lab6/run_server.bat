@echo off
chcp 65001
cd /d "%~dp0"
php -S localhost:8000
pause
