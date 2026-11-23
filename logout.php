<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?: 'Lax',
        ]
    );
}

session_destroy();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

set_flash('success', 'You have been logged out.');
header('Location: login.php');
exit;