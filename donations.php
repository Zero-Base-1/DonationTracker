<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';
requireLogin();
require __DIR__ . '/includes/queries.php';

$errors = [];
$userId = currentUserId();
$formData = [
    'id' => '',
    'donor_name' => '',
    'donation_date' => date('Y-m-d'),
    'amount' => '0.00',
    'type' => '',
    'event_id' => '',
    'notes' => '',
];
$successMessage = flash('success');
$errorMessage = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'id' => sanitize($_POST['id'] ?? ''),
        'donor_name' => sanitize($_POST['donor_name'] ?? ''),
        'donation_date' => sanitize($_POST['donation_date'] ?? ''),
        'amount' => sanitize($_POST['amount'] ?? '0'),
        'type' => sanitize($_POST['type'] ?? ''),
        'event_id' => sanitize($_POST['event_id'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? ''),
    ];

    $donationId = $formData['id'] !== '' ? (int) $formData['id'] : null;
    $amountValue = (float) $formData['amount'];
    $eventId = $formData['event_id'] !== '' ? (int) $formData['event_id'] : null;

    if ($formData['donor_name'] === '') {
        $errors[] = 'Donor name is required.';
    }
    if ($formData['donation_date'] === '' || !validateDate($formData['donation_date'])) {
        $errors[] = 'A valid donation date is required.';
    }
    if ($amountValue < 0) {
        $errors[] = 'Amount cannot be negative.';
    }
    if ($formData['type'] === '') {
        $errors[] = 'Donation type is required.';
    }

    if (count($errors) === 0) {
        $payload = [
            'donor_name' => $formData['donor_name'],
            'donation_date' => $formData['donation_date'],
            'amount' => $amountValue,
            'type' => $formData['type'],
            'event_id' => $eventId,
            'created_by' => $userId,
            'notes' => $formData['notes'],
        ];

        if ($donationId) {
            $existing = getDonation($pdo, $donationId);
            if (!$existing || (!isAdmin() && (int) ($existing['created_by'] ?? 0) !== $userId)) {
                flash('error', 'You cannot modify this donation.');
                redirect('/DonationTracker/donations.php');
            }

            unset($payload['created_by']);
            updateDonation($pdo, $donationId, $payload);
            flash('success', 'Donation updated successfully.');
        } else {
            createDonation($pdo, $payload);
            flash('success', 'Donation added successfully.');
        }

        redirect('/DonationTracker/donations.php');
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $existing = getDonation($pdo, $deleteId);
    if (!$existing || (!isAdmin() && (int) ($existing['created_by'] ?? 0) !== $userId)) {
        flash('error', 'You cannot delete this donation.');
        redirect('/DonationTracker/donations.php');
    }

    deleteDonation($pdo, $deleteId);
    flash('success', 'Donation deleted successfully.');
    redirect('/DonationTracker/donations.php');
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $donation = getDonation($pdo, $editId);

    if (!$donation || (!isAdmin() && (int) ($donation['created_by'] ?? 0) !== $userId)) {
        flash('error', 'Donation not found.');
        redirect('/DonationTracker/donations.php');
    }

    $formData = [
        'id' => (string) $donation['id'],
        'donor_name' => $donation['donor_name'],
        'donation_date' => $donation['donation_date'],
        'amount' => (string) $donation['amount'],
        'type' => $donation['type'],
        'event_id' => $donation['event_id'] !== null ? (string) $donation['event_id'] : '',
        'notes' => $donation['notes'] ?? '',
    ];
}

$donations = getDonations($pdo, isAdmin() ? null : $userId);
$events = getEvents($pdo, isAdmin() ? null : $userId);

$pageTitle = 'Donations';
$activeNav = 'donations';
include __DIR__ . '/templates/header.php';

?>

<div class="grid gap-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-primary">Add or Update Donation</p>
                <p class="text-xs text-slate-500">Record donation details to keep your data fresh.</p>
            </div>
        </div>

        <?php if ($errorMessage) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <?= htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">Please review the following</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMessage) : ?>
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3">
                <?= htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']); ?>">
            <div>
                <label class="block text-sm font-medium text-slate-600">Donor Name</label>
                <input type="text" name="donor_name" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['donor_name']); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">Donation Date</label>
                <input type="date" name="donation_date" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['donation_date']); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">Amount</label>
                <input type="number" name="amount" min="0" step="0.01" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['amount']); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">Type</label>
                <select name="type" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition">
                    <option value="" disabled <?php if ($formData['type'] === '') echo 'selected'; ?>>Select type</option>
                    <?php
                    $types = ['Cash', 'In-kind', 'Online', 'Pledge'];
                    foreach ($types as $option) {
                        $selected = $formData['type'] === $option ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">Related Event</label>
                <select name="event_id" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition">
                    <option value="">No event selected</option>
                    <?php foreach ($events as $event) : ?>
                        <option value="<?= (int) $event['id']; ?>" <?php if ((string) $event['id'] === $formData['event_id']) echo 'selected'; ?>><?= htmlspecialchars($event['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600">Notes</label>
                <textarea name="notes" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition"><?= htmlspecialchars($formData['notes']); ?></textarea>
            </div>
            <div class="md:col-span-2 flex gap-4">
                <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Save Donation</button>
                <button type="reset" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Clear</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <p class="text-lg font-semibold text-primary">Donation Records</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Donor</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($donations) === 0) : ?>
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-sm text-slate-500">No donations recorded yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($donations as $donation) : ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    <?= htmlspecialchars($donation['donor_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($donation['donation_date']))); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= formatCurrency((float) $donation['amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars($donation['type']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars($donation['event_name'] ?? '—'); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="?edit=<?= (int) $donation['id']; ?>" class="text-accent hover:text-primary transition">Edit</a>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <a href="?delete=<?= (int) $donation['id']; ?>" class="text-red-500 hover:text-red-600 transition" onclick="return confirm('Delete this donation?');">Delete</a>
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
include __DIR__ . '/templates/footer.php';

