<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;
$formEmail = '';
$resetLink = null;
$expiresFormatted = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formEmail = sanitize($_POST['email'] ?? '');

    if ($formEmail === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (count($errors) === 0) {
        $resetDetails = createPasswordResetToken($pdo, $formEmail);
        $success = true;

        if ($resetDetails !== null) {
            $resetLink = app_url('reset_password.php?token=' . urlencode($resetDetails['token']));

            $expiresAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $resetDetails['expires_at']);
            if ($expiresAt instanceof DateTimeImmutable) {
                $expiresFormatted = $expiresAt->format('F j, Y g:i A');
            }
        }

        // Always clear the form field so we don't reveal whether the email exists.
        $formEmail = '';
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200 p-10 md:p-12 card-shadow">
        <h1 class="text-3xl font-semibold text-primary">Reset your password</h1>
        <p class="text-sm text-slate-500 mt-2">Enter the email associated with your DonationTracker account and we’ll send you reset instructions.</p>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">We couldn’t send the reset link</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success) : ?>
            <div class="mt-6 rounded-lg border border-green-200 bg-green-50 text-green-700 px-4 py-3 space-y-2">
                <p class="font-medium">Check your inbox</p>
                <p class="text-sm">If an account with that email exists, you’ll receive a message with a link to reset your password shortly.</p>
                <?php if ($resetLink !== null) : ?>
                    <div class="text-xs bg-white/80 border border-green-200 rounded-lg px-3 py-2 text-green-800">
                        <p class="font-medium mb-1">Testing shortcut</p>
                        <a href="<?= htmlspecialchars($resetLink); ?>" class="break-all text-accent hover:text-primary transition"><?= htmlspecialchars($resetLink); ?></a>
                        <?php if ($expiresFormatted !== null) : ?>
                            <p class="mt-1">This link expires on <?= htmlspecialchars($expiresFormatted); ?>.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600">Email address</label>
                <input type="email" id="email" name="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="you@example.org" value="<?= htmlspecialchars($formEmail); ?>">
            </div>
            <button type="submit" class="w-full inline-flex justify-center bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Send reset link</button>
            <div class="text-xs text-slate-500 text-center space-y-2">
                <p>
                    Remembered your password?
                    <a href="<?= app_url('login.php'); ?>" class="text-accent hover:text-primary transition font-medium">Back to sign in</a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';

?>

