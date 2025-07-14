<?php
declare(strict_types=1);
require 'config.php';

// Session nur starten, wenn noch keine aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zugriffsschutz
if (!isset($_SESSION['waiter_id'])) {
    header('Location: login.php');
    exit;
}

// Bestellung-ID validieren
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Position entfernen?
if (isset($_GET['remove'])) {
    $menuId = filter_input(INPUT_GET, 'remove', FILTER_VALIDATE_INT) ?: 0;
    if ($menuId > 0) {
        // einzelne Position löschen
        $pdo->prepare(
            'DELETE FROM order_items WHERE order_id = :oid AND menu_item_id = :mid'
        )->execute(['oid' => $orderId, 'mid' => $menuId]);
        // Auftrag löschen, wenn leer
        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM order_items WHERE order_id = :oid'
        );
        $count->execute(['oid' => $orderId]);
        if ((int)$count->fetchColumn() === 0) {
            $pdo->prepare('DELETE FROM orders WHERE id = :oid')
                ->execute(['oid' => $orderId]);
            header('Location: menu.php');
            exit;
        }
    }
    header("Location: order_details.php?id={$orderId}");
    exit;
}

// Existenz der Bestellung prüfen
$stmt = $pdo->prepare('SELECT 1 FROM orders WHERE id = :oid');
$stmt->execute(['oid' => $orderId]);
if (!$stmt->fetchColumn()) {
    header('Location: orders.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['cancel'])) {
            // gesamte Bestellung löschen
            $pdo->prepare('DELETE FROM order_items WHERE order_id = :oid')
                ->execute(['oid' => $orderId]);
            $pdo->prepare('DELETE FROM orders WHERE id = :oid')
                ->execute(['oid' => $orderId]);
            header('Location: menu.php');
            exit;
        }
        // Änderungen speichern
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM order_items WHERE order_id = :oid')
            ->execute(['oid' => $orderId]);

        $insert = $pdo->prepare(
            'INSERT INTO order_items 
             (order_id, menu_item_id, quantity, price_at_order)
             VALUES (:oid, :mid, :qty, :price)'
        );

        foreach ($_POST['items'] ?? [] as $mid => $it) {
            $qty = (int)($it['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            // aktuellen Preis abfragen
            $price = (float)$pdo
                ->prepare('SELECT price FROM menu_items WHERE id = :mid')
                ->execute(['mid' => $mid]) // prepare+execute returns bool, need fetch
            && ($val = $pdo->query("SELECT price FROM menu_items WHERE id = {$mid}")->fetchColumn())
                ? (float)$val
                : 0.0;

            if ($price > 0) {
                $insert->execute([
                    'oid'   => $orderId,
                    'mid'   => $mid,
                    'qty'   => $qty,
                    'price' => $price,
                ]);
            }
        }

        $pdo->commit();
        $message = 'Änderungen gespeichert.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Fehler: ' . htmlspecialchars($e->getMessage());
    }
}

// aktuelle Positionen laden
$stmtItems = $pdo->prepare('
    SELECT oi.menu_item_id, oi.quantity, mi.name, mi.price
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE oi.order_id = :oid
    ORDER BY mi.name
');
$stmtItems->execute(['oid' => $orderId]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Bestellung #<?= htmlspecialchars((string)$orderId) ?> bearbeiten</title>
    <style>
        .message { padding:10px; background:#f0f0f0; margin-bottom:15px; border-radius:4px; }
        .remove-link { color: red; text-decoration: none; margin-left:10px; }
        .back-link { display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Bestellung #<?= htmlspecialchars((string)$orderId) ?> bearbeiten</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <ul>
            <?php if (!empty($items)): foreach ($items as $it): ?>
                <?php $mid = (int)$it['menu_item_id']; ?>
                <li>
                    <?= htmlspecialchars($it['name']) ?> — <?= number_format((float)$it['price'],2,',',' ') ?> €
                    <input type="hidden" name="items[<?= $mid ?>][menu_id]" value="<?= $mid ?>">
                    Menge:
                    <input
                        type="number"
                        name="items[<?= $mid ?>][qty]"
                        value="<?= (int)$it['quantity'] ?>"
                        min="0"
                    >
                    <a
                        class="remove-link"
                        href="order_details.php?id=<?= $orderId ?>&remove=<?= $mid ?>"
                        onclick="return confirm('Position wirklich löschen?')"
                    >✖</a>
                </li>
            <?php endforeach; else: ?>
                <li>Keine Positionen.</li>
            <?php endif; ?>
        </ul>
        <button type="submit">Änderungen speichern</button>
        <button type="submit" name="cancel">Bestellung löschen</button>
    </form>

    <a class="back-link" href="menu.php">&larr; Zurück zum Menü</a>
</body>
</html>
