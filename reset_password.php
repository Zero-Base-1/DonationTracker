<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string) ($_POST['token'] ?? ''));
} else {
    $token = trim((string) ($_GET['token'] ?? ''));
}

$resetRecord = $token !== '' ? findValidPasswordReset($pdo, $token) : null;

$errors = [];
$password = '';
$confirmPassword = '';
$tokenInvalid = $token === '' || $resetRecord === null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($tokenInvalid) {
        $errors[] = 'That reset link is invalid or has expired. Please request a new one.';
    }

    if ($password === '') {
        $errors[] = 'Please enter a new password.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please re-enter your new password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (count($errors) === 0) {
        if (resetPasswordUsingToken($pdo, $token, $password)) {
            flash('success', 'Your password has been reset. You can sign in with the new password now.');
            redirect('login.php');
        }

        $errors[] = 'We could not reset your password. The link may have expired. Please request a new one.';
        $resetRecord = null;
        $tokenInvalid = true;
    }
}

$pageTitle = 'Reset Password';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200 p-10 md:p-12 card-shadow">
        <h1 class="text-3xl font-semibold text-primary">Choose a new password</h1>
        <p class="text-sm text-slate-500 mt-2">Create a strong password to secure your DonationTracker account.</p>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">We couldn’t reset your password</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($tokenInvalid && $_SERVER['REQUEST_METHOD'] !== 'POST') : ?>
            <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-700 px-4 py-3 space-y-2">
                <p class="font-medium">This reset link can’t be used</p>
                <p class="text-sm">It may have expired or already been used. Request a new password reset to continue.</p>
                <a href="<?= app_url('forgot_password.php'); ?>" class="inline-flex items-center gap-2 text-sm text-accent hover:text-primary transition font-medium">
                    Request a new reset link
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$tokenInvalid) : ?>
            <form method="POST" class="mt-8 space-y-6">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
                <div>
                    <label class="block text-sm font-medium text-slate-600">Account</label>
                    <p class="mt-2 text-sm text-slate-500"><?= htmlspecialchars($resetRecord['email']); ?></p>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600">New password</label>
                    <input type="password" id="password" name="password" required minlength="8" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="At least 8 characters" value="">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-600">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="Re-enter your new password" value="">
                </div>
                <button type="submit" class="w-full inline-flex justify-center bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Update password</button>
                <div class="text-xs text-slate-500 text-center space-y-2">
                    <p>
                        Remembered your password?
                        <a href="<?= app_url('login.php'); ?>" class="text-accent hover:text-primary transition font-medium">Back to sign in</a>
                    </p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';

?>

