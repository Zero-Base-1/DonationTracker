<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';
requireLogin();
require __DIR__ . '/includes/queries.php';

$userId = currentUserId();

if (isAdmin()) {
    $stats = getDonationStats($pdo);
    $eventStats = getEventStats($pdo);
    $recentDonations = getRecentDonations($pdo);
    $recentEvents = getRecentEvents($pdo);
    $monthlyTotals = getMonthlyDonationTotals($pdo);
    $dailyTotals = getDailyDonationTotals($pdo);
    $eventDonationSeries = getEventDonationSeries($pdo);
} else {
    $stats = getDonationStats($pdo, $userId);
    $eventStats = getEventStats($pdo, $userId);
    $recentDonations = getRecentDonations($pdo, 5, $userId);
    $recentEvents = getRecentEvents($pdo, 5, $userId);
    $monthlyTotals = getMonthlyDonationTotals($pdo, 6, $userId);
    $dailyTotals = getDailyDonationTotals($pdo, 30, $userId);
    $eventDonationSeries = getEventDonationSeries($pdo, $userId);
}

$monthlyLabels = array_map(static fn($row) => $row['month'], $monthlyTotals);
$monthlyValues = array_map(static fn($row) => (float) $row['total_amount'], $monthlyTotals);
$dailyLabels = array_map(static fn($row) => date('M j', strtotime($row['day'])), $dailyTotals);
$dailyValues = array_map(static fn($row) => (float) $row['total_amount'], $dailyTotals);
$eventLabels = array_map(static fn($row) => $row['event_name'], $eventDonationSeries);
$eventDates = array_map(static fn($row) => $row['event_date'], $eventDonationSeries);
$eventTotals = array_map(static fn($row) => (float) $row['total_amount'], $eventDonationSeries);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/templates/header.php';

?>

<div class="grid gap-6 lg:gap-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow stat-card">
            <p class="text-xs sm:text-sm text-slate-500">Total Donations</p>
            <p class="text-2xl sm:text-3xl font-semibold text-primary mt-2 stat-number"><?= formatCurrency((float) $stats['total_amount']); ?></p>
            <p class="text-xs text-slate-400 mt-1">All recorded monetary contributions</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow stat-card">
            <p class="text-xs sm:text-sm text-slate-500">Donations Logged</p>
            <p class="text-2xl sm:text-3xl font-semibold text-primary mt-2 stat-number"><?= (int) $stats['donation_count']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Including cash and in-kind donations</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow stat-card">
            <p class="text-xs sm:text-sm text-slate-500">Unique Donors</p>
            <p class="text-2xl sm:text-3xl font-semibold text-primary mt-2 stat-number"><?= (int) $stats['unique_donors']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Individuals or organizations</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow stat-card">
            <p class="text-xs sm:text-sm text-slate-500">Upcoming Events</p>
            <p class="text-2xl sm:text-3xl font-semibold text-primary mt-2 stat-number"><?= (int) $eventStats['upcoming_events']; ?></p>
            <p class="text-xs text-slate-400 mt-1">Within the current or future dates</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow">
            <div class="flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-base sm:text-lg font-semibold text-primary">Donations Insights</p>
                    <p class="text-xs text-slate-500">Analyze giving trends and event performance</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 text-xs">
                    <button type="button" data-chart="time" class="chart-toggle px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:text-primary hover:border-primary transition active whitespace-nowrap">Donations Over Time</button>
                    <button type="button" data-chart="events" class="chart-toggle px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:text-primary hover:border-primary transition whitespace-nowrap">Event Performance</button>
                </div>
            </div>
            <div id="timeFilterContainer" class="mt-3 sm:mt-4 flex items-center gap-2 text-xs" style="display: none;">
                <label class="text-slate-600 whitespace-nowrap">Filter by:</label>
                <select id="timeFilter" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition">
                    <option value="monthly">Monthly</option>
                    <option value="daily">Daily</option>
                </select>
            </div>
            <div class="mt-4 sm:mt-6 h-64 sm:h-72 chart-container">
                <canvas id="donationsChart" class="w-full h-full"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow">
            <div class="flex items-center justify-between">
                <p class="text-base sm:text-lg font-semibold text-primary">Event Summary</p>
                <div class="text-xs text-slate-500">Snapshot</div>
            </div>
            <div class="mt-4 sm:mt-6 space-y-4">
                <div>
                    <p class="text-xs sm:text-sm text-slate-500">Total Events</p>
                    <p class="text-xl sm:text-2xl font-semibold text-primary mt-1"><?= (int) $eventStats['total_events']; ?></p>
                </div>
                <div>
                    <p class="text-xs sm:text-sm text-slate-500">Upcoming Events</p>
                    <p class="text-xl sm:text-2xl font-semibold text-primary mt-1"><?= (int) $eventStats['upcoming_events']; ?></p>
                </div>
                <a href="<?= app_url('events.php'); ?>" class="inline-flex items-center gap-2 text-sm text-accent hover:text-primary transition">Manage events →</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-200">
                <p class="text-base sm:text-lg font-semibold text-primary">Recent Donations</p>
                <a href="<?= app_url('donations.php'); ?>" class="text-xs sm:text-sm text-accent hover:text-primary transition">View all</a>
            </div>
            <ul class="divide-y divide-slate-200">
                <?php if (count($recentDonations) === 0) : ?>
                    <li class="px-4 sm:px-6 py-4 sm:py-5 text-sm text-slate-500">No donations recorded yet.</li>
                <?php else : ?>
                    <?php foreach ($recentDonations as $donation) : ?>
                        <li class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-primary truncate"><?= htmlspecialchars($donation['donor_name']); ?></p>
                                <p class="text-xs text-slate-500 mt-1">Event: <?= htmlspecialchars($donation['event_name'] ?? 'Unassigned'); ?></p>
                            </div>
                            <div class="text-left sm:text-right flex-shrink-0">
                                <p class="text-sm font-semibold text-primary"><?= formatCurrency((float) $donation['amount']); ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars(date('M j, Y', strtotime($donation['donation_date']))); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-200">
                <p class="text-base sm:text-lg font-semibold text-primary">Upcoming & Recent Events</p>
                <a href="<?= app_url('events.php'); ?>" class="text-xs sm:text-sm text-accent hover:text-primary transition">View all</a>
            </div>
            <ul class="divide-y divide-slate-200">
                <?php if (count($recentEvents) === 0) : ?>
                    <li class="px-4 sm:px-6 py-4 sm:py-5 text-sm text-slate-500">No events recorded yet.</li>
                <?php else : ?>
                    <?php foreach ($recentEvents as $event) : ?>
                        <li class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-primary truncate"><?= htmlspecialchars($event['name']); ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($event['location'] ?: 'Location TBA'); ?></p>
                            </div>
                            <div class="text-left sm:text-right flex-shrink-0">
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
        const eventDates = <?= json_encode(array_map(static fn($date) => $date ? date('M j, Y', strtotime($date)) : null, $eventDates), JSON_THROW_ON_ERROR); ?>;

        const monthlyLabels = <?= json_encode($monthlyLabels, JSON_THROW_ON_ERROR); ?>;
        const monthlyValues = <?= json_encode($monthlyValues, JSON_THROW_ON_ERROR); ?>;
        const dailyLabels = <?= json_encode($dailyLabels, JSON_THROW_ON_ERROR); ?>;
        const dailyValues = <?= json_encode($dailyValues, JSON_THROW_ON_ERROR); ?>;

        const chartConfigs = {
            time: {
                monthly: {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Donations',
                            data: monthlyValues,
                            borderColor: '#38BDF8',
                            backgroundColor: 'rgba(56, 189, 248, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                        }]
                    }
                },
                daily: {
                    type: 'line',
                    data: {
                        labels: dailyLabels,
                        datasets: [{
                            label: 'Donations',
                            data: dailyValues,
                            borderColor: '#38BDF8',
                            backgroundColor: 'rgba(56, 189, 248, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                        }]
                    }
                }
            },
            events: {
                type: 'bar',
                data: {
                    labels: <?= json_encode($eventLabels, JSON_THROW_ON_ERROR); ?>,
                    datasets: [{
                        label: 'Total Raised',
                        data: <?= json_encode($eventTotals, JSON_THROW_ON_ERROR); ?>,
                        backgroundColor: 'rgba(14, 165, 233, 0.7)',
                        borderColor: '#0EA5E9',
                        borderWidth: 1,
                    }]
                }
            }
        };

        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => '₱' + value
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = context.parsed.y ?? context.parsed;
                            const formatted = typeof value === 'number' ? value.toLocaleString() : value;
                            return `₱${formatted}`;
                        },
                        afterLabel: (context) => {
                            const datasetLabel = context.dataset.label;
                            const value = context.parsed.y;
                            const index = context.dataIndex;
                            if (datasetLabel === 'Total Raised' && typeof value === 'number' && eventDates[index]) {
                                return `Event Date: ${eventDates[index]}`;
                            }
                            return '';
                        }
                    }
                }
            }
        };

        const mergeConfig = (config) => ({
            ...config,
            options: {
                ...baseOptions,
                ...config.options
            }
        });

        let currentTimeFilter = 'monthly';
        let currentChart = new Chart(ctx, mergeConfig(chartConfigs.time.monthly));

        const timeFilterContainer = document.getElementById('timeFilterContainer');
        const timeFilter = document.getElementById('timeFilter');

        const updateTimeChart = (filter) => {
            currentTimeFilter = filter;
            const config = chartConfigs.time[filter];
            if (config) {
                currentChart.destroy();
                currentChart = new Chart(ctx, mergeConfig(config));
            }
        };

        if (timeFilter) {
            timeFilter.addEventListener('change', (e) => {
                updateTimeChart(e.target.value);
            });
        }

        const buttons = document.querySelectorAll('.chart-toggle');
        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-chart');
                if (!chartConfigs[target]) {
                    return;
                }

                if (btn.classList.contains('active')) {
                    return;
                }

                buttons.forEach((b) => b.classList.remove('active', 'border-primary', 'text-primary'));
                btn.classList.add('active', 'border-primary', 'text-primary');

                if (target === 'time') {
                    if (timeFilterContainer) {
                        timeFilterContainer.style.display = 'flex';
                    }
                    updateTimeChart(currentTimeFilter);
                } else {
                    if (timeFilterContainer) {
                        timeFilterContainer.style.display = 'none';
                    }
                    currentChart.destroy();
                    currentChart = new Chart(ctx, mergeConfig(chartConfigs[target]));
                }
            });
        });

        // Show filter on initial load if "Donations Over Time" is active
        if (timeFilterContainer) {
            timeFilterContainer.style.display = 'flex';
        }
    }
</script>

<?php
include __DIR__ . '/templates/footer.php';

