<?php
declare(strict_types=1);

require 'config.php';
// Starte nur, wenn noch keine Session aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['waiter_id'])) {
    header('Location: login.php');
    exit;
}

// Bestellung-ID validieren
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Artikel zum Auftrag laden
$stmt = $pdo->prepare("
    SELECT mi.name, oi.quantity, oi.price_at_order
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE oi.order_id = :orderId
    ORDER BY mi.name
");
$stmt->execute(['orderId' => $orderId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Rechnung #<?= htmlspecialchars((string)$orderId) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Rechnung für Bestellung #<?= htmlspecialchars((string)$orderId) ?></h2>

    <?php if (empty($rows)): ?>
        <p>Keine Positionen vorhanden.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($rows as $item):
            $lineTotal = (float)$item['price_at_order'] * (int)$item['quantity'];
            $total += $lineTotal;
        ?>
            <li>
                <?= htmlspecialchars($item['name']) ?> –
                <?= (int)$item['quantity'] ?> ×
                <?= number_format((float)$item['price_at_order'], 2, ',', ' ') ?> €
                = <?= number_format($lineTotal, 2, ',', ' ') ?> €
            </li>
        <?php endforeach; ?>
        </ul>
        <p><strong>Gesamt: <?= number_format($total, 2, ',', ' ') ?> €</strong></p>
        <button onclick="window.print()">Drucken</button>
    <?php endif; ?>

    <p><a href="menu.php">&larr; Zurück zum Menü</a></p>
</body>
</html>
