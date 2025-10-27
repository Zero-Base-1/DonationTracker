<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function getDonationStats(PDO $pdo): array
{
    $totalAmount = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM donations')->fetchColumn();
    $donationCount = (int) $pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
    $uniqueDonors = (int) $pdo->query('SELECT COUNT(DISTINCT donor_name) FROM donations')->fetchColumn();

    return [
        'total_amount' => $totalAmount,
        'donation_count' => $donationCount,
        'unique_donors' => $uniqueDonors,
    ];
}

function getEventStats(PDO $pdo): array
{
    $totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $upcomingEvents = (int) $pdo->query('SELECT COUNT(*) FROM events WHERE date(event_date) >= date("now")')->fetchColumn();

    return [
        'total_events' => $totalEvents,
        'upcoming_events' => $upcomingEvents,
    ];
}

function getRecentDonations(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare('SELECT d.*, e.name AS event_name FROM donations d LEFT JOIN events e ON d.event_id = e.id ORDER BY d.donation_date DESC, d.created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getRecentEvents(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare('SELECT * FROM events ORDER BY date(event_date) DESC, created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getMonthlyDonationTotals(PDO $pdo, int $months = 6): array
{
    $stmt = $pdo->prepare('SELECT strftime("%Y-%m", donation_date) AS month, SUM(amount) AS total_amount FROM donations WHERE donation_date >= date("now", :modifier) GROUP BY strftime("%Y-%m", donation_date) ORDER BY month');
    $stmt->bindValue(':modifier', '-' . $months . ' months', PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getDonations(PDO $pdo): array
{
    return $pdo->query('SELECT d.*, e.name AS event_name FROM donations d LEFT JOIN events e ON d.event_id = e.id ORDER BY d.donation_date DESC, d.created_at DESC')->fetchAll();
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
    $stmt = $pdo->prepare('INSERT INTO donations (donor_name, donation_date, amount, type, event_id, notes) VALUES (:donor_name, :donation_date, :amount, :type, :event_id, :notes)');
    $stmt->execute([
        ':donor_name' => $data['donor_name'],
        ':donation_date' => $data['donation_date'],
        ':amount' => $data['amount'],
        ':type' => $data['type'],
        ':event_id' => $data['event_id'] ?: null,
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

function getEvents(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM events ORDER BY date(event_date) DESC, created_at DESC')->fetchAll();
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
    $stmt = $pdo->prepare('INSERT INTO events (name, event_date, location, description) VALUES (:name, :event_date, :location, :description)');
    $stmt->execute([
        ':name' => $data['name'],
        ':event_date' => $data['event_date'],
        ':location' => $data['location'],
        ':description' => $data['description'],
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

function getDonationTotalsByEvent(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT e.name AS event_name, COALESCE(SUM(d.amount), 0) AS total_amount, COUNT(d.id) AS donation_count FROM events e LEFT JOIN donations d ON d.event_id = e.id GROUP BY e.id, e.name ORDER BY total_amount DESC');

    return $stmt->fetchAll();
}

function getDonationTotalsByType(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT type, COALESCE(SUM(amount), 0) AS total_amount, COUNT(*) AS donation_count FROM donations GROUP BY type ORDER BY total_amount DESC');

    return $stmt->fetchAll();
}

function getRecentActivity(PDO $pdo, int $limit = 10): array
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
    ) AS activity ORDER BY date(activity_date) DESC, datetime(created_at) DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
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

