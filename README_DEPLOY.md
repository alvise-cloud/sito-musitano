# Deploy

Sito Flask per Prof. Musitano.

Variabili da configurare sull'hosting:

- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`
- `SECRET_KEY`
- `SESSION_COOKIE_SECURE=1`
- `TELEGRAM_BOT_TOKEN` solo se si usa il bot Telegram
- `SERVER_URL` solo se si usa il bot Telegram

Comandi hosting:

- Build: `pip install -r requirements.txt`
- Start: `gunicorn app:app`

Deploy consigliato:

- Applicazione Flask su Render come Web Service Python.
- Dominio e DNS gestiti da SiteGround.
- Dopo il deploy, configurare su Render il dominio personalizzato e copiare i record DNS dentro SiteGround.

Non caricare `.env` su GitHub: contiene credenziali locali.
