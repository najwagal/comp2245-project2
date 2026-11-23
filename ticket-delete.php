<?php
require __DIR__ . '/includes/bootstrap.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');           // a link (GET) never deletes anything
}
require_csrf();

$id = filter_var(post_str('id'), FILTER_VALIDATE_INT);
$ticket = $id ? fetch_ticket($pdo, $id) : null;
if (!$ticket) {
    abort_page(404, 'That ticket does not exist.');
}
// Checked again on the server: only the admin or the ticket's creator.
if (!user_can_delete($me, $ticket)) {
    abort_page(403, 'You can only delete tickets you created.');
}

$stmt = $pdo->prepare('DELETE FROM tickets WHERE id = ?');   // notes go too (ON DELETE CASCADE)
$stmt->execute([$ticket['id']]);
flash('Ticket #' . $ticket['id'] . ' was deleted.');
redirect('index.php');
