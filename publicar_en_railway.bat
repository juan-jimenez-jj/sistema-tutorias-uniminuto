@echo off
chcp 65001 >nul
title Publicar Sistema de Tutorias en Railway
setlocal
cd /d "%~dp0"
echo.
echo  ====================================================
echo   Publicar el Sistema de Tutorias en internet (Railway)
echo  ====================================================
echo.
where npm >nul 2>nul
if errorlevel 1 ( echo [X] Falta Node.js. & goto fin )
where railway >nul 2>nul
if errorlevel 1 (
  echo Instalando la herramienta de Railway...
  call npm install -g @railway/cli
  if errorlevel 1 goto error
)
echo.
echo [1/5] Inicia sesion: se abrira el navegador. Entra con GitHub o con tu correo y vuelve aqui.
call railway login
if errorlevel 1 goto error
echo.
echo [2/5] Creando el proyecto...
call railway init --name tutorias-uniminuto
if errorlevel 1 goto error
echo.
echo [3/5] Creando la base de datos MySQL...
call railway add --database mysql
if errorlevel 1 goto error
echo.
echo [4/5] Creando el servicio web y subiendo el sistema (tarda de 3 a 6 minutos)...
call railway add --service web --variables "MYSQL_URL=${{MySQL.MYSQL_URL}}"
if errorlevel 1 goto error
call railway up --service web --ci
if errorlevel 1 goto error
echo.
echo [5/5] Generando el link publico...
call railway domain --service web
echo.
echo  Listo. El link de arriba (https://....up.railway.app) es el que compartes con tu grupo.
echo  Si al abrirlo dice "Application failed to respond", espera 1 minuto: la base de datos se esta llenando.
goto fin
:error
echo.
echo [X] Algo fallo. Toma una captura de esta ventana y enviasela a Claude.
:fin
echo.
pause
