@echo off
setlocal
cd /d "%~dp0"

echo Avvio sito Prof. Musitano...

set ADMIN_USERNAME=musitanorocco3310
set ADMIN_PASSWORD=Rocco3310
set SECRET_KEY=musitano-local-dev-change-in-production
set SESSION_COOKIE_SECURE=0
set FLASK_DEBUG=0

if not exist ".venv\Scripts\python.exe" (
  python -m venv .venv
)

".venv\Scripts\python.exe" -m pip install flask flask_sqlalchemy requests python-telegram-bot

echo.
echo Apri: http://127.0.0.1:5000
echo Area admin: http://127.0.0.1:5000/admin
echo Username admin locale: musitanorocco3310
echo Password admin locale: Rocco3310
echo.

".venv\Scripts\python.exe" app.py
pause
