<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pageTitle = $pageTitle ?? 'Fix-It Desk';
$user = current_user();
$flash = take_flash();
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> — Fix-It Desk</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php">Fix-It Desk</a>
            <nav class="main-nav" aria-label="Primary navigation">
                <?php if ($user !== null): ?>
                    <a href="index.php" class="<?= in_array($currentScript, ['index.php', 'ticket.php', 'ticket-edit.php'], true) ? 'active' : '' ?>">Tickets</a>
                    <a href="ticket-new.php" class="<?= $currentScript === 'ticket-new.php' ? 'active' : '' ?>">New Ticket</a>
                    <?php if (is_admin()): ?>
                        <a href="users.php" class="<?= $currentScript === 'users.php' ? 'active' : '' ?>">Users</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="<?= $currentScript === 'login.php' ? 'active' : '' ?>">Log In</a>
                <?php endif; ?>
            </nav>
            <?php if ($user !== null): ?>
                <div class="user-bar">
                    <span class="user-meta">
                        <?= e($user['firstname'] . ' ' . $user['lastname']) ?>
                        <span class="role-badge"><?= e($user['role']) ?></span>
                    </span>
                    <a class="btn btn-secondary btn-sm" href="logout.php">Log Out</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="container page-main">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>" role="status">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>