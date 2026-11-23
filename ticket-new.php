<?php
require __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$values = ['title' => '', 'description' => '', 'priority' => '', 'assigned_to' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$values, $errors, $assignee] = validate_ticket($pdo);
    if (!$errors) {
        // created_by comes from the session, never from the form.
        $stmt = $pdo->prepare('INSERT INTO tickets (title, description, priority, created_by, assigned_to) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$values['title'], $values['description'], $values['priority'], $me['id'], $assignee]);
        flash('Ticket created.');
        redirect('ticket.php?id=' . (int) $pdo->lastInsertId());
    }
}

$users = fetch_users($pdo);
$pageTitle = 'New ticket';
$activeNav = 'new';
$submitLabel = 'Create ticket';
$cancelUrl = 'index.php';
require __DIR__ . '/includes/header.php';
?>
<h1>New ticket</h1>
<?php require __DIR__ . '/includes/ticket_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
