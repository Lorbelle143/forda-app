<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user has the admin role.
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to login page if the user is not logged in.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getBasePath() . 'login.php');
        exit;
    }
}

/**
 * Redirect to student dashboard if the user is not an admin.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . getBasePath() . 'dashboard.php');
        exit;
    }
}

/**
 * Get the base path relative to the current file location.
 * Works for files in subdirectories (admin/, student/).
 */
function getBasePath(): string {
    // __DIR__ is the includes/ directory; app root is one level up
    $appRoot    = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    $scriptDir  = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

    if ($appRoot === false || $scriptDir === false) {
        return '../'; // fallback
    }

    // Normalize separators
    $appRoot   = rtrim(str_replace('\\', '/', $appRoot), '/');
    $scriptDir = rtrim(str_replace('\\', '/', $scriptDir), '/');

    if ($scriptDir === $appRoot) {
        return ''; // same directory as app root
    }

    // Count extra depth beyond app root
    $relative = str_replace($appRoot, '', $scriptDir);
    $depth    = substr_count(trim($relative, '/'), '/') + 1;

    return str_repeat('../', $depth);
}

/**
 * Escape output to prevent XSS.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current user's ID from session.
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's name from session.
 */
function currentUserName(): string {
    return $_SESSION['name'] ?? 'User';
}

/**
 * Generate a CSRF token and store it in session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate the CSRF token from a POST request. Dies on failure.
 */
function verifyCsrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    if (empty($stored) || empty($submitted) || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('Security check failed. Please go back and try again.');
    }
}

/**
 * Simple login rate limiting — max 10 attempts per 15 minutes per IP.
 */
function checkLoginRateLimit(): bool {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    // Reset window after 15 minutes
    if (time() - $_SESSION[$key]['first'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] <= 10;
}

function resetLoginRateLimit(): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>
