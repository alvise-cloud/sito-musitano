from flask import Flask, render_template, request, redirect, url_for, session, jsonify, Response
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
import csv, io, os
from urllib.parse import quote
from werkzeug.middleware.proxy_fix import ProxyFix

app = Flask(__name__)

def load_local_env():
    for filename in ('.env', '.env.local'):
        if not os.path.exists(filename):
            continue
        with open(filename, encoding='utf-8') as env_file:
            for raw_line in env_file:
                line = raw_line.strip()
                if not line or line.startswith('#') or '=' not in line:
                    continue
                key, value = line.split('=', 1)
                os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))

load_local_env()

database_url = os.environ.get('DATABASE_URL', 'sqlite:///musitano.db')
if database_url.startswith('postgres://'):
    database_url = database_url.replace('postgres://', 'postgresql://', 1)

app.secret_key = os.environ.get('SECRET_KEY', 'dev-only-change-before-publication')
app.config['SQLALCHEMY_DATABASE_URI'] = database_url
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SESSION_COOKIE_HTTPONLY'] = True
app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
app.config['SESSION_COOKIE_SECURE'] = os.environ.get('SESSION_COOKIE_SECURE', '').lower() in ('1', 'true', 'yes')
app.config['PREFERRED_URL_SCHEME'] = 'https' if app.config['SESSION_COOKIE_SECURE'] else 'http'
app.wsgi_app = ProxyFix(app.wsgi_app, x_for=1, x_proto=1, x_host=1, x_port=1)

ADMIN_USERNAME = os.environ.get('ADMIN_USERNAME')
ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD')
PRIMARY_DOMAIN = os.environ.get('PRIMARY_DOMAIN', 'profmusitanofertility.it')
db = SQLAlchemy(app)

@app.before_request
def redirect_render_host_to_primary_domain():
    host = request.host.split(':', 1)[0].lower()
    if host.endswith('.onrender.com'):
        query = request.query_string.decode('utf-8')
        target = f"https://{PRIMARY_DOMAIN}{request.path}"
        if query:
            target = f"{target}?{query}"
        return redirect(target, code=301)

@app.after_request
def add_security_headers(response):
    response.headers.setdefault('X-Content-Type-Options', 'nosniff')
    response.headers.setdefault('X-Frame-Options', 'SAMEORIGIN')
    response.headers.setdefault('Referrer-Policy', 'strict-origin-when-cross-origin')
    response.headers.setdefault('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
    if request.is_secure or app.config['SESSION_COOKIE_SECURE']:
        response.headers.setdefault('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
    return response

class Prenotazione(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100))
    email = db.Column(db.String(150))
    telefono = db.Column(db.String(50))
    sede = db.Column(db.String(80))
    data = db.Column(db.String(50))
    messaggio = db.Column(db.Text)
    stato = db.Column(db.String(30), default='Nuova')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Slot(db.Model):
    sede = db.Column(db.String(80), default='San Donà di Piave')
    id = db.Column(db.Integer, primary_key=True)
    data = db.Column(db.String(50))
    ora = db.Column(db.String(20))
    tipo = db.Column(db.String(80), default='Visita')
    disponibile = db.Column(db.Boolean, default=True)

class Cliente(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(120))
    email = db.Column(db.String(150))
    telefono = db.Column(db.String(50))
    note = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Visita(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    cliente_id = db.Column(db.Integer, db.ForeignKey('cliente.id'), nullable=True)
    paziente = db.Column(db.String(120))
    data = db.Column(db.String(50))
    tipo = db.Column(db.String(100))
    note = db.Column(db.Text)
    cliente = db.relationship('Cliente', backref='visite')


SERVIZI = {
    'ginecologia': {
        'titolo': 'Prevenzione ginecologica',
        'sottotitolo': 'Controlli periodici, diagnosi precoce personalizzata.',
        'testo': 'La ginecologia rappresenta il primo riferimento clinico per la tutela della salute femminile attraverso la prevenzione, la diagnosi precoce ed i percorsi specialistici dedicati al benessere della donna in ogni fase della sua vita.',
        'punti': ['Visita ginecologica', 'Thin Prep  Pap test', 'Ecografia Transvaginale ad Alta Risoluzione', 'Inserzione IUD']
    },
    'ostetricia': {
        'titolo': 'Ostetricia e maternità',
        'sottotitolo': 'Controlli, ascolto e accompagnamento durante la gravidanza.',
        'testo': 'L’ostetricia accompagna la donna nel percorso della gravidanza con controlli clinici, ecografie, valutazioni del benessere materno-fetale e un supporto costante nelle diverse fasi della gestazione.',
        'punti': ['Controlli in gravidanza', 'Ecografie ostetriche', 'Amniocentesi e villocentesi', 'Counselling prenatale']
    },
   'fertilita': {
    'titolo': 'Sterilità ed Infertilità di Coppia',
    'sottotitolo': 'Valutazione specialistica della sterilità e dell’infertilità di coppia.',
    'testo': "Il percorso diagnostico personalizzato del Prof. Musitano prevede numerose analisi e l'intervento di diversi specialisti. ONE STOP CLINIC: Velocità e accuratezza della diagnosi in un unica giornata e presso un unica sede vengono effettuate tutte le analisi necessarie.",
    'punti': ['Infertilità di coppia', 'Fecondazione in vitro microassistita', 'Ovodonazione', 'Diagnosi Genetica Preinpianto(PGD)']
},
    'thin-prep': {
        'titolo': 'Thin Prep Pap Test',
        'sottotitolo': 'Pap test in fase liquida per una lettura più chiara del campione.',
        'testo': 'Il Thin Prep Pap Test conserva il campione in fase liquida e consente una preparazione più pulita del vetrino, riducendo gli elementi oscuranti e migliorando la qualità della valutazione citologica.Aumenta in modo considerevole il grado di predittività e diagnosi precoce delle precancerosi del collo uterino.',
        'punti': ['Campione in fase liquida', 'Conservazione immediata', '100% delle cellule vengono esaminate', 'Alta attendibilità e predittività']
    },
    'ecografia': {
        'titolo': 'Ecotomografia transvaginale',
        'sottotitolo': 'Diagnostica ecografica per utero, ovaie e strutture pelviche.',
        'testo': 'L’ecotomografia transvaginale è un esame strumentale che permette una valutazione dettagliata degli organi pelvici femminili e supporta diagnosi e monitoraggi follicolari specialistici.',
        'punti': ['Screening di utero e ovaie', 'Controllo pelvico', 'Supporto diagnostico', 'Monitoraggio follicolare']
    },
    'prevenzione': {
        'titolo': 'Screening combinato',
        'sottotitolo': 'Più controlli in un’unica seduta.',
        'testo': 'Lo screening combinato integra visita ginecologica, visita senologica, ecografia transvaginale ad alta risoluzione, Thin Prep Pap Test e markers ovarici.',
        'punti': ['Visita ginecologica', 'Visita senologica', 'Ecografia transvaginale', 'Markers ovarici']
    }
}

@app.context_processor
def inject_globals():
    return {'telegram_link': os.environ.get('TELEGRAM_BOT_LINK', 'https://t.me/INSERISCI_USERNAME_BOT')}

@app.template_filter('whatsapp_phone')
def whatsapp_phone(value):
    digits = ''.join(ch for ch in (value or '') if ch.isdigit())
    if digits.startswith('00'):
        digits = digits[2:]
    if digits.startswith('39'):
        return digits
    if digits.startswith('0'):
        return '39' + digits[1:]
    if digits.startswith('3') and len(digits) >= 9:
        return '39' + digits
    return digits

@app.template_filter('gmail_compose')
def gmail_compose(email, subject='Prenotazione confermata', body=''):
    return (
        'https://mail.google.com/mail/?view=cm&fs=1'
        f'&to={quote(email or "")}'
        f'&su={quote(subject or "")}'
        f'&body={quote(body or "")}'
    )

def seed():
    if not Slot.query.first():
        for d,o,t in [('2026-05-13','09:00','Screening combinato'),('2026-05-13','10:30','Ecografia transvaginale'),('2026-05-14','15:00','Colposcopia'),('2026-05-15','11:00','Prima visita')]:
            db.session.add(Slot(data=d, ora=o, tipo=t))
        db.session.commit()

with app.app_context():
    db.create_all()
    seed()

def admin_required():
    return session.get('admin') is True

@app.route('/')
def home():
    return render_template('home.html')


@app.route('/servizi/<slug>')
def servizio(slug):
    item = SERVIZI.get(slug)
    if not item:
        return redirect(url_for('home'))
    return render_template('servizio.html', servizio=item, slug=slug)
@app.route('/prenotazioni', methods=['GET','POST'])
def prenotazioni():
    if request.method == 'POST':
        p = Prenotazione(
            nome=request.form.get('nome'),
            email=request.form.get('email'),
            telefono=request.form.get('telefono'),
            data=request.form.get('data'),
            messaggio=request.form.get('messaggio')
        )

        db.session.add(p)

        cliente = Cliente.query.filter(
            (Cliente.email == p.email) | (Cliente.telefono == p.telefono)
        ).first()

        if cliente:
            cliente.nome = p.nome or cliente.nome
            cliente.email = p.email or cliente.email
            cliente.telefono = p.telefono or cliente.telefono
            cliente.note = p.messaggio or cliente.note
        else:
            cliente = Cliente(
                nome=p.nome,
                email=p.email,
                telefono=p.telefono,
                note=p.messaggio
            )
            db.session.add(cliente)

        db.session.commit()

        return render_template('grazie.html', prenotazione=p)

    slots = Slot.query.filter_by(disponibile=True).order_by(Slot.data, Slot.ora).all()
    return render_template('prenotazioni.html', slots=slots)
@app.route('/admin', methods=['GET','POST'])
def admin():
    if request.method == 'POST':
        if not ADMIN_USERNAME or not ADMIN_PASSWORD:
            return render_template('login_admin.html', errore='Credenziali admin non configurate sul server')

        username = request.form.get('username')
        password = request.form.get('password')

        if username == ADMIN_USERNAME and password == ADMIN_PASSWORD:
            session['admin'] = True
            return redirect(url_for('dashboard'))

        return render_template('login_admin.html', errore='Credenziali non corrette')

    return render_template('login_admin.html')


@app.route('/dashboard')
def dashboard():
    if not admin_required():
        return redirect(url_for('admin'))

    pren = Prenotazione.query.order_by(Prenotazione.id.desc()).all()
    slots = Slot.query.order_by(Slot.data, Slot.ora).all()
    clienti = Cliente.query.order_by(Cliente.id.desc()).all()
    visite = Visita.query.order_by(Visita.id.desc()).all()

    return render_template(
        'admin_dashboard.html',
        prenotazioni=pren,
        slots=slots,
        clienti=clienti,
        visite=visite
    )
@app.route('/logout_admin')
def logout_admin():
    session.clear(); return redirect(url_for('admin'))

@app.route('/aggiungi_slot', methods=['POST'])
def aggiungi_slot():
    if not admin_required(): 
        return redirect(url_for('admin'))

    db.session.add(Slot(
        sede=request.form.get('sede'),
        data=request.form['data'],
        ora=request.form['ora'],
        tipo=request.form.get('tipo','Visita')
    ))

    db.session.commit()
    return redirect(url_for('dashboard')+'#agenda')
@app.route('/elimina_slot/<int:id>')
def elimina_slot(id):
    if not admin_required(): return redirect(url_for('admin'))
    s=Slot.query.get_or_404(id); db.session.delete(s); db.session.commit(); return redirect(url_for('dashboard')+'#agenda')

@app.route('/elimina/<int:id>')
def elimina(id):
    if not admin_required(): return redirect(url_for('admin'))
    p=Prenotazione.query.get_or_404(id); db.session.delete(p); db.session.commit(); return redirect(url_for('dashboard'))

@app.route('/cliente/nuovo', methods=['POST'])
def nuovo_cliente():
    if not admin_required(): return redirect(url_for('admin'))
    c=Cliente(nome=request.form['nome'], email=request.form.get('email'), telefono=request.form.get('telefono'), note=request.form.get('note'))
    db.session.add(c); db.session.commit(); return redirect(url_for('dashboard')+'#pazienti')

@app.route('/visita/nuova', methods=['POST'])
def nuova_visita():
    if not admin_required(): return redirect(url_for('admin'))
    v=Visita(paziente=request.form.get('paziente'), data=request.form.get('data'), tipo=request.form.get('tipo'), note=request.form.get('note'))
    db.session.add(v); db.session.commit(); return redirect(url_for('dashboard')+'#visite')

@app.route('/api/prenota', methods=['POST'])
def api_prenota():
    data = request.get_json(force=True)
    nuova = Prenotazione(nome=data.get('nome'), email=data.get('email'), telefono=data.get('telefono',''), data=data.get('data'), messaggio=data.get('messaggio'))
    db.session.add(nuova); db.session.commit()
    return jsonify({'id': nuova.id, 'status':'ok'})

@app.route('/export/prenotazioni.csv')
def export_csv():
    if not admin_required(): return redirect(url_for('admin'))
    out=io.StringIO(); w=csv.writer(out); w.writerow(['nome','email','telefono','data','messaggio','stato'])
    for p in Prenotazione.query.all(): w.writerow([p.nome,p.email,p.telefono,p.data,p.messaggio,p.stato])
    return Response(out.getvalue(), mimetype='text/csv', headers={'Content-Disposition':'attachment; filename=prenotazioni.csv'})
@app.route('/paziente/<int:id>')
def scheda_paziente(id):

    if not admin_required():
        return redirect(url_for('admin'))

    cliente = Cliente.query.get_or_404(id)
    visite = Visita.query.filter_by(
        cliente_id=id
    ).order_by(
        Visita.id.desc()
    ).all()

    return render_template(
        'scheda_paziente.html',
        cliente=cliente,
        visite=visite
    )
@app.route('/conferma_prenotazione/<int:id>', methods=['POST'])
def conferma_prenotazione(id):
    if not admin_required():
        return redirect(url_for('admin'))

    p = Prenotazione.query.get_or_404(id)

    sede = request.form.get('sede')
    data_visita = request.form.get('data_visita')
    ora_inizio = request.form.get('ora_inizio')
    ora_fine = request.form.get('ora_fine')

    p.sede = sede
    p.data = f"{data_visita} dalle {ora_inizio} alle {ora_fine}"
    p.stato = 'Confermata'

    db.session.commit()

    return redirect(url_for('dashboard'))


@app.route('/completa_prenotazione/<int:id>')
def completa_prenotazione(id):
    if not admin_required():
        return redirect(url_for('admin'))

    p = Prenotazione.query.get_or_404(id)
    p.stato = 'Completata'
    db.session.commit()

    return redirect(url_for('dashboard'))
@app.route('/annulla_prenotazione/<int:id>')
def annulla_prenotazione(id):
    if not admin_required():
        return redirect(url_for('admin'))

    p = Prenotazione.query.get_or_404(id)
    p.stato = 'Annullata'
    db.session.commit()

    return redirect(url_for('dashboard'))

@app.route('/privacy')
def privacy():
    return render_template('privacy.html')

@app.route('/cookie-policy')
def cookie_policy():
    return render_template('cookie_policy.html')

@app.route('/termini')
def termini():
    return render_template('termini.html')

if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=os.environ.get('FLASK_DEBUG', '').lower() in ('1', 'true', 'yes')
    )
