<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Missing config.php. Copy config.example.php to config.php and configure database settings.');
}

require_once $configPath;

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $dbHost, $dbName, $dbUser, $dbPass;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_user(): ?array
{
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin()) {
        abort_page(403, 'Access denied: administrator privileges required.');
    }

    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------
// Shared helpers used by the ticket pages and the JSON API
// ---------------------------------------------------------------------

function flash(string $message, string $type = 'success'): void
{
    set_flash($type, $message);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function post_str(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function text_len(string $value): int
{
    // Works even if the mbstring extension is not enabled.
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : (int) preg_match_all('/./su', $value);
}

function slug(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
}

function fmt_dt(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $time = strtotime($value);
    return $time === false ? $value : date('j M Y, g:i a', $time);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        abort_page(419, 'Your session token expired or was invalid. Go back, reload the page and try again.');
    }
}

function abort_page(int $status, string $message): never
{
    http_response_code($status);
    $pageTitle = 'Error';
    require __DIR__ . '/header.php';
    echo '<div class="card"><h1>' . e((string) $status) . '</h1><p>' . e($message) . '</p>'
        . '<p><a href="index.php">Back to tickets</a></p></div>';
    require __DIR__ . '/footer.php';
    exit;
}

// --- JSON API helpers (api/*.php) ------------------------------------

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_user(): array
{
    $user = current_user();
    if ($user === null) {
        json_out(['error' => 'Your session has ended. Please log in again.'], 401);
    }
    return $user;
}

function api_post_only(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        json_out(['error' => 'Use POST for this request.'], 405);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        json_out(['error' => 'Your session token expired. Reload the page and try again.'], 403);
    }
}

function json_body(): array
{
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

// --- Database handle + ticket helpers --------------------------------

try {
    $pdo = get_db();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Could not connect to the database. Check config.php and that schema.sql has been imported.');
}

require_once __DIR__ . '/tickets.php';
