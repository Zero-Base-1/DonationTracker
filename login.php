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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            redirect('/DonationTracker/dashboard.php');
        }

        $errors[] = 'Invalid credentials. Please try again.';
    }
}

$pageTitle = 'Login';
include __DIR__ . '/templates/header.php';

?>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200 p-10 md:p-12 card-shadow">
        <h1 class="text-3xl font-semibold text-primary">Welcome back</h1>
        <p class="text-sm text-slate-500 mt-2">Sign in to access the DonationTracker dashboard.</p>

        <?php if (count($errors) > 0) : ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <p class="font-medium">We couldn’t sign you in</p>
                <ul class="list-disc text-sm pl-5 mt-2 space-y-1">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600">Email address</label>
                <input type="email" id="email" name="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="you@example.org" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div>
                <div class="flex items-center justify-between text-sm">
                    <label for="password" class="font-medium text-slate-600">Password</label>
                    <a href="#" class="text-accent hover:text-primary transition">Forgot password?</a>
                </div>
                <input type="password" id="password" name="password" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 focus:border-accent focus:ring-2 focus:ring-accent/40 transition" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full inline-flex justify-center bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Sign In</button>
            <div class="text-xs text-slate-500 text-center space-y-2">
                <p>Default admin: admin@donationtracker.local / changeme123</p>
                <p>
                    Don’t have an account?
                    <a href="/DonationTracker/signin.php" class="text-accent hover:text-primary transition font-medium">Create one</a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';

