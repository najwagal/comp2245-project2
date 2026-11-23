<?php
require __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$ticket = $id ? fetch_ticket($pdo, $id) : null;
if (!$ticket) {
    abort_page(404, 'That ticket does not exist.');
}

$values = [
    'title'       => $ticket['title'],
    'description' => $ticket['description'],
    'priority'    => $ticket['priority'],
    'assigned_to' => $ticket['assigned_to'] === null ? '' : (string) $ticket['assigned_to'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$values, $errors, $assignee] = validate_ticket($pdo);
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE tickets SET title = ?, description = ?, priority = ?, assigned_to = ? WHERE id = ?');
        $stmt->execute([$values['title'], $values['description'], $values['priority'], $assignee, $ticket['id']]);
        flash('Ticket updated.');
        redirect('ticket.php?id=' . (int) $ticket['id']);
    }
}

$users = fetch_users($pdo);
$pageTitle = 'Edit ticket #' . $ticket['id'];
$activeNav = 'tickets';
$submitLabel = 'Save changes';
$cancelUrl = 'ticket.php?id=' . (int) $ticket['id'];
require __DIR__ . '/includes/header.php';
?>
<h1>Edit ticket #<?= (int) $ticket['id'] ?></h1>
<?php require __DIR__ . '/includes/ticket_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
