<?php
declare(strict_types=1);
require 'config.php';

// Session starten, falls nötig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zugriffsschutz
if (!isset($_SESSION['waiter_id'])) {
    header('Location: login.php');
    exit;
}

// Parameter validieren
$orderId   = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
$newStatus = filter_input(INPUT_GET, 'new_status', FILTER_SANITIZE_STRING) ?: '';

// Statusgrößen
$validStatuses = ['sent', 'in_kitchen', 'ready', 'cancelled'];

if ($orderId > 0 && in_array($newStatus, $validStatuses, true)) {
    if ($newStatus === 'cancelled') {
        // Bestellung und Positionen löschen
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM order_items WHERE order_id = :oid')
            ->execute(['oid' => $orderId]);
        $pdo->prepare('DELETE FROM orders WHERE id = :oid')
            ->execute(['oid' => $orderId]);
        $pdo->commit();
    } else {
        // Nur Status aktualisieren
        $stmt = $pdo->prepare('
            UPDATE orders
            SET status = :status
            WHERE id = :oid
        ');
        $stmt->execute([
            'status' => $newStatus,
            'oid'    => $orderId,
        ]);
    }
}

// Zurückleiten je nach Referer
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($ref, 'menu.php') !== false) {
    header('Location: menu.php');
} else {
    header('Location: orders.php');
}
exit;
