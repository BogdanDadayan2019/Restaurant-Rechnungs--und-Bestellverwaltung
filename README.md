# Restaurant-Rechnungs--und-Bestellverwaltung

Projektbeschreibung
Dieses Webprojekt ist ein einfaches Restaurant Order-Management-System für Servicepersonal.
Die Anwendung ermöglicht es Kellnern und Kellnerinnen, sich anzumelden, offene Bestellungen einzusehen und für jede Bestellung eine Rechnung zu erstellen und auszudrucken. Dabei werden Menüpunkte aus der Datenbank geladen und zugehörige Bestellposten samt Menge und Preis angezeigt.

Hauptfunktionen

Authentifizierung: Session-basierte Anmeldung für Servicepersonal (Waiter).

Bestellübersicht: Anzeige offener Aufträge mit Details.

Rechnungserstellung: Zusammenstellung der einzelnen Bestellpositionen inkl. Menge, Stückpreis und Gesamtbetrag sowie Druckfunktion.

Sichere Datenbankzugriffe: Prepared Statements mit PDO zur Vermeidung von SQL-Injection.

Systemanforderungen

PHP 7.4 oder höher

MySQL (oder MariaDB)

Webserver (z. B. Apache oder Nginx)


Wichtige Dateien und Verzeichnisse

config.php: Datenbankverbindungsparameter (DSN, Benutzer, Passwort).

login.php: Anmeldeformular und Session-Start.

orders.php: Übersicht über alle Bestellungen.

invoice.php: Anzeige und Druck der Rechnung für eine Bestellung.

menu.php: Menüpunkte-Verwaltung bzw. Anzeige.

style.css: Basis-Stylesheet für Layout und Druck-Styles.

Datenbankschema

-- Tabelle für Menüpunkte
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL
);

-- Tabelle für Bestellungen
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabelle für bestellte Positionen
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  quantity INT NOT NULL,
  price_at_order DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Tabelle für Servicepersonal
CREATE TABLE waiters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

Installation & Nutzung
Repository klonen und in das Document-Root des Webservers kopieren.
Datenbank erstellen und Tabellen mit obigem Schema anlegen.

config.php anpassen:
<?php
// config.php
\$dsn = 'mysql:host=localhost;dbname=restaurant';
\$user = 'dbuser';
\$pass = 'dbpassword';
\$pdo = new PDO(\$dsn, \$user, \$pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

Webbrowser öffnen und login.php aufrufen, mit einem als waiters-User registrieren bzw. in die Datenbank eintragen.
Nach Login weiter zu orders.php, Bestellung auswählen und auf Rechnung klicken.

Autor
Bogdan Dadaian
