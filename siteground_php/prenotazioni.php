<?php
require __DIR__ . '/db.php';

$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['privacy_consent'] ?? '') !== '1' || ($_POST['health_notice_consent'] ?? '') !== '1') {
        $errore = 'Per inviare la richiesta e necessario accettare le informative obbligatorie.';
    } else {
        $stmt = db()->prepare('INSERT INTO prenotazioni (nome,email,telefono,indirizzo,messaggio,stato) VALUES (?,?,?,?,?,"Nuova")');
        $stmt->execute([
            trim($_POST['nome'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['telefono'] ?? ''),
            trim($_POST['indirizzo'] ?? ''),
            trim($_POST['messaggio'] ?? ''),
        ]);
        header('Location: /grazie.php');
        exit;
    }
}

page_head('Prenota una visita | Prof. R. Musitano');
?>
<div class="floating-brand">
    <a href="/" class="floating-logo-text"><div class="floating-text"><strong>Sterility &amp; Fertility</strong><small>Fertilita · Prevenzione · Cura</small></div></a>
    <button class="floating-menu-btn" onclick="openMenu()" type="button" aria-label="Apri menu"><span></span><span></span><span></span></button>
</div>
<div class="side-menu" id="sideMenu">
    <button class="close-menu" onclick="closeMenu()" type="button" aria-label="Chiudi menu">×</button>
    <a href="/"><span>⌂</span>Home</a>
    <a href="/prenotazioni.php"><span>◇</span>Prenota una visita</a>
</div>
<section class="booking-luxury-page">
    <div class="booking-copy">
        <span>Prenota la tua visita</span>
        <h1>Richiedi una<br>consulenza<br><em>specialistica</em></h1>
        <p>Compila il modulo per essere ricontattata dal Prof. R. Musitano e dal suo team. Ogni richiesta viene gestita con riservatezza e attenzione professionale.</p>
        <div class="booking-benefits">
            <div><b>✓</b><strong>Riservatezza</strong><small>Accesso limitato al personale autorizzato</small></div>
            <div><b>♡</b><strong>Primo ricontatto</strong><small>Il modulo non sostituisce la visita medica</small></div>
            <div><b>↗</b><strong>Risposta rapida</strong><small>Lo studio ti ricontattera appena possibile</small></div>
        </div>
    </div>
    <form method="POST" class="booking-luxury-form">
        <?php if ($errore): ?><div class="booking-error"><?= e($errore) ?></div><?php endif; ?>
        <div class="booking-fields">
            <label>Nome e cognome *<input type="text" name="nome" placeholder="Inserisci il tuo nome e cognome" required autocomplete="name"></label>
            <label>Email *<input type="email" name="email" placeholder="Inserisci la tua email" required autocomplete="email"></label>
            <label>Cellulare *<input type="tel" name="telefono" placeholder="Inserisci il tuo numero di cellulare" required autocomplete="tel"></label>
            <label>Indirizzo di residenza *<input type="text" name="indirizzo" placeholder="Inserisci il tuo indirizzo di residenza" required autocomplete="street-address"></label>
        </div>
        <label class="full">Motivo della richiesta *<textarea name="messaggio" placeholder="Esempio: richiesta consulenza fertilita, controllo ginecologico, visita ostetrica. Non inserire referti, immagini, diagnosi complete o dati sanitari non necessari." required></textarea></label>
        <div class="booking-privacy-note"><strong>Uso corretto del modulo</strong><p>Questo modulo serve solo per richiedere un ricontatto o un appuntamento. Non sostituisce una visita medica, non gestisce urgenze e non deve essere usato per inviare documentazione clinica dettagliata.</p></div>
        <label class="booking-consent"><input type="checkbox" name="privacy_consent" value="1" required><span>Ho letto l'<a href="/privacy.html" target="_blank" rel="noopener">informativa privacy</a> e acconsento al trattamento dei miei dati personali per essere ricontattata/o e gestire la richiesta.</span></label>
        <label class="booking-consent"><input type="checkbox" name="health_notice_consent" value="1" required><span>Prendo atto che eventuali informazioni sulla salute inserite volontariamente saranno trattate solo per valutare e gestire la richiesta sanitaria, nei limiti necessari.</span></label>
        <button type="submit">Invia richiesta →</button>
        <small class="safe-note">I dati non sono ceduti a terzi per finalita commerciali. Per maggiori dettagli consulta la Privacy Policy.</small>
    </form>
</section>
<?php page_footer(); ?>
