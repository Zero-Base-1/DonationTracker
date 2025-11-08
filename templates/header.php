<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? $_SESSION['user']['name'] : null;
$sidebarCollapsedPreference = isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true';

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
    <link rel="icon" type="image/svg+xml" href="<?= app_url('assets/icon.svg'); ?>" />
    <link rel="stylesheet" href="<?= app_url('assets/styles.css'); ?>" />
</head>

<body class="bg-soft text-slate-900" style="font-family: 'Inter', sans-serif;"<?= $sidebarCollapsedPreference ? ' data-sidebar-collapsed="true"' : ''; ?>>
    <script>
        (function () {
            const DESKTOP_BREAKPOINT = 768;
            const STORAGE_KEY = 'sidebarCollapsed';
            const COOKIE_NAME = 'sidebarCollapsed';
            const COOKIE_MAX_AGE = 60 * 60 * 24 * 365; // 1 year

            function readCookie(name) {
                const escapedName = name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
                const pattern = new RegExp(`(?:^|;\\s*)${escapedName}=([^;]*)`);
                const match = document.cookie.match(pattern);
                return match ? decodeURIComponent(match[1]) : null;
            }

            function persistCookie(value) {
                const parts = [
                    `${COOKIE_NAME}=${value}`,
                    'path=/',
                    `max-age=${COOKIE_MAX_AGE}`,
                    'samesite=lax'
                ];

                if (window.location && window.location.protocol === 'https:') {
                    parts.push('secure');
                }

                document.cookie = parts.join(';');
            }

            function determineShouldCollapse() {
                const cookieValue = readCookie(COOKIE_NAME);
                if (cookieValue === 'true') {
                    return true;
                }
                if (cookieValue === 'false') {
                    return false;
                }

                if (typeof Storage === 'undefined') {
                    return false;
                }

                try {
                    const storedValue = localStorage.getItem(STORAGE_KEY);
                    if (storedValue === 'true') {
                        persistCookie('true');
                        return true;
                    }
                    if (storedValue === 'false') {
                        persistCookie('false');
                    }
                } catch (err) {
                    // Ignore localStorage errors
                }

                return false;
            }

            try {
                if (window.innerWidth < DESKTOP_BREAKPOINT) {
                    return;
                }

                if (!determineShouldCollapse()) {
                    return;
                }

                document.body.setAttribute('data-sidebar-collapsed', 'true');

                const style = document.createElement('style');
                style.textContent = '#sidebar { transition: none !important; }';
                document.head.appendChild(style);

                const applyState = () => {
                    const sidebar = document.getElementById('sidebar');
                    if (!sidebar) {
                        return false;
                    }

                    if (!sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                    }

                    requestAnimationFrame(() => {
                        style.remove();
                    });

                    return true;
                };

                if (applyState()) {
                    return;
                }

                const observer = new MutationObserver(() => {
                    if (applyState()) {
                        observer.disconnect();
                    }
                });

                observer.observe(document.body, { childList: true, subtree: true });

                setTimeout(() => {
                    if (applyState()) {
                        observer.disconnect();
                    }
                }, 10);
            } catch (err) {
                // Silently ignore to avoid blocking render
            }
        })();
    </script>
    <div class="min-h-screen flex bg-soft">
        <?php if ($isLoggedIn) : ?>
            <aside id="sidebar" class="fixed md:relative inset-y-0 left-0 z-40 hidden md:flex flex-col w-64 lg:w-72 bg-primary text-white transition-all duration-300 md:translate-x-0 -translate-x-full<?= $sidebarCollapsedPreference ? ' collapsed' : ''; ?>" data-sidebar-init="true">
                <div class="px-4 lg:px-6 py-6 lg:py-8 border-b border-white/10 relative">
                    <div class="flex items-center gap-2 lg:gap-3 min-w-0 sidebar-header-content">
                        <span class="inline-flex items-center justify-center w-9 h-9 lg:w-10 lg:h-10 rounded-full bg-accent/20 text-accent font-semibold text-sm lg:text-base flex-shrink-0">DT</span>
                        <div class="sidebar-text min-w-0 flex-1">
                            <p class="text-base lg:text-lg font-semibold leading-tight">
                                <span class="block">Donation</span>
                                <span class="block">Tracker</span>
                            </p>
                        </div>
                        <button id="sidebar-toggle" class="flex-shrink-0 p-2 hover:bg-white/10 rounded-lg transition sidebar-toggle-btn" aria-label="Toggle sidebar">
                            <svg class="w-4 h-4 lg:w-5 lg:h-5 sidebar-icon-expanded" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                            </svg>
                            <svg class="w-4 h-4 lg:w-5 lg:h-5 sidebar-icon-collapsed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <nav class="flex-1 px-4 lg:px-6 py-4 lg:py-6 space-y-1.5 lg:space-y-2 text-xs lg:text-sm overflow-y-auto">
                    <a href="<?= app_url('dashboard.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'dashboard') echo 'bg-white/10'; ?>" title="Dashboard">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Dashboard</span>
                    </a>
                    <a href="<?= app_url('donations.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'donations') echo 'bg-white/10'; ?>" title="Donations">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Donations</span>
                    </a>
                    <a href="<?= app_url('events.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'events') echo 'bg-white/10'; ?>" title="Events">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Events</span>
                    </a>
                    <a href="<?= app_url('reports.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'reports') echo 'bg-white/10'; ?>" title="Reports">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Reports</span>
                    </a>
                    <?php if (isAdmin()) : ?>
                        <a href="<?= app_url('admin/users.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'users') echo 'bg-white/10'; ?>" title="Users">
                            <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="sidebar-text whitespace-nowrap">Users</span>
                        </a>
                        <a href="<?= app_url('admin/audit.php'); ?>" class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg hover:bg-white/10 transition <?php if (($activeNav ?? '') === 'audit') echo 'bg-white/10'; ?>" title="Audit Log">
                            <svg class="w-4 h-4 lg:w-5 lg:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="sidebar-text whitespace-nowrap">Audit Log</span>
                        </a>
                    <?php endif; ?>
                </nav>
                <div class="px-4 lg:px-6 py-4 lg:py-6 border-t border-white/10 text-xs lg:text-sm">
                    <p class="sidebar-text text-white/80 mb-3">Signed in as</p>
                    <p class="sidebar-text font-semibold text-white truncate"><?= htmlspecialchars($userName ?? 'Unknown user'); ?></p>
                    <a href="<?= app_url('logout.php'); ?>" class="mt-4 inline-flex items-center gap-2 text-sm text-accent hover:text-white transition" title="Log out">
                        <span class="sidebar-text">Log out</span>
                        <span class="sidebar-text">→</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <main class="flex-1 min-h-screen">
            <header class="flex items-center justify-between px-4 sm:px-6 md:px-10 py-4 sm:py-5 md:py-6 bg-white border-b border-slate-200 sticky top-0 z-10">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <?php if ($isLoggedIn) : ?>
                        <button class="md:hidden inline-flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-primary/10 text-primary flex-shrink-0" id="mobile-nav-toggle" aria-label="Toggle navigation">
                            ☰
                        </button>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="text-base sm:text-lg md:text-xl font-semibold text-primary truncate"><?= $pageTitle ?? 'DonationTracker'; ?></p>
                        <p class="hidden sm:block text-xs sm:text-sm text-slate-500 truncate">DonationTracker – A Charity Donations & Events Management Platform</p>
                    </div>
                </div>
                <?php if ($isLoggedIn) : ?>
                    <div class="hidden md:flex items-center gap-4 text-sm flex-shrink-0">
                        <span class="text-slate-600"><?= htmlspecialchars($userName ?? 'Admin'); ?></span>
                        <a href="<?= app_url('logout.php'); ?>" class="inline-flex items-center gap-2 text-sm bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition">Log Out</a>
                    </div>
                    <a href="<?= app_url('logout.php'); ?>" class="md:hidden inline-flex items-center justify-center text-xs bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition flex-shrink-0">Log Out</a>
                <?php else : ?>
                    <a href="<?= app_url('login.php'); ?>" class="inline-flex items-center gap-2 text-xs sm:text-sm bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition flex-shrink-0">Log In</a>
                <?php endif; ?>
            </header>
            <section class="px-4 sm:px-6 md:px-10 py-6 sm:py-8 md:py-10">

