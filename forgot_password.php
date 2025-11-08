<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_user_email'], $_SESSION['password_reset_user_name'], $_SESSION['password_reset_ready']);

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;
$formEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formEmail = sanitize($_POST['email'] ?? '');

    if ($formEmail === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (count($errors) === 0) {
        $success = true;
        $lookupUser = null;

        try {
            $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $formEmail]);
            $lookupUser = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Failed to locate user during password reset lookup: ' . $e->getMessage());
        }

        if ($lookupUser) {
            $_SESSION['password_reset_user_id'] = (int) $lookupUser['id'];
            $_SESSION['password_reset_user_email'] = $lookupUser['email'];
            $_SESSION['password_reset_user_name'] = $lookupUser['name'];
            $_SESSION['password_reset_ready'] = true;

            invalidatePasswordResetTokens($pdo, (int) $lookupUser['id']);

            redirect('reset_password.php');
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
        <p class="text-sm text-slate-500 mt-2">Enter the email associated with your DonationTracker account. If it matches an account, you’ll be guided straight to choose a new password—no emails involved.</p>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">We couldn’t start the reset</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success) : ?>
            <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-700 px-4 py-3 space-y-2">
                <p class="font-medium">If that email is recognised</p>
                <p class="text-sm">You’ll be redirected to set a new password right away. If nothing happens, double-check the address or contact an administrator.</p>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600">Email address</label>
                <input type="email" id="email" name="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="you@example.org" value="<?= htmlspecialchars($formEmail); ?>">
            </div>
            <button type="submit" class="w-full inline-flex justify-center bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Start reset</button>
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

