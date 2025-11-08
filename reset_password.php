<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$resetUserId = isset($_SESSION['password_reset_user_id']) ? (int) $_SESSION['password_reset_user_id'] : null;
$resetUserEmail = isset($_SESSION['password_reset_user_email']) ? (string) $_SESSION['password_reset_user_email'] : null;
$resetUserName = isset($_SESSION['password_reset_user_name']) ? (string) $_SESSION['password_reset_user_name'] : null;
$resetReadyFlag = isset($_SESSION['password_reset_ready']) ? (bool) $_SESSION['password_reset_ready'] : false;

if (!$resetReadyFlag || $resetUserId === null || $resetUserId <= 0 || $resetUserEmail === null) {
    $resetUserId = null;
    $resetUserEmail = null;
    $resetUserName = null;
}

$errors = [];
$password = '';
$confirmPassword = '';
$resetUnavailable = $resetUserId === null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($resetUnavailable) {
        $errors[] = 'Your password reset session has expired. Please start over.';
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
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id');
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id' => $resetUserId,
            ]);

            invalidatePasswordResetTokens($pdo, (int) $resetUserId);

            $pdo->commit();
            unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_user_email'], $_SESSION['password_reset_user_name'], $_SESSION['password_reset_ready']);

            flash('success', 'Your password has been reset. You can sign in with the new password now.');
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Failed to reset password directly: ' . $e->getMessage());
            $errors[] = 'We could not reset your password right now. Please try again or contact support.';
        }
    }
}

$pageTitle = 'Reset Password';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200 p-10 md:p-12 card-shadow">
        <h1 class="text-3xl font-semibold text-primary">Choose a new password</h1>
        <p class="text-sm text-slate-500 mt-2">Create a strong password to secure your DonationTracker account. You arrived here directly after verifying your email—no email messages were sent.</p>

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

        <?php if ($resetUnavailable && $_SERVER['REQUEST_METHOD'] !== 'POST') : ?>
            <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-700 px-4 py-3 space-y-2">
                <p class="font-medium">This reset session can’t be used</p>
                <p class="text-sm">The password reset session may have expired or has already been completed. Start the process again to continue.</p>
                <a href="<?= app_url('forgot_password.php'); ?>" class="inline-flex items-center gap-2 text-sm text-accent hover:text-primary transition font-medium">
                    Start a new reset
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$resetUnavailable) : ?>
            <form method="POST" class="mt-8 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-600">Account</label>
                    <p class="mt-2 text-sm text-slate-500"><?= htmlspecialchars($resetUserEmail ?? ''); ?></p>
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

