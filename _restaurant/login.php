<?php
declare(strict_types=1);
require 'config.php';

// Prüfe, ob Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben holen und trimmen
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    // Nutzer suchen
    $stmt = $pdo->prepare('
        SELECT id, password_hash, display_name
        FROM waiters
        WHERE username = :username
    ');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // Passwort prüfen
    if ($user !== false && password_verify($password, $user['password_hash'])) {
        $_SESSION['waiter_id']    = (int)$user['id'];
        $_SESSION['display_name'] = $user['display_name'];
        header('Location: menu.php');
        exit;
    } else {
        $error = 'Benutzername oder Passwort ungültig';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Kellner-Anmeldung</title>
</head>
<body>
    <h2>Kellner-Anmeldung</h2>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label>Benutzername:
                <input type="text" name="username" required>
            </label>
        </p>
        <p>
            <label>Passwort:
                <input type="password" name="password" required>
            </label>
        </p>
        <button type="submit">Anmelden</button>
    </form>
</body>
</html>
