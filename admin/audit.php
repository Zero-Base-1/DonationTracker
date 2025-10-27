<?php

declare(strict_types=1);

require __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();
require __DIR__ . '/../includes/queries.php';

$activityLimit = isset($_GET['limit']) ? max(10, (int) $_GET['limit']) : 50;
$activityFeed = getActivityFeed($pdo, $activityLimit);

$pageTitle = 'Audit Log';
$activeNav = 'audit';
include __DIR__ . '/../templates/header.php';

?>

<div class="grid gap-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Recent Activity</p>
                <p class="text-xs text-slate-500">Combined stream of user registrations, donation entries, and event updates.</p>
            </div>
            <form method="GET" class="flex items-center gap-3 text-xs">
                <label for="limit" class="text-slate-500">Show</label>
                <select id="limit" name="limit" class="rounded-lg border border-slate-200 px-3 py-2 focus:border-accent focus:ring-2 focus:ring-accent/40 transition">
                    <?php foreach ([25, 50, 100] as $option) : ?>
                        <option value="<?= $option; ?>" <?php if ($activityLimit === $option) echo 'selected'; ?>><?= $option; ?> items</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Apply</button>
            </form>
        </div>

        <div class="overflow-x-auto mt-6">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Details</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($activityFeed) === 0) : ?>
                        <tr>
                            <td colspan="3" class="px-6 py-6 text-center text-sm text-slate-500">No activity recorded yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($activityFeed as $item) : ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <?php
                                    $badgeClasses = [
                                        'Donation' => 'bg-emerald-100 text-emerald-700',
                                        'Event' => 'bg-indigo-100 text-indigo-700',
                                        'User' => 'bg-amber-100 text-amber-700',
                                    ];
                                    $type = $item['activity_type'] ?? 'Activity';
                                    $classes = $badgeClasses[$type] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium <?= $classes; ?>">
                                        <?= htmlspecialchars($type); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-primary">
                                        <?= htmlspecialchars($item['title']); ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        <?php if ($type === 'Donation') : ?>
                                            Amount: <?= formatCurrency((float) ($item['metric'] ?? 0)); ?>
                                        <?php elseif ($type === 'Event') : ?>
                                            Event date: <?= htmlspecialchars(date('M j, Y', strtotime($item['activity_date']))); ?>
                                        <?php elseif ($type === 'User') : ?>
                                            New account created
                                        <?php else : ?>
                                            Recorded update
                                        <?php endif; ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <p><?= htmlspecialchars(date('M j, Y', strtotime($item['created_at']))); ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars(date('H:i', strtotime($item['created_at']))); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../templates/footer.php';


