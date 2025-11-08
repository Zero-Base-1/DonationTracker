<?php

declare(strict_types=1);

// MySQL connection configuration
$host = 'sql211.infinityfree.com';
$dbname = 'if0_40366769_donationtracker';
$username = 'if0_40366769';
$password = 'XOYSVYVOHx0e8no';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT "user",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        location VARCHAR(255),
        description TEXT,
        created_by INT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_name VARCHAR(255) NOT NULL,
        donation_date DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        type VARCHAR(50) NOT NULL,
        event_id INT,
        created_by INT,
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token_hash (token_hash),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
} catch (PDOException $e) {
    error_log('Failed to ensure password_resets table: ' . $e->getMessage());
    if (isset($_GET['debug'])) {
        echo 'Failed to ensure password_resets table: ' . htmlspecialchars($e->getMessage());
    }
}

$ensureColumn = static function(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = ? 
                           AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    $exists = (int) $stmt->fetchColumn();
    
    if ($exists === 0) {
        try {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
        } catch (PDOException $e) {
            // Column may already exist; ignore error.
        }
    }
};

$ensureColumn($pdo, 'events', 'created_by', 'INT');
$ensureColumn($pdo, 'donations', 'created_by', 'INT');

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

