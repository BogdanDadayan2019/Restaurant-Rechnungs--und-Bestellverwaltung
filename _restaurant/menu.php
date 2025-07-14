<?php
declare(strict_types=1);
require 'config.php';

// Nur angemeldete Kellner dürfen hier
if (!isset($_SESSION['waiter_id'])) {
    header('Location: login.php');
    exit;
}

// Flash-Nachricht holen und löschen
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Verfügbare Menüpunkte laden
$stmtMenu = $pdo->query('
    SELECT id, name, price
    FROM menu_items
    WHERE is_available = 1
    ORDER BY name
');
$menuItems = $stmtMenu->fetchAll();

// Eigene Bestellungen laden
$stmtOrders = $pdo->prepare('
    SELECT id, table_id, status, created_at
    FROM orders
    WHERE waiter_id = :wid
    ORDER BY created_at DESC
');
$stmtOrders->execute(['wid' => $_SESSION['waiter_id']]);
$myOrders = $stmtOrders->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Speisekarte</title>
    <link rel="stylesheet" href="style.css">
    <style>
      table { border-collapse: collapse; width: 100% }
      th, td { border: 1px solid #ccc; padding: 6px; }
      th { background: #f0f0f0; }
      a { color: #06c; text-decoration: none; }
      a:hover { text-decoration: underline; }
      .flash { color: green; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Speisekarte</h2>
    <p>
        Kellner: <?= htmlspecialchars((string)$_SESSION['display_name']) ?> |
        <a href="logout.php">Abmelden</a>
    </p>

    <?php if ($flash): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <form action="save_order.php" method="post" onsubmit="return validateOrder()">
        <p>
            <label>Tischnummer:
                <input type="number" name="table_id" min="1" value="1" required>
            </label>
        </p>
        <ul>
            <?php foreach ($menuItems as $item): ?>
                <li>
                    <label>
                        <input
                            type="checkbox"
                            name="items[<?= (int)$item['id'] ?>][menu_id]"
                            value="<?= (int)$item['id'] ?>"
                        >
                        <?= htmlspecialchars($item['name']) ?> —
                        <?= number_format((float)$item['price'], 2, ',', ' ') ?> €
                    </label>
                    <input
                        type="number"
                        name="items[<?= (int)$item['id'] ?>][qty]"
                        min="1" value="1"
                        style="width:50px;"
                    >
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="submit">Bestellung an Küche senden</button>
    </form>

    <p>
        <a href="orders.php">
            <button type="button">Bestellungen in der Küche</button>
        </a>
    </p>

    <h3>Meine Bestellungen</h3>
    <table>
        <tr>
            <th>ID</th><th>Tisch</th><th>Status</th><th>Zeit</th><th>Aktionen</th>
        </tr>
        <?php if (empty($myOrders)): ?>
            <tr><td colspan="5">Keine Bestellungen.</td></tr>
        <?php else: foreach ($myOrders as $o): ?>
            <tr>
                <td><?= (int)$o['id'] ?></td>
                <td><?= (int)$o['table_id'] ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
                <td><?= htmlspecialchars($o['created_at']) ?></td>
                <td>
                    <a href="bill.php?order_id=<?= (int)$o['id'] ?>">Rechnung</a>
                    <?php if ($o['status'] !== 'ready'): ?>
                        | <a href="order_details.php?id=<?= (int)$o['id'] ?>">Bearbeiten</a>
                    <?php endif; ?>
                    | <a
                        href="update_status.php?order_id=<?= (int)$o['id'] ?>&new_status=cancelled&from=menu"
                        onclick="return confirm('Wirklich stornieren?')"
                      >Stornieren</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </table>

    <script>
    // Mindestens ein Gericht wählen
    function validateOrder() {
        for (const b of document.querySelectorAll('input[type="checkbox"]')) {
            if (b.checked) return true;
        }
        alert('Bitte wählen Sie mindestens ein Gericht aus.');
        return false;
    }
    </script>
</body>
</html>
