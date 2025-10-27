<?php

declare(strict_types=1);

$databasePath = __DIR__ . '/../data/database.sqlite';

if (!is_dir(dirname($databasePath))) {
    mkdir(dirname($databasePath), 0777, true);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "user",
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        event_date TEXT NOT NULL,
        location TEXT,
        description TEXT,
        created_by INTEGER,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS donations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        donor_name TEXT NOT NULL,
        donation_date TEXT NOT NULL,
        amount REAL NOT NULL DEFAULT 0,
        type TEXT NOT NULL,
        event_id INTEGER,
        created_by INTEGER,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )'
);

$ensureColumn = static function(PDO $pdo, string $table, string $column, string $definition): void {
    $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $info) {
        if (isset($info['name']) && strtolower((string) $info['name']) === strtolower($column)) {
            return;
        }
    }

    try {
        $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    } catch (PDOException $e) {
        // Column may already exist; ignore error.
    }
};

$ensureColumn($pdo, 'events', 'created_by', 'INTEGER');
$ensureColumn($pdo, 'donations', 'created_by', 'INTEGER');

$adminId = $pdo->query('SELECT id FROM users WHERE role = "admin" ORDER BY id LIMIT 1')->fetchColumn();

if ($adminId) {
    $stmt = $pdo->prepare('UPDATE events SET created_by = :adminId WHERE created_by IS NULL');
    $stmt->execute([':adminId' => $adminId]);

    $stmt = $pdo->prepare('UPDATE donations SET created_by = :adminId WHERE created_by IS NULL');
    $stmt->execute([':adminId' => $adminId]);
}

$userCount = (int) $pdo->query('SELECT COUNT(*) as count FROM users')->fetchColumn();

if ($userCount === 0) {
    $defaultPassword = password_hash('changeme123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
    $stmt->execute([
        ':name' => 'Admin User',
        ':email' => 'admin@donationtracker.local',
        ':password_hash' => $defaultPassword,
        ':role' => 'admin',
    ]);
}

return $pdo;

