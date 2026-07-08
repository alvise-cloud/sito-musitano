<?php
declare(strict_types=1);

session_start();

const ADMIN_USERNAME = 'musitanorocco3310';
const ADMIN_PASSWORD = 'Rocco3310';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = dirname(__DIR__) . '/musitano_data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0750, true);
    }

    $pdo = new PDO('sqlite:' . $dataDir . '/musitano.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS prenotazioni (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT,
        email TEXT,
        telefono TEXT,
        indirizzo TEXT,
        messaggio TEXT,
        sede TEXT,
        data_visita TEXT,
        stato TEXT DEFAULT "Nuova",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS clienti (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT,
        email TEXT,
        telefono TEXT,
        note TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS visite (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        paziente TEXT,
        data_visita TEXT,
        tipo TEXT,
        note TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    return $pdo;
}

function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_required(): void {
    if (empty($_SESSION['admin'])) {
        header('Location: /admin.php');
        exit;
    }
}

function digits_for_whatsapp(?string $value): string {
    $digits = preg_replace('/\D+/', '', (string) $value);
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '39')) {
        return $digits;
    }
    if (str_starts_with($digits, '0')) {
        return '39' . substr($digits, 1);
    }
    if (str_starts_with($digits, '3')) {
        return '39' . $digits;
    }
    return $digits;
}

function page_head(string $title = 'Prof. R. Musitano | Sterility & Fertility'): void {
    echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="stylesheet" href="/static/css/style.css?v=20260702-wa-footer-compact-final">';
    echo '<script defer src="/static/js/script.js"></script></head><body>';
}

function page_footer(bool $showFooter = true): void {
    echo '<a class="whatsapp-float" href="https://wa.me/393737731584?text=Ciao%2C%20vorrei%20informazioni%20da%20Musitano%20Sterility%20%26%20Fertility." target="_blank" rel="noopener" aria-label="Apri WhatsApp" style="position:fixed;right:28px;bottom:28px;z-index:2147483647;width:74px;height:74px;display:grid;place-items:center;border-radius:50%;text-decoration:none;"><span class="whatsapp-icon-wrap" style="position:relative;width:74px;height:74px;display:grid;place-items:center;border-radius:50%;background:#25D366;box-shadow:0 18px 46px rgba(37,211,102,.38),0 7px 18px rgba(0,0,0,.18);"><span class="whatsapp-alert-dot" style="position:absolute;top:6px;right:6px;width:15px;height:15px;border:3px solid #fff;border-radius:50%;background:#e43355;"></span><svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path fill="#25D366" d="M16 3.1A12.7 12.7 0 0 0 5.1 22.3L3.6 28.9l6.7-1.6A12.7 12.7 0 1 0 16 3.1Z"/><path fill="white" d="M23.3 18.7c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.4.1-.6.1-.1.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.7-1.7-1-2.3-.2-.5-.5-.4-.7-.4h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-.9 2.4.1 1.4 1 2.8 1.2 3 .2.2 2 3.1 5 4.2 3 1.1 3 .7 3.6.7.6 0 1.8-.7 2.1-1.3.3-.6.3-1.1.2-1.3-.1-.1-.3-.2-.6-.4z"/></svg></span></a>';
    if ($showFooter) {
        echo '<footer class="legal-footer"><div class="footer-brand"><strong>Sterility &amp; Fertility</strong><span>Prof. R. Musitano · Ginecologia · Ostetricia · Prevenzione</span><small>© 2026 Prof. Musitano</small></div><div class="footer-columns"><section><h3>Contatti</h3><a href="tel:+393737731584">+39 373 773 1584</a><a href="https://wa.me/393737731584" target="_blank" rel="noopener">WhatsApp studio</a><a href="mailto:info@profmusitanofertility.it">info@profmusitanofertility.it</a></section><section><h3>Informazioni</h3><a href="/termini.html">Info e condizioni</a><a href="/privacy.html">Privacy Policy</a><a href="/cookie-policy.html">Cookie Policy</a><a href="/prenotazioni.php">Prenota una visita</a><a href="/admin.php">Area riservata</a></section><section class="footer-locations-section"><h3>Sedi</h3><p class="footer-locations">BOLOGNA · BOVA MARINA · CAORLE · LIVORNO<br>MILANO · PADOVA · PALERMO · REGGIO CALABRIA<br>ROMA · SAN DONA\' DI PIAVE · VENEZIA<br>LUGANO (SVIZZERA) · KIEV (UKRAINA)</p></section></div></footer>';
    }
    echo '<script>function openMenu(){document.getElementById("sideMenu")?.classList.add("active")}function closeMenu(){document.getElementById("sideMenu")?.classList.remove("active")}function toggleBooking(id){const row=document.getElementById("booking-"+id);if(row){row.style.display=row.style.display==="none"?"block":"none"}}</script></body></html>';
}
