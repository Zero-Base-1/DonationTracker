<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = $_SESSION['user']['id'] ?? null;
$_SESSION = [];
session_destroy();
clearRememberedUser($userId !== null ? (int) $userId : null);

redirect('login.php');

