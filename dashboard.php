<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';
requireLogin();
require __DIR__ . '/includes/queries.php';

$stats = getDonationStats($pdo);
$eventStats = getEventStats($pdo);
$recentDonations = getRecentDonations($pdo);
$recentEvents = getRecentEvents($pdo);
$monthlyTotals = getMonthlyDonationTotals($pdo);

$monthlyLabels = array_map(static fn($row) => $row['month'], $monthlyTotals);
$monthlyValues = array_map(static fn($row) => (float) $row['total_amount'], $monthlyTotals);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/templates/header.php';

?>

<div class="grid gap-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <p class="text-sm text-slate-500">Total Donations</p>
            <p class="text-3xl font-semibold text-primary mt-2"><?= formatCurrency((float) $stats['total_amount']); ?></p>
            <p class="text-xs text-slate-400 mt-1">All recorded monetary contributions</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <p class="text-sm text-slate-500">Donations Logged</p>
            <p class="text-3xl font-semibold text-primary mt-2"><?= (int) $stats['donation_count']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Including cash and in-kind donations</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <p class="text-sm text-slate-500">Unique Donors</p>
            <p class="text-3xl font-semibold text-primary mt-2"><?= (int) $stats['unique_donors']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Individuals or organizations</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <p class="text-sm text-slate-500">Upcoming Events</p>
            <p class="text-3xl font-semibold text-primary mt-2"><?= (int) $eventStats['upcoming_events']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Within the current or future dates</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-primary">Donations Over Time</p>
                <div class="text-xs text-slate-500">Last 6 months</div>
            </div>
            <div class="mt-6 h-72">
                <canvas id="donationsChart" class="w-full h-full"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-primary">Event Summary</p>
                <div class="text-xs text-slate-500">Snapshot</div>
            </div>
            <div class="mt-6 space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Total Events</p>
                    <p class="text-2xl font-semibold text-primary mt-1"><?= (int) $eventStats['total_events']; ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Upcoming Events</p>
                    <p class="text-2xl font-semibold text-primary mt-1"><?= (int) $eventStats['upcoming_events']; ?></p>
                </div>
                <a href="/DonationTracker/events.php" class="inline-flex items-center gap-2 text-sm text-accent hover:text-primary transition">Manage events →</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <p class="text-lg font-semibold text-primary">Recent Donations</p>
                <a href="/DonationTracker/donations.php" class="text-sm text-accent hover:text-primary transition">View all</a>
            </div>
            <ul class="divide-y divide-slate-200">
                <?php if (count($recentDonations) === 0) : ?>
                    <li class="px-6 py-5 text-sm text-slate-500">No donations recorded yet.</li>
                <?php else : ?>
                    <?php foreach ($recentDonations as $donation) : ?>
                        <li class="px-6 py-5 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-primary"><?= htmlspecialchars($donation['donor_name']); ?></p>
                                <p class="text-xs text-slate-500 mt-1">Event: <?= htmlspecialchars($donation['event_name'] ?? 'Unassigned'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-primary"><?= formatCurrency((float) $donation['amount']); ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars(date('M j, Y', strtotime($donation['donation_date']))); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <p class="text-lg font-semibold text-primary">Upcoming & Recent Events</p>
                <a href="/DonationTracker/events.php" class="text-sm text-accent hover:text-primary transition">View all</a>
            </div>
            <ul class="divide-y divide-slate-200">
                <?php if (count($recentEvents) === 0) : ?>
                    <li class="px-6 py-5 text-sm text-slate-500">No events recorded yet.</li>
                <?php else : ?>
                    <?php foreach ($recentEvents as $event) : ?>
                        <li class="px-6 py-5 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-primary"><?= htmlspecialchars($event['name']); ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($event['location'] ?: 'Location TBA'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-primary"><?= htmlspecialchars(date('M j, Y', strtotime($event['event_date']))); ?></p>
                                <p class="text-xs text-slate-500 mt-1">Added <?= htmlspecialchars(date('M j', strtotime($event['created_at']))); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('donationsChart');
    if (ctx) {
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyLabels, JSON_THROW_ON_ERROR); ?>,
                datasets: [{
                    label: 'Donations',
                    data: <?= json_encode($monthlyValues, JSON_THROW_ON_ERROR); ?>,
                    borderColor: '#38BDF8',
                    backgroundColor: 'rgba(56, 189, 248, 0.2)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + value
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
</script>

<?php
include __DIR__ . '/templates/footer.php';

