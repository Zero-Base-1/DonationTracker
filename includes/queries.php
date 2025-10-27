<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function getDonationStats(PDO $pdo, ?int $userId = null): array
{
    if ($userId === null) {
        $totalAmount = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM donations')->fetchColumn();
        $donationCount = (int) $pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
        $uniqueDonors = (int) $pdo->query('SELECT COUNT(DISTINCT donor_name) FROM donations')->fetchColumn();
    } else {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0), COUNT(*), COUNT(DISTINCT donor_name) FROM donations WHERE created_by = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $totalAmount = (float) ($row[0] ?? 0);
        $donationCount = (int) ($row[1] ?? 0);
        $uniqueDonors = (int) ($row[2] ?? 0);
    }

    return [
        'total_amount' => $totalAmount,
        'donation_count' => $donationCount,
        'unique_donors' => $uniqueDonors,
    ];
}

function getEventStats(PDO $pdo, ?int $userId = null): array
{
    if ($userId === null) {
        $totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
        $stmt = $pdo->query('SELECT COUNT(*) FROM events WHERE date(event_date) >= date("now")');
        $upcomingEvents = (int) $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*), SUM(CASE WHEN date(event_date) >= date("now") THEN 1 ELSE 0 END) FROM events WHERE created_by = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $totalEvents = (int) ($row[0] ?? 0);
        $upcomingEvents = (int) ($row[1] ?? 0);
    }

    return [
        'total_events' => $totalEvents,
        'upcoming_events' => $upcomingEvents,
    ];
}

function getRecentDonations(PDO $pdo, int $limit = 5, ?int $userId = null): array
{
    $query = 'SELECT d.*, e.name AS event_name FROM donations d LEFT JOIN events e ON d.event_id = e.id';
    if ($userId !== null) {
        $query .= ' WHERE d.created_by = :user_id';
    }
    $query .= ' ORDER BY d.donation_date DESC, d.created_at DESC LIMIT :limit';

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getRecentEvents(PDO $pdo, int $limit = 5, ?int $userId = null): array
{
    $query = 'SELECT * FROM events';
    if ($userId !== null) {
        $query .= ' WHERE created_by = :user_id';
    }
    $query .= ' ORDER BY date(event_date) DESC, created_at DESC LIMIT :limit';

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getMonthlyDonationTotals(PDO $pdo, int $months = 6, ?int $userId = null): array
{
    $query = 'SELECT strftime("%Y-%m", donation_date) AS month, SUM(amount) AS total_amount FROM donations WHERE donation_date >= date("now", :modifier)';
    if ($userId !== null) {
        $query .= ' AND created_by = :user_id';
    }
    $query .= ' GROUP BY strftime("%Y-%m", donation_date) ORDER BY month';

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':modifier', '-' . $months . ' months', PDO::PARAM_STR);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getDonations(PDO $pdo, ?int $userId = null): array
{
    $query = 'SELECT d.*, e.name AS event_name FROM donations d LEFT JOIN events e ON d.event_id = e.id';
    if ($userId !== null) {
        $query .= ' WHERE d.created_by = :user_id';
    }
    $query .= ' ORDER BY d.donation_date DESC, d.created_at DESC';

    $stmt = $pdo->prepare($query);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getDonation(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM donations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $donation = $stmt->fetch();

    return $donation ?: null;
}

function createDonation(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO donations (donor_name, donation_date, amount, type, event_id, created_by, notes) VALUES (:donor_name, :donation_date, :amount, :type, :event_id, :created_by, :notes)');
    $stmt->execute([
        ':donor_name' => $data['donor_name'],
        ':donation_date' => $data['donation_date'],
        ':amount' => $data['amount'],
        ':type' => $data['type'],
        ':event_id' => $data['event_id'] ?: null,
        ':created_by' => $data['created_by'],
        ':notes' => $data['notes'],
    ]);

    return (int) $pdo->lastInsertId();
}

function updateDonation(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare('UPDATE donations SET donor_name = :donor_name, donation_date = :donation_date, amount = :amount, type = :type, event_id = :event_id, notes = :notes WHERE id = :id');
    $stmt->execute([
        ':donor_name' => $data['donor_name'],
        ':donation_date' => $data['donation_date'],
        ':amount' => $data['amount'],
        ':type' => $data['type'],
        ':event_id' => $data['event_id'] ?: null,
        ':notes' => $data['notes'],
        ':id' => $id,
    ]);
}

function deleteDonation(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM donations WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function getEvents(PDO $pdo, ?int $userId = null): array
{
    $query = 'SELECT * FROM events';
    if ($userId !== null) {
        $query .= ' WHERE created_by = :user_id';
    }
    $query .= ' ORDER BY date(event_date) DESC, created_at DESC';

    $stmt = $pdo->prepare($query);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getEvent(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();

    return $event ?: null;
}

function createEvent(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO events (name, event_date, location, description, created_by) VALUES (:name, :event_date, :location, :description, :created_by)');
    $stmt->execute([
        ':name' => $data['name'],
        ':event_date' => $data['event_date'],
        ':location' => $data['location'],
        ':description' => $data['description'],
        ':created_by' => $data['created_by'],
    ]);

    return (int) $pdo->lastInsertId();
}

function updateEvent(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare('UPDATE events SET name = :name, event_date = :event_date, location = :location, description = :description WHERE id = :id');
    $stmt->execute([
        ':name' => $data['name'],
        ':event_date' => $data['event_date'],
        ':location' => $data['location'],
        ':description' => $data['description'],
        ':id' => $id,
    ]);
}

function deleteEvent(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function getDonationTotalsByEvent(PDO $pdo, ?int $userId = null): array
{
    $query = 'SELECT e.name AS event_name, COALESCE(SUM(d.amount), 0) AS total_amount, COUNT(d.id) AS donation_count
        FROM events e
        LEFT JOIN donations d ON d.event_id = e.id';
    if ($userId !== null) {
        $query .= ' WHERE e.created_by = :user_id';
    }
    $query .= ' GROUP BY e.id, e.name ORDER BY total_amount DESC';

    $stmt = $pdo->prepare($query);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getDonationTotalsByType(PDO $pdo, ?int $userId = null): array
{
    $query = 'SELECT type, COALESCE(SUM(amount), 0) AS total_amount, COUNT(*) AS donation_count FROM donations';
    if ($userId !== null) {
        $query .= ' WHERE created_by = :user_id';
    }
    $query .= ' GROUP BY type ORDER BY total_amount DESC';

    $stmt = $pdo->prepare($query);
    if ($userId !== null) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

function getRecentActivity(PDO $pdo, int $limit = 10, ?int $userId = null): array
{
    if ($userId === null) {
        $sql = 'SELECT * FROM (
            SELECT donor_name AS title, donation_date AS activity_date, "Donation" AS activity_type, amount AS metric, created_at
            FROM donations
            UNION ALL
            SELECT name AS title, event_date AS activity_date, "Event" AS activity_type, NULL AS metric, created_at
            FROM events
            UNION ALL
            SELECT name AS title, created_at AS activity_date, "User" AS activity_type, NULL AS metric, created_at
            FROM users
        ) AS activity ORDER BY date(activity_date) DESC, datetime(created_at) DESC LIMIT :limit';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    } else {
        $sql = 'SELECT * FROM (
            SELECT donor_name AS title, donation_date AS activity_date, "Donation" AS activity_type, amount AS metric, created_at
            FROM donations WHERE created_by = :user_id
            UNION ALL
            SELECT name AS title, event_date AS activity_date, "Event" AS activity_type, NULL AS metric, created_at
            FROM events WHERE created_by = :user_id
        ) AS activity ORDER BY date(activity_date) DESC, datetime(created_at) DESC LIMIT :limit';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();

    return $stmt->fetchAll();
}

function getUsers(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY datetime(created_at) DESC')->fetchAll();
}

function getActivityFeed(PDO $pdo, int $limit = 50): array
{
    $stmt = $pdo->prepare('SELECT * FROM (
        SELECT donor_name AS title, donation_date AS activity_date, "Donation" AS activity_type, amount AS metric, created_at
        FROM donations
        UNION ALL
        SELECT name AS title, event_date AS activity_date, "Event" AS activity_type, NULL AS metric, created_at
        FROM events
        UNION ALL
        SELECT name AS title, created_at AS activity_date, "User" AS activity_type, NULL AS metric, created_at
        FROM users
    ) AS feed ORDER BY datetime(created_at) DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

