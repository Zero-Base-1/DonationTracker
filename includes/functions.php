<?php

declare(strict_types=1);

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message === null) {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $msg;
    }

    $_SESSION['flash'][$key] = $message;

    return null;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function truncateText(string $text, int $width, string $suffix = '…'): string
{
    if ($text === '') {
        return $text;
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, $suffix, 'UTF-8');
    }

    if (strlen($text) <= $width) {
        return $text;
    }

    $trimmed = substr($text, 0, max(0, $width - strlen($suffix)));

    return $trimmed . $suffix;
}

function currentUser(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        flash('error', 'Please sign in to continue.');
        redirect('/DonationTracker/login.php');
    }
}

function isAdmin(): bool
{
    $user = currentUser();

    return $user !== null && ($user['role'] ?? 'user') === 'admin';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        flash('error', 'You do not have permission to access that area.');
        redirect('/DonationTracker/dashboard.php');
    }
}

