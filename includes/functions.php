<?php

declare(strict_types=1);

if (!defined('APP_BASE_PATH')) {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $appRoot = dirname(__DIR__);

    $documentRootReal = $documentRoot !== '' ? realpath($documentRoot) : false;
    $appRootReal = realpath($appRoot);

    if ($documentRootReal !== false) {
        $documentRoot = $documentRootReal;
    }

    if ($appRootReal !== false) {
        $appRoot = $appRootReal;
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $appRoot = rtrim(str_replace('\\', '/', $appRoot), '/');

    $basePath = '';
    if ($documentRoot !== '' && str_starts_with($appRoot, $documentRoot)) {
        $basePath = substr($appRoot, strlen($documentRoot));
    }

    $basePath = '/' . ltrim($basePath, '/');

    if ($basePath === '/' || $basePath === '') {
        $basePath = '';
    }

    define('APP_BASE_PATH', $basePath);
}

if (!defined('REMEMBER_ME_COOKIE')) {
    define('REMEMBER_ME_COOKIE', 'dt_remember');
}

if (!defined('REMEMBER_ME_TTL_DAYS')) {
    define('REMEMBER_ME_TTL_DAYS', 14);
}

function app_url(string $path = ''): string
{
    $base = APP_BASE_PATH ?? '';
    $path = ltrim($path, '/');

    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . $path;
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    if (!preg_match('#^(?:[a-z]+:)?//#i', $path)) {
        if (str_starts_with($path, '/')) {
            if ((APP_BASE_PATH ?? '') !== '') {
                $normalizedBase = rtrim(APP_BASE_PATH, '/') . '/';
                if (!str_starts_with($path, $normalizedBase)) {
                    $path = rtrim(APP_BASE_PATH, '/') . $path;
                }
            }
        } else {
            $path = app_url($path);
        }
    }

    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message === null) {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $msg;
    }

    $_SESSION['flash'][$key] = $message;

    return null;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

function formatCurrency(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function truncateText(string $text, int $width, string $suffix = '…'): string
{
    if ($text === '') {
        return $text;
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, $suffix, 'UTF-8');
    }

    if (strlen($text) <= $width) {
        return $text;
    }

    $trimmed = substr($text, 0, max(0, $width - strlen($suffix)));

    return $trimmed . $suffix;
}

function rememberCookiePath(): string
{
    $base = APP_BASE_PATH ?? '';

    if ($base === '' || $base === '/') {
        return '/';
    }

    return rtrim($base, '/') . '/';
}

function rememberCookieOptions(int $expiresTimestamp): array
{
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    return [
        'expires' => $expiresTimestamp,
        'path' => rememberCookiePath(),
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

function forgetRememberCookie(): void
{
    if (!isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        return;
    }

    setcookie(REMEMBER_ME_COOKIE, '', rememberCookieOptions(time() - 3600));
    unset($_COOKIE[REMEMBER_ME_COOKIE]);
}

function deleteRememberTokensForUser(int $userId): void
{
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    } catch (PDOException $e) {
        error_log('Failed to delete remember tokens for user ' . $userId . ': ' . $e->getMessage());
    }
}

function rememberUser(array $user, int $ttlDays = REMEMBER_ME_TTL_DAYS): void
{
    if (!isset($user['id'])) {
        return;
    }

    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return;
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log('Failed to generate remember token: ' . $e->getMessage());
        return;
    }

    $userId = (int) $user['id'];
    $expiresAt = (new DateTimeImmutable('+' . max(1, $ttlDays) . ' days'));
    $tokenHash = hash('sha256', $token);

    try {
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        $pdo->prepare('INSERT INTO user_remember_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')
            ->execute([
                ':user_id' => $userId,
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
    } catch (PDOException $e) {
        error_log('Failed to persist remember token: ' . $e->getMessage());
        return;
    }

    setcookie(
        REMEMBER_ME_COOKIE,
        $userId . ':' . $token,
        rememberCookieOptions($expiresAt->getTimestamp())
    );

    $_COOKIE[REMEMBER_ME_COOKIE] = $userId . ':' . $token;
}

function restoreUserFromRememberCookie(): ?array
{
    if (!isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        return null;
    }

    $cookieValue = $_COOKIE[REMEMBER_ME_COOKIE];
    $parts = explode(':', $cookieValue, 2);

    if (count($parts) !== 2) {
        forgetRememberCookie();
        return null;
    }

    [$userIdRaw, $token] = $parts;
    if ($token === '' || !ctype_digit($userIdRaw)) {
        forgetRememberCookie();
        return null;
    }

    $userId = (int) $userIdRaw;
    $tokenHash = hash('sha256', $token);

    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.role, t.expires_at 
             FROM user_remember_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.user_id = :user_id AND t.token_hash = :token_hash
             LIMIT 1'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
        ]);
        $record = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Failed to restore remember token: ' . $e->getMessage());
        forgetRememberCookie();
        return null;
    }

    if (!$record) {
        deleteRememberTokensForUser($userId);
        forgetRememberCookie();
        return null;
    }

    $expiresAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $record['expires_at']);
    $now = new DateTimeImmutable('now');

    if (!$expiresAt || $expiresAt <= $now) {
        deleteRememberTokensForUser($userId);
        forgetRememberCookie();
        return null;
    }

    deleteRememberTokensForUser($userId);

    $user = [
        'id' => (int) $record['id'],
        'name' => $record['name'],
        'email' => $record['email'],
        'role' => $record['role'] ?? 'user',
    ];

    rememberUser($user);

    return $user;
}

function clearRememberedUser(?int $userId = null): void
{
    if ($userId !== null) {
        deleteRememberTokensForUser($userId);
    }

    forgetRememberCookie();
}

function currentUser(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $user = restoreUserFromRememberCookie();
    if ($user !== null) {
        $_SESSION['user'] = $user;
    }

    return $user;
}

function currentUserId(): ?int
{
    $user = currentUser();

    return $user ? (int) $user['id'] : null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function isAdmin(): bool
{
    $user = currentUser();

    return $user !== null && ($user['role'] ?? 'user') === 'admin';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        flash('error', 'You do not have permission to access that area.');
        redirect('dashboard.php');
    }
}

function createPasswordResetToken(PDO $pdo, string $email, int $ttlMinutes = 60): ?array
{
    try {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Failed to look up user for password reset: ' . $e->getMessage());
        return null;
    }

    if (!$user) {
        return null;
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

    try {
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute([
            ':user_id' => $user['id'],
        ]);

        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')
            ->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt,
            ]);
    } catch (PDOException $e) {
        error_log('Failed to store password reset token: ' . $e->getMessage());
        return null;
    }

    return [
        'token' => $token,
        'expires_at' => $expiresAt,
        'user' => $user,
    ];
}

function findValidPasswordReset(PDO $pdo, string $token): ?array
{
    $token = trim($token);

    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare(
            'SELECT pr.*, u.email, u.name 
             FROM password_resets pr 
             INNER JOIN users u ON pr.user_id = u.id 
             WHERE pr.token_hash = :token_hash 
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $reset = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Failed to locate password reset token: ' . $e->getMessage());
        return null;
    }

    if (!$reset) {
        return null;
    }

    $now = new DateTimeImmutable('now');
    $expiresAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $reset['expires_at']);

    if (!$expiresAt || $expiresAt < $now) {
        $pdo->prepare('DELETE FROM password_resets WHERE id = :id')->execute([':id' => $reset['id']]);
        return null;
    }

    return $reset;
}

function resetPasswordUsingToken(PDO $pdo, string $token, string $newPassword): bool
{
    $reset = findValidPasswordReset($pdo, $token);

    if (!$reset) {
        return false;
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id')->execute([
            ':password_hash' => $passwordHash,
            ':user_id' => $reset['user_id'],
        ]);

        $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute([
            ':user_id' => $reset['user_id'],
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Failed to reset password using token: ' . $e->getMessage());
        return false;
    }

    return true;
}

function invalidatePasswordResetTokens(PDO $pdo, int $userId): void
{
    try {
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute([
            ':user_id' => $userId,
        ]);
    } catch (PDOException $e) {
        error_log('Failed to invalidate password reset tokens: ' . $e->getMessage());
    }
}

