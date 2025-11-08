<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';
requireLogin();
require __DIR__ . '/includes/queries.php';

$errors = [];
$userId = currentUserId();
$formData = [
    'id' => '',
    'name' => '',
    'event_date' => date('Y-m-d'),
    'location' => '',
    'description' => '',
];
$successMessage = flash('success');
$errorMessage = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'id' => sanitize($_POST['id'] ?? ''),
        'name' => sanitize($_POST['name'] ?? ''),
        'event_date' => sanitize($_POST['event_date'] ?? ''),
        'location' => sanitize($_POST['location'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
    ];

    $eventId = $formData['id'] !== '' ? (int) $formData['id'] : null;

    if ($formData['name'] === '') {
        $errors[] = 'Event name is required.';
    }
    if ($formData['event_date'] === '' || !validateDate($formData['event_date'])) {
        $errors[] = 'A valid event date is required.';
    }

    if (count($errors) === 0) {
        $payload = [
            'name' => $formData['name'],
            'event_date' => $formData['event_date'],
            'location' => $formData['location'],
            'description' => $formData['description'],
            'created_by' => $userId,
        ];

        if ($eventId) {
            $existing = getEvent($pdo, $eventId);
            if (!$existing || (!isAdmin() && (int) ($existing['created_by'] ?? 0) !== $userId)) {
                flash('error', 'You cannot modify this event.');
                redirect('events.php');
            }

            unset($payload['created_by']);
            updateEvent($pdo, $eventId, $payload);
            flash('success', 'Event updated successfully.');
        } else {
            createEvent($pdo, $payload);
            flash('success', 'Event added successfully.');
        }

        redirect('events.php');
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $existing = getEvent($pdo, $deleteId);
    if (!$existing || (!isAdmin() && (int) ($existing['created_by'] ?? 0) !== $userId)) {
        flash('error', 'You cannot delete this event.');
        redirect('events.php');
    }

    deleteEvent($pdo, $deleteId);
    flash('success', 'Event deleted successfully.');
    redirect('events.php');
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $event = getEvent($pdo, $editId);

    if (!$event || (!isAdmin() && (int) ($event['created_by'] ?? 0) !== $userId)) {
        flash('error', 'Event not found.');
        redirect('events.php');
    }

    $formData = [
        'id' => (string) $event['id'],
        'name' => $event['name'],
        'event_date' => $event['event_date'],
        'location' => $event['location'] ?? '',
        'description' => $event['description'] ?? '',
    ];
}

$events = getEvents($pdo, isAdmin() ? null : $userId);

$pageTitle = 'Events';
$activeNav = 'events';
include __DIR__ . '/templates/header.php';

?>

<div class="grid gap-6 md:gap-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 md:p-6 card-shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
            <div>
                <p class="text-base sm:text-lg font-semibold text-primary">Add or Update Event</p>
                <p class="text-xs text-slate-500">Keep your community informed about upcoming initiatives.</p>
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

        <form method="POST" class="mt-4 sm:mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']); ?>">
            <div class="md:col-span-2">
                <label class="block text-xs sm:text-sm font-medium text-slate-600">Event Name</label>
                <input type="text" name="name" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 sm:px-4 py-2 sm:py-3 text-sm focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['name']); ?>">
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-slate-600">Event Date</label>
                <input type="date" name="event_date" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 sm:px-4 py-2 sm:py-3 text-sm focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['event_date']); ?>">
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-slate-600">Location</label>
                <input type="text" name="location" class="mt-2 w-full rounded-lg border border-slate-200 px-3 sm:px-4 py-2 sm:py-3 text-sm focus:border-accent focus:ring-2 focus:ring-accent/40 transition" value="<?= htmlspecialchars($formData['location']); ?>">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs sm:text-sm font-medium text-slate-600">Description</label>
                <textarea name="description" rows="4" class="mt-2 w-full rounded-lg border border-slate-200 px-3 sm:px-4 py-2 sm:py-3 text-sm focus:border-accent focus:ring-2 focus:ring-accent/40 transition"><?= htmlspecialchars($formData['description']); ?></textarea>
            </div>
            <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 sm:gap-4 button-group">
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg hover:bg-secondary transition font-medium text-sm">Save Event</button>
                <button type="reset" class="inline-flex items-center justify-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition text-sm">Clear</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 card-shadow">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-200">
            <p class="text-base sm:text-lg font-semibold text-primary">Event Records</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Description</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (count($events) === 0) : ?>
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-sm text-slate-500">No events recorded yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($events as $event) : ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    <?= htmlspecialchars($event['name']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($event['event_date']))); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars($event['location'] ?: '—'); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= htmlspecialchars($event['description'] ? truncateText($event['description'], 80) : '—'); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="?edit=<?= (int) $event['id']; ?>" class="text-accent hover:text-primary transition">Edit</a>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <a href="?delete=<?= (int) $event['id']; ?>" class="text-red-500 hover:text-red-600 transition" onclick="return confirm('Delete this event?');">Delete</a>
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

