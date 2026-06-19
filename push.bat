@echo off
REM ============================================================
REM  Run this on the DEV machine to push your changes to GitHub.
REM  Usage:  push.bat your commit message here
REM  (everything you type after push.bat becomes the message)
REM ============================================================
cd /d "%~dp0"

set "msg=%*"
if "%msg%"=="" set "msg=Update %date% %time%"

git add -A
git commit -m "%msg%"
git push

echo.
echo ======== PUSHED: %msg% ========
