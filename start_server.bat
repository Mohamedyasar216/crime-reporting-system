@echo off
title Crime Reporting System Server
echo ===================================================
echo   CRIME REPORTING SYSTEM - DEVELOPMENT SERVER
echo ===================================================
echo.
echo   Starting PHP Server with Router...
echo   URL: http://localhost:8000
echo.
echo   [!] Please close any other running PHP servers 
echo       on port 8000 before running this.
echo.
echo ===================================================

php -S localhost:8000 router.php
pause
