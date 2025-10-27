<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    redirect('/DonationTracker/dashboard.php');
}

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($formData['name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if ($formData['email'] === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (count($errors) === 0) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute([':email' => $formData['email']]);

        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'An account with this email already exists. Please sign in instead.';
        }
    }

    if (count($errors) === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
        $stmt->execute([
            ':name' => $formData['name'],
            ':email' => $formData['email'],
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => 'user',
        ]);

        flash('success', 'Account created! Please sign in to continue.');
        redirect('/DonationTracker/login.php');
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200 p-10 md:p-12 card-shadow">
        <h1 class="text-3xl font-semibold text-primary">Create your DonationTracker account</h1>
        <p class="text-sm text-slate-500 mt-2">Set up your profile to start logging donations and events.</p>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">We couldn’t create your account</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-600">Full name</label>
                <input type="text" id="name" name="name" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="Alex Rivers" value="<?= htmlspecialchars($formData['name']); ?>">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600">Email address</label>
                <input type="email" id="email" name="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="you@example.org" value="<?= htmlspecialchars($formData['email']); ?>">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-600">Password</label>
                <input type="password" id="password" name="password" required minlength="8" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="At least 8 characters">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-slate-600">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="Re-enter your password">
            </div>
            <button type="submit" class="w-full inline-flex justify-center bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Create Account</button>
            <div class="text-xs text-slate-500 text-center">
                <p>
                    Already have an account?
                    <a href="/DonationTracker/login.php" class="text-accent hover:text-primary transition font-medium">Sign in</a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';


