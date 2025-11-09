<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$pageTitle = 'Welcome';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="glass-panel rounded-2xl sm:rounded-3xl p-6 sm:p-10 md:p-16 card-shadow">
        <div class="flex flex-col md:flex-row items-center gap-6 sm:gap-10">
            <div class="flex-1 space-y-4 sm:space-y-6">
                <p class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-full bg-accent/10 text-accent text-xs sm:text-sm font-medium">
                    Empowering change through better insights
                </p>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-semibold text-primary">DonationTracker</h1>
                <p class="text-base sm:text-lg text-slate-600 leading-relaxed">
                    Streamline how your organization records and manages donations and charity events. Gain clarity on
                    contributions, understand donor engagement, and lead with impact.
                </p>
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 sm:gap-4 pt-4">
                    <a href="<?= app_url('login.php'); ?>" class="inline-flex items-center justify-center px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg bg-primary text-white font-medium text-sm sm:text-base shadow transition hover:bg-secondary">Access Dashboard</a>
                    <a href="#features" class="inline-flex items-center justify-center px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg border border-primary/20 text-primary font-medium text-sm sm:text-base hover:bg-primary/10 transition">Explore Features</a>
                </div>
            </div>
            <div class="flex-1 w-full">
                <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-200 card-shadow">
                    <p class="text-xs sm:text-sm uppercase tracking-wide text-slate-500">Snapshot</p>
                    <p class="text-xl sm:text-2xl font-semibold text-primary mt-2 sm:mt-3">Why DonationTracker?</p>
                    <ul class="mt-4 sm:mt-6 space-y-3 sm:space-y-4 text-xs sm:text-sm text-slate-600">
                        <li class="flex gap-2 sm:gap-3 items-start">
                            <span class="inline-flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-accent/10 text-accent flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M8.5 15.5V19m7-8V19m-3.5-5.5V19M4 7l8-4 8 4v4"></path>
                                </svg>
                            </span>
                            <span>Clear dashboards that highlight total donations, top events, and recent activity.</span>
                        </li>
                        <li class="flex gap-2 sm:gap-3 items-start">
                            <span class="inline-flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-accent/10 text-accent flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4M6 20h12a2 2 0 002-2V8.414a1 1 0 00-.293-.707l-4.414-4.414A1 1 0 0014.586 3H6a2 2 0 00-2 2v13a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            <span>Fast entry forms to capture donor contributions along with event details.</span>
                        </li>
                        <li class="flex gap-2 sm:gap-3 items-start">
                            <span class="inline-flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-accent/10 text-accent flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4l8 4v5c0 4.97-3.582 9.743-8 10-4.418-.257-8-5.03-8-10V8l8-4z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12.5l2 2 3.5-3.5"></path>
                                </svg>
                            </span>
                            <span>User login to keep donation records secure and private.</span>
                        </li>
                        <li class="flex gap-2 sm:gap-3 items-start">
                            <span class="inline-flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-accent/10 text-accent flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.5 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                            <span>Stay informed with summaries of the latest donations and upcoming events.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div id="features" class="mt-10 sm:mt-16 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 md:gap-8 feature-grid">
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 card-shadow">
            <p class="text-xs sm:text-sm uppercase tracking-wide text-primary/70">Feature</p>
            <h2 class="text-lg sm:text-xl font-semibold text-primary mt-2">Donation Management</h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-3 sm:mt-4">Log donor name, donation date, amount or in-kind type, and link contributions to events.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 card-shadow">
            <p class="text-xs sm:text-sm uppercase tracking-wide text-primary/70">Feature</p>
            <h2 class="text-lg sm:text-xl font-semibold text-primary mt-2">Event Tracking</h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-3 sm:mt-4">Keep track of upcoming charity events with event dates, locations, and descriptions.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 card-shadow">
            <p class="text-xs sm:text-sm uppercase tracking-wide text-primary/70">Feature</p>
            <h2 class="text-lg sm:text-xl font-semibold text-primary mt-2">Insights & Reports</h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-3 sm:mt-4">Get monthly totals and see which events mobilize your community the most.</p>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';

