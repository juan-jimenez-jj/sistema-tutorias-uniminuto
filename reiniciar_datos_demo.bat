@echo off
chcp 65001 >nul
title Sistema de Tutorias UNIMINUTO - reiniciar datos de demostracion
setlocal
cd /d "%~dp0"

echo.
echo  ==========================================================
echo   Reiniciar los datos de demostracion
echo  ==========================================================
echo.
echo  Borra TODAS las tutorias, usuarios y reservas de prueba y
echo  vuelve a crear los datos de demostracion con fechas de HOY:
echo   - tutorias proximas para el estudiante (cuenta regresiva)
echo   - tutorias por registrar para los docentes
echo   - un semestre completo de historial para los reportes
echo.
echo  Usalo el dia antes de la sustentacion o esa misma manana.
echo  Antes: cierra las ventanas negras del sistema y deja MySQL
echo  iniciado en el panel de XAMPP.
echo.
set /p RESP=Escribe SI para continuar: 
if /i not "%RESP%"=="SI" (
  echo No se hizo ningun cambio.
  goto fin
)

if exist "C:\xampp8\php\php.exe" set "PATH=C:\xampp8\php;%PATH%"
where php >nul 2>nul
if errorlevel 1 if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
where php >nul 2>nul
if errorlevel 1 (
  echo [X] No encuentro PHP. Instala XAMPP con PHP 8.2 en C:\xampp8.
  goto fin
)

cd backend
if not exist ".env" copy ".env.xampp" ".env" >nul
echo.
echo [1/3] Borrando las tablas...
php bin\console db:reset
if errorlevel 1 goto errordb
echo [2/3] Creando las tablas...
php bin\console migrate
if errorlevel 1 goto errordb
echo [3/3] Cargando datos de demostracion con fechas de hoy...
php bin\console db:seed
if errorlevel 1 goto errordb
cd ..
echo.
echo  Listo. Ahora abre instalar_y_ejecutar.bat como siempre.
echo  Cuentas: estudiante@uniminuto.edu.co / Estudiante2026*
echo           andres.rozo@uniminuto.edu.co / Docente2026*
echo           admin@uniminuto.edu.co / Admin2026*
goto fin

:errordb
echo.
echo [X] No pude conectarme a MySQL. Abre el panel de XAMPP, presiona Start en MySQL
echo     y vuelve a ejecutar este archivo.

:fin
echo.
pause
