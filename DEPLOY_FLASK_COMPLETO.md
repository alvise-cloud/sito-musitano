# Deploy Flask completo

Obiettivo: pubblicare sul dominio `profmusitanofertility.it` la versione Flask completa, quindi con admin e prenotazioni salvate.

## Decisione tecnica

GitHub Pages non va bene come soluzione finale, perche pubblica solo file statici e non puo eseguire `app.py`, database, login admin o dashboard.

La soluzione finale deve essere un hosting Python/Flask con WSGI o container Docker.

## File gia pronti

- `app.py`: applicazione Flask
- `requirements.txt`: dipendenze Python
- `Procfile`: avvio per piattaforme Python (`gunicorn app:app`)
- `wsgi.py`: entrypoint WSGI generico
- `passenger_wsgi.py`: entrypoint per hosting con Passenger
- `Dockerfile`: deploy container

## Variabili da configurare sull'hosting

```text
ADMIN_USERNAME=musitanorocco3310
ADMIN_PASSWORD=...
SECRET_KEY=...
SESSION_COOKIE_SECURE=1
FLASK_DEBUG=0
```

Consigliato su produzione:

```text
DATABASE_URL=...
```

Se non viene configurato `DATABASE_URL`, l'app usa SQLite locale. Per un hosting stabile e backup seri e meglio un database persistente gestito dall'hosting.

## Comandi standard

Build:

```bash
pip install -r requirements.txt
```

Start:

```bash
gunicorn app:app
```

Oppure con Docker:

```bash
docker build -t sito-musitano .
docker run -p 8000:8000 --env-file .env sito-musitano
```

## DNS finale

Quando l'hosting Flask e pronto, il dominio non deve piu puntare a GitHub Pages.

I record GitHub Pages da sostituire saranno:

```text
A profmusitanofertility.it -> 185.199.108.153
A profmusitanofertility.it -> 185.199.109.153
A profmusitanofertility.it -> 185.199.110.153
A profmusitanofertility.it -> 185.199.111.153
CNAME www -> alvise-cloud.github.io
```

Al loro posto andranno messi i record indicati dal nuovo hosting Flask.

## HTTPS

Il certificato HTTPS deve essere attivato sull'hosting Flask, non su GitHub Pages.

Quando HTTPS e attivo, il sito deve aprirsi senza avviso "Non sicuro":

```text
https://profmusitanofertility.it
https://www.profmusitanofertility.it
```

## Test finale

1. Aprire la home.
2. Aprire `/prenotazioni`.
3. Inviare una prenotazione di prova.
4. Aprire `/admin`.
5. Fare login.
6. Verificare che la prenotazione compaia in dashboard.
7. Cancellare la prova.
