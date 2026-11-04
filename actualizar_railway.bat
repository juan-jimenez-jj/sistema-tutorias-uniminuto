@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Subiendo los cambios a Railway...
call railway up --service web --ci
pause
