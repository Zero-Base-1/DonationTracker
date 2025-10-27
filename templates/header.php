<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? $_SESSION['user']['name'] : null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'DonationTracker'; ?></title>
    <meta name="description" content="DonationTracker – A Charity Donations & Events Management Platform" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F172A',
                        secondary: '#1E293B',
                        accent: '#38BDF8',
                        soft: '#F8FAFC',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/DonationTracker/assets/styles.css" />
</head>

<body class="bg-soft text-slate-900" style="font-family: 'Inter', sans-serif;">
    <div class="min-h-screen flex bg-soft">
        <?php if ($isLoggedIn) : ?>
            <aside class="hidden md:flex md:flex-col w-72 bg-primary text-white">
                <div class="px-6 py-8 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-accent/20 text-accent font-semibold">DT</span>
                        <div>
                            <p class="text-lg font-semibold">DonationTracker</p>
                            <p class="text-xs text-white/70">Simplify giving, amplify impact.</p>
                        </div>
                    </div>
                </div>
                <nav class="flex-1 px-6 py-6 space-y-2 text-sm">
                    <a href="/DonationTracker/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'dashboard') echo 'bg-white/10'; ?>">
                        <span class="text-lg">🏠</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/DonationTracker/donations.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'donations') echo 'bg-white/10'; ?>">
                        <span class="text-lg">💖</span>
                        <span>Donations</span>
                    </a>
                    <a href="/DonationTracker/events.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'events') echo 'bg-white/10'; ?>">
                        <span class="text-lg">📅</span>
                        <span>Events</span>
                    </a>
                    <a href="/DonationTracker/reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'reports') echo 'bg-white/10'; ?>">
                        <span class="text-lg">📊</span>
                        <span>Reports</span>
                    </a>
                    <?php if (isAdmin()) : ?>
                        <a href="/DonationTracker/admin/users.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'users') echo 'bg-white/10'; ?>">
                            <span class="text-lg">🧑‍🤝‍🧑</span>
                            <span>Users</span>
                        </a>
                        <a href="/DonationTracker/admin/audit.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'audit') echo 'bg-white/10'; ?>">
                            <span class="text-lg">🗒️</span>
                            <span>Audit Log</span>
                        </a>
                    <?php endif; ?>
                </nav>
                <div class="px-6 py-6 border-t border-white/10 text-sm">
                    <p class="text-white/80 mb-3">Signed in as</p>
                    <p class="font-semibold text-white"><?= htmlspecialchars($userName ?? 'Unknown user'); ?></p>
                    <a href="/DonationTracker/logout.php" class="mt-4 inline-flex items-center gap-2 text-sm text-accent hover:text-white transition">
                        <span>Log out</span>
                        <span>→</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <main class="flex-1 min-h-screen">
            <header class="flex items-center justify-between px-6 md:px-10 py-6 bg-white border-b border-slate-200 sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <?php if ($isLoggedIn) : ?>
                        <button class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 text-primary" id="mobile-nav-toggle" aria-label="Toggle navigation">
                            ☰
                        </button>
                    <?php endif; ?>
                    <div>
                        <p class="text-xl font-semibold text-primary"><?= $pageTitle ?? 'DonationTracker'; ?></p>
                        <p class="text-sm text-slate-500">DonationTracker – A Charity Donations & Events Management Platform</p>
                    </div>
                </div>
                <?php if ($isLoggedIn) : ?>
                    <div class="hidden md:flex items-center gap-4 text-sm">
                        <span class="text-slate-600"><?= htmlspecialchars($userName ?? 'Admin'); ?></span>
                        <a href="/DonationTracker/logout.php" class="inline-flex items-center gap-2 text-sm bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition">Log Out</a>
                    </div>
                <?php else : ?>
                    <a href="/DonationTracker/login.php" class="inline-flex items-center gap-2 text-sm bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition">Log In</a>
                <?php endif; ?>
            </header>
            <section class="px-6 md:px-10 py-10">

