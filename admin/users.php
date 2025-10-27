<?php

declare(strict_types=1);

require __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();
require __DIR__ . '/../includes/queries.php';

$users = getUsers($pdo);

$pageTitle = 'User Directory';
$activeNav = 'users';
include __DIR__ . '/../templates/header.php';

?>

<div class="grid gap-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Registered Accounts</p>
                <p class="text-xs text-slate-500">Review all DonationTracker users and their roles.</p>
            </div>
        </div>

        <div class="overflow-x-auto mt-6">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($users) === 0) : ?>
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-sm text-slate-500">No users have been registered yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($users as $user) : ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    <?= htmlspecialchars($user['name']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars($user['email']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium bg-primary/10 text-primary uppercase">
                                        <?= htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($user['created_at']))); ?>
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


