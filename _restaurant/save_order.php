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

// Eingaben validieren
$tableId  = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT) ?: 0;
$waiterId = (int)$_SESSION['waiter_id'];
$items    = $_POST['items'] ?? [];

// Mindestens ein Gericht mit Menge > 0
$filtered = [];
foreach ($items as $mid => $it) {
    $qty = (int)($it['qty'] ?? 0);
    if ((!empty($it['menu_id'])) && $qty > 0) {
        $filtered[(int)$mid] = $qty;
    }
}

if ($tableId <= 0 || empty($filtered)) {
    $_SESSION['flash'] = 'Bitte wählen Sie mindestens ein Gericht aus.';
    header('Location: menu.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Neuer Auftrag mit Status 'sent'
    $stmt = $pdo->prepare('
        INSERT INTO orders (table_id, waiter_id, status)
        VALUES (:tid, :wid, "sent")
    ');
    $stmt->execute([
        'tid' => $tableId,
        'wid' => $waiterId,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Positionen einfügen
    $stmtItem = $pdo->prepare('
        INSERT INTO order_items
            (order_id, menu_item_id, quantity, price_at_order)
        VALUES (:oid, :mid, :qty, :price)
    ');
    $priceStmt = $pdo->prepare('SELECT price FROM menu_items WHERE id = :mid');

    foreach ($filtered as $mid => $qty) {
        // Preis abfragen
        $priceStmt->execute(['mid' => $mid]);
        $unitPrice = (float)$priceStmt->fetchColumn();

        $stmtItem->execute([
            'oid'   => $orderId,
            'mid'   => $mid,
            'qty'   => $qty,
            'price' => $unitPrice,
        ]);
    }

    $pdo->commit();
    $_SESSION['flash'] = "Bestellung #{$orderId} erfolgreich an die Küche gesendet.";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = 'Fehler beim Anlegen der Bestellung.';
}

header('Location: menu.php');
exit;
?>
