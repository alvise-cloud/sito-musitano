# Deploy Render

Questa repository contiene la versione da pubblicare:

`outputs/musitano_footer_compatto_whatsapp_fixed`

## Render

Build command:

```text
pip install -r requirements.txt
```

Start command:

```text
gunicorn app:app
```

Variabili ambiente:

```text
ADMIN_USERNAME=musitanorocco3310
ADMIN_PASSWORD=Rocco3310
SECRET_KEY=generare-una-chiave-lunga
SESSION_COOKIE_SECURE=1
FLASK_DEBUG=0
```

Se si aggiunge un database PostgreSQL:

```text
DATABASE_URL=...
```
