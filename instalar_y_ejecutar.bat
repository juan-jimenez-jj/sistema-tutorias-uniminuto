@echo off
chcp 65001 >nul
title Sistema de Tutorias UNIMINUTO - instalador
setlocal
cd /d "%~dp0"

echo.
echo  ==============================================
echo   Sistema de Tutorias UNIMINUTO - instalacion
echo  ==============================================
echo.

rem --- PHP 8.2+: se prefiere el XAMPP nuevo en C:\xampp8 ---
if exist "C:\xampp8\php\php.exe" set "PATH=C:\xampp8\php;%PATH%"
where php >nul 2>nul
if errorlevel 1 if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
where php >nul 2>nul
if errorlevel 1 (
  echo [X] No encuentro PHP. Instala XAMPP con PHP 8.2 en C:\xampp8.
  goto fin
)
php -r "exit(PHP_VERSION_ID >= 80200 ? 0 : 1);"
if errorlevel 1 (
  echo [X] Tu PHP es muy antiguo:
  php -r "echo '    version ', PHP_VERSION, PHP_EOL;"
  echo     El proyecto necesita PHP 8.2 o superior. Instala XAMPP 8.2 en la carpeta C:\xampp8
  echo     desde https://www.apachefriends.org/es/download.html y vuelve a ejecutar este archivo.
  goto fin
)
where composer >nul 2>nul
if errorlevel 1 (
  echo [X] Falta Composer. Descargalo de https://getcomposer.org/Composer-Setup.exe
  echo     y cuando pregunte por PHP elige C:\xampp\php\php.exe. Luego vuelve a ejecutar este archivo.
  goto fin
)
where npm >nul 2>nul
if errorlevel 1 (
  echo [X] Falta Node.js. Descarga la version LTS de https://nodejs.org y vuelve a ejecutar este archivo.
  goto fin
)
echo [OK] PHP, Composer y Node encontrados.
echo.
echo Recuerda: MySQL debe estar iniciado en el panel de XAMPP.
echo.

cd backend
if not exist ".env" copy ".env.xampp" ".env" >nul
echo [1/5] Instalando librerias de PHP...
call composer install --no-interaction
if errorlevel 1 goto error
echo [2/5] Creando la base de datos y las tablas...
php bin\console migrate
if errorlevel 1 goto errordb
echo [3/5] Cargando datos de demostracion...
php bin\console db:seed
cd ..\frontend
echo [4/5] Instalando librerias del frontend (puede tardar unos minutos)...
if not exist "node_modules\@fontsource-variable\plus-jakarta-sans" call npm install --no-audit --no-fund
if errorlevel 1 goto error
cd ..
echo [5/5] Iniciando servidores...
start "API Tutorias (no cerrar)" /d "%~dp0backend" cmd /k php -S 127.0.0.1:8080 -t public public/index.php
start "Notificaciones (no cerrar)" /d "%~dp0backend" cmd /k php bin\console outbox:work
start "Web Tutorias (no cerrar)" /d "%~dp0frontend" cmd /k npm run dev -- --host
timeout /t 6 >nul
start "" http://localhost:5173
echo.
echo  Listo. Se abrio http://localhost:5173 en tu navegador.
echo.
echo  Desde el celular (conectado al MISMO wifi) abre http://IP-DE-ESTE-PC:5173
echo  La IP de este PC es la que aparece aqui abajo como "Direccion IPv4":
ipconfig | findstr /i "IPv4"
echo.
echo  Para detener el sistema cierra las tres ventanas negras que se abrieron.
goto fin

:errordb
echo.
echo [X] No pude conectar con MySQL. Abre el panel de XAMPP, dale Start a MySQL y vuelve a ejecutar.
goto fin
:error
echo.
echo [X] Ocurrio un error. Copia el mensaje de arriba y enviaselo a Claude.
:fin
echo.
pause
