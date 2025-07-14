<?php
declare(strict_types=1);
require 'config.php';

// Session nur starten, wenn noch keine aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zugriffsschutz für angemeldete Kellner
if (!isset($_SESSION['waiter_id'])) {
    header('Location: login.php');
    exit;
}

// Bestellungen mit Status 'sent' oder 'in_kitchen' laden
$sql = '
    SELECT o.id, o.table_id, o.status, o.created_at, w.display_name
    FROM orders o
    JOIN waiters w ON o.waiter_id = w.id
    WHERE o.status IN (:sent, :inkitchen)
    ORDER BY o.created_at ASC
';
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'sent'     => 'sent',
    'inkitchen'=> 'in_kitchen'
]);
$orders = $stmt->fetchAll();

// Status-Übersetzung
$statusMap = [
    'sent'       => 'In der Küche',
    'in_kitchen' => 'In der Küche'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Bestellungen in der Küche</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f0f0f0; }
        .back-link { display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Bestellungen in der Küche</h2>
    <p>
        Kellner: <?= htmlspecialchars((string)$_SESSION['display_name']) ?> |
        <a href="logout.php">Abmelden</a>
    </p>
    <table>
        <tr>
            <th>ID</th>
            <th>Tisch</th>
            <th>Kellner</th>
            <th>Status</th>
            <th>Zeit</th>
            <th>Aktionen</th>
        </tr>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= (int)$o['id'] ?></td>
                <td><?= (int)$o['table_id'] ?></td>
                <td><?= htmlspecialchars($o['display_name']) ?></td>
                <td><?= htmlspecialchars($statusMap[$o['status']] ?? $o['status']) ?></td>
                <td><?= htmlspecialchars($o['created_at']) ?></td>
                <td>
                    <?php if (in_array($o['status'], ['sent', 'in_kitchen'], true)): ?>
                        <a href="update_status.php?order_id=<?= (int)$o['id'] ?>&new_status=ready">
                            Als fertig markieren
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <a class="back-link" href="menu.php">&larr; Zurück zum Menü</a>
</body>
</html>
