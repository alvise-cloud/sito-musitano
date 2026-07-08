<?php
require __DIR__ . '/db.php';
admin_required();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

if ($action === 'confirm' && $id > 0) {
    $data = ($_POST['data_visita'] ?? '') . ' dalle ' . ($_POST['ora_inizio'] ?? '') . ' alle ' . ($_POST['ora_fine'] ?? '');
    $stmt = db()->prepare('UPDATE prenotazioni SET sede=?, data_visita=?, stato="Confermata" WHERE id=?');
    $stmt->execute([$_POST['sede'] ?? '', $data, $id]);
} elseif ($action === 'complete' && $id > 0) {
    db()->prepare('UPDATE prenotazioni SET stato="Completata" WHERE id=?')->execute([$id]);
} elseif ($action === 'cancel' && $id > 0) {
    db()->prepare('UPDATE prenotazioni SET stato="Annullata" WHERE id=?')->execute([$id]);
} elseif ($action === 'delete' && $id > 0) {
    db()->prepare('DELETE FROM prenotazioni WHERE id=?')->execute([$id]);
} elseif ($action === 'new_client') {
    db()->prepare('INSERT INTO clienti (nome,email,telefono,note) VALUES (?,?,?,?)')->execute([$_POST['nome'] ?? '', $_POST['email'] ?? '', $_POST['telefono'] ?? '', $_POST['note'] ?? '']);
} elseif ($action === 'new_visit') {
    db()->prepare('INSERT INTO visite (paziente,data_visita,tipo,note) VALUES (?,?,?,?)')->execute([$_POST['paziente'] ?? '', $_POST['data_visita'] ?? '', $_POST['tipo'] ?? '', $_POST['note'] ?? '']);
} elseif ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=prenotazioni.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['nome','email','telefono','indirizzo','data','sede','messaggio','stato']);
    foreach (db()->query('SELECT * FROM prenotazioni ORDER BY id DESC') as $p) {
        fputcsv($out, [$p['nome'], $p['email'], $p['telefono'], $p['indirizzo'], $p['data_visita'], $p['sede'], $p['messaggio'], $p['stato']]);
    }
    exit;
}

header('Location: /admin.php');
exit;
