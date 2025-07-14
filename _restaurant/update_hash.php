<?php
declare(strict_types=1);
require 'config.php'; // stellt $pdo bereit

// Zu ändernder Nutzer und neues Passwort
$username    = 'kellner1';
$newPassword = 'secret123';

// Passwort-Hash erzeugen
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
if ($hash === false) {
    exit('Fehler: Passwort-Hash konnte nicht erstellt werden.' . PHP_EOL);
}

try {
    // Passwort-Hash in der Datenbank aktualisieren
    $stmt = $pdo->prepare('
        UPDATE waiters
        SET password_hash = :hash
        WHERE username = :username
    ');
    $stmt->execute([
        'hash'     => $hash,
        'username' => $username,
    ]);

    if ($stmt->rowCount() === 0) {
        echo "Warnung: Kein Datensatz für Nutzer '{$username}' gefunden." . PHP_EOL;
    } else {
        echo "Erfolg: Passwort für Nutzer '{$username}' aktualisiert." . PHP_EOL;
    }
} catch (PDOException $e) {
    // Fehlerbehandlung
    echo 'Datenbank-Fehler: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . PHP_EOL;
    exit(1);
}
