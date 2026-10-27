@echo off
title Kuliner Management System
echo ========================================================
echo   Menjalankan Sistem Manajemen Restoran Kuliner
echo   Akses: http://localhost:8000
echo ========================================================
echo.

set PHP_BIN=php
if exist "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
    set PHP_BIN=C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe
)

start "" http://localhost:8000
"%PHP_BIN%" -S localhost:8000 -t "%~dp0"
pause
