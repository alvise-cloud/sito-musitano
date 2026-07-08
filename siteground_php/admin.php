<?php
require __DIR__ . '/db.php';

$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['username'] ?? '') === ADMIN_USERNAME && ($_POST['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: /admin.php');
        exit;
    }
    $errore = 'Credenziali non corrette';
}

if (empty($_SESSION['admin'])) {
    page_head('Area riservata | Prof. R. Musitano');
    ?>
    <section class="login-wrap elite-login">
      <div class="login-card elite-login-card">
        <div class="login-orbit"><span>✦</span><span>+</span><span>○</span></div>
        <div class="login-icon">M</div>
        <p class="eyebrow">Area riservata</p>
        <h1>Accesso Admin</h1>
        <p class="muted">Inserisci nome utente e password dello studio.</p>
        <?php if ($errore): ?><div class="error"><?= e($errore) ?></div><?php endif; ?>
        <form method="POST" class="stack">
          <label class="pin-label">Nome utente</label>
          <input type="text" name="username" autocomplete="username" placeholder="nome utente" required autofocus>
          <label class="pin-label">Password</label>
          <input type="password" name="password" autocomplete="current-password" placeholder="password" required>
          <button class="full" type="submit">Entra nel pannello</button>
        </form>
      </div>
    </section>
    <?php page_footer(false); exit;
}

$prenotazioni = db()->query('SELECT * FROM prenotazioni ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$clienti = db()->query('SELECT * FROM clienti ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$visite = db()->query('SELECT * FROM visite ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$confermate = count(array_filter($prenotazioni, fn($p) => ($p['stato'] ?? '') === 'Confermata'));

page_head('Dashboard Studio | Prof. R. Musitano');
?>
<main class="admin admin-premium">
<header class="admin-head"><div><p class="eyebrow">Gestionale medico premium</p><h1>Dashboard Studio</h1></div><a class="btn ghost" href="/logout.php">Logout</a></header>
<section class="kpi">
    <div><b><?= count($prenotazioni) ?></b><span>Prenotazioni</span></div>
    <div><b><?= count($clienti) ?></b><span>Pazienti</span></div>
    <div><b><?= count($visite) ?></b><span>Visite registrate</span></div>
    <div><b><?= $confermate ?></b><span>Confermate</span></div>
</section>
<section class="admin-grid">
<div class="panel">
    <div class="panel-title"><h2>Prenotazioni</h2><a class="quick" href="/admin_action.php?action=export">Esporta CSV</a></div>
    <?php foreach ($prenotazioni as $p): ?>
    <div class="booking-item" onclick="toggleBooking('<?= (int) $p['id'] ?>')">
        <div><b><?= e($p['nome']) ?></b><span><?= e($p['email']) ?> · <?= e($p['telefono']) ?></span></div>
        <div><em class="status <?= $p['stato'] === 'Confermata' ? 'ok' : ($p['stato'] === 'Completata' ? 'done' : ($p['stato'] === 'Annullata' ? 'cancel' : 'new')) ?>"><?= e($p['stato']) ?></em></div>
    </div>
    <div id="booking-<?= (int) $p['id'] ?>" class="booking-detail-card" style="display:none;">
        <h3><?= e($p['nome']) ?></h3>
        <p><b>Email:</b> <?= e($p['email']) ?></p>
        <p><b>Telefono:</b> <?= e($p['telefono']) ?></p>
        <p><b>Indirizzo:</b> <?= e($p['indirizzo']) ?></p>
        <p><b>Sede:</b> <?= e($p['sede'] ?: 'Non confermata') ?></p>
        <p><b>Data:</b> <?= e($p['data_visita'] ?: 'Da confermare') ?></p>
        <p><b>Messaggio:</b> <?= nl2br(e($p['messaggio'])) ?></p>
        <?php if (!in_array($p['stato'], ['Confermata','Completata'], true)): ?>
        <form method="POST" action="/admin_action.php" class="confirm-box">
            <input type="hidden" name="action" value="confirm"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <select name="sede" required><option value="">Sede</option><option>Caorle</option><option>Bova Marina</option><option>San Dona di Piave</option><option>Lugano</option><option>Venezia</option></select>
            <input type="date" name="data_visita" required><input type="time" name="ora_inizio" required><input type="time" name="ora_fine" required>
            <button class="action confirm" type="submit">✓ Conferma</button>
        </form>
        <?php endif; ?>
        <?php $msg = 'La tua prenotazione presso ' . ($p['sede'] ?: 'lo studio') . ' il ' . ($p['data_visita'] ?: '') . ' e stata confermata con successo.'; ?>
        <div class="admin-actions">
            <a class="action whatsapp" href="https://wa.me/<?= e(digits_for_whatsapp($p['telefono'])) ?>?text=<?= urlencode($msg) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a class="action phone" href="tel:<?= e($p['telefono']) ?>">Chiama</a>
            <a class="action email" href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($p['email']) ?>&su=<?= urlencode('Prenotazione confermata') ?>&body=<?= urlencode($msg) ?>" target="_blank" rel="noopener">Gmail</a>
            <a class="action complete" href="/admin_action.php?action=complete&id=<?= (int) $p['id'] ?>">Completata</a>
            <a class="action cancel" href="/admin_action.php?action=cancel&id=<?= (int) $p['id'] ?>">Annulla</a>
            <a class="action delete" href="/admin_action.php?action=delete&id=<?= (int) $p['id'] ?>" onclick="return confirm('Eliminare la prenotazione?')">Elimina</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="panel" id="pazienti">
    <div class="panel-title"><h2>Nuovo paziente</h2></div>
    <form class="stack" method="POST" action="/admin_action.php">
        <input type="hidden" name="action" value="new_client">
        <input name="nome" placeholder="Nome e cognome" required><input name="telefono" placeholder="Telefono"><input name="email" placeholder="Email"><textarea name="note" placeholder="Note cliniche / amministrative"></textarea><button>Salva paziente</button>
    </form>
    <div class="panel-title" style="margin-top:30px;"><h2>Pazienti salvati</h2></div>
    <?php foreach ($clienti as $c): ?><div class="mini"><div><b><?= e($c['nome']) ?></b><br><span><?= e($c['telefono']) ?> · <?= e($c['email']) ?></span><br><small><?= e($c['note']) ?></small></div></div><?php endforeach; ?>
</div>
</section>
<section class="admin-grid">
<div class="panel" id="visite"><div class="panel-title"><h2>Registra visita</h2></div><form class="stack" method="POST" action="/admin_action.php"><input type="hidden" name="action" value="new_visit"><input name="paziente" placeholder="Paziente"><input type="datetime-local" name="data_visita"><input name="tipo" placeholder="Tipo visita"><textarea name="note" placeholder="Esito, terapia, follow-up"></textarea><button>Registra visita</button></form></div>
<div class="panel"><div class="panel-title"><h2>Storico visite</h2></div><?php foreach ($visite as $v): ?><div class="mini"><b><?= e($v['paziente']) ?></b><span><?= e($v['data_visita']) ?> · <?= e($v['tipo']) ?></span></div><?php endforeach; ?></div>
</section>
</main>
<?php page_footer(false); ?>
