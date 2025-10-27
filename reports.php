<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';
requireLogin();
require __DIR__ . '/includes/queries.php';

$userId = currentUserId();

if (isAdmin()) {
    $totalsByEvent = getDonationTotalsByEvent($pdo);
    $totalsByType = getDonationTotalsByType($pdo);
    $recentActivity = getRecentActivity($pdo);
} else {
    $totalsByEvent = getDonationTotalsByEvent($pdo, $userId);
    $totalsByType = getDonationTotalsByType($pdo, $userId);
    $recentActivity = getRecentActivity($pdo, 10, $userId);
}

$pageTitle = 'Reports';
$activeNav = 'reports';
include __DIR__ . '/templates/header.php';

?>

<div class="grid gap-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Donation Totals by Event</p>
                <p class="text-xs text-slate-500">Compare fundraising performance across events.</p>
            </div>
        </div>
        <div class="overflow-x-auto mt-6">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Total Donations</th>
                        <th class="px-6 py-3">Donation Count</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($totalsByEvent) === 0) : ?>
                        <tr>
                            <td colspan="3" class="px-6 py-6 text-center text-sm text-slate-500">No event donation data yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($totalsByEvent as $row) : ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    <?= htmlspecialchars($row['event_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= formatCurrency((float) $row['total_amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= (int) $row['donation_count']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Donation Totals by Type</p>
                <p class="text-xs text-slate-500">Understand how contributions are coming in.</p>
            </div>
        </div>
        <div class="overflow-x-auto mt-6">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Total Donations</th>
                        <th class="px-6 py-3">Donation Count</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($totalsByType) === 0) : ?>
                        <tr>
                            <td colspan="3" class="px-6 py-6 text-center text-sm text-slate-500">No donation type data yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($totalsByType as $row) : ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    <?= htmlspecialchars($row['type']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= formatCurrency((float) $row['total_amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= (int) $row['donation_count']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Recent Activity</p>
                <p class="text-xs text-slate-500">Latest donations and events logged.</p>
            </div>
        </div>
        <ul class="mt-6 space-y-4">
            <?php if (count($recentActivity) === 0) : ?>
                <li class="text-sm text-slate-500">No recent activity to display.</li>
            <?php else : ?>
                <?php foreach ($recentActivity as $item) : ?>
                    <li class="flex items-center justify-between gap-4 bg-slate-50 rounded-xl px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-primary"><?= htmlspecialchars($item['title']); ?></p>
                            <p class="text-xs text-slate-500 mt-1">Type: <?= htmlspecialchars($item['activity_type']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($item['activity_date']))); ?></p>
                            <?php if ($item['activity_type'] === 'Donation' && $item['metric'] !== null) : ?>
                                <p class="text-sm font-semibold text-primary mt-1"><?= formatCurrency((float) $item['metric']); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';

