@echo off
REM ============================================================
REM  PGC-DTS one-click deploy (run this on the SERVER).
REM  It updates code, rebuilds, migrates, clears caches, recycles IIS.
REM  cd /d "%~dp0" makes it run from wherever this file lives.
REM ============================================================
cd /d "%~dp0"

echo === Pulling latest code ===
git pull || goto :error

echo === Installing PHP packages ===
call composer install --no-dev --optimize-autoloader || goto :error

echo === Building front-end assets ===
call npm install || goto :error
call npm run build || goto :error

echo === Running database migrations ===
php artisan migrate --force || goto :error

echo === Rebuilding caches ===
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo === Restarting IIS ===
iisreset

echo.
echo ======== DEPLOY COMPLETE ========
goto :eof

:error
echo.
echo !!! DEPLOY FAILED on the step above - fix it and re-run. !!!
exit /b 1
