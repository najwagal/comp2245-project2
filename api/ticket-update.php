<?php
require __DIR__ . '/../includes/bootstrap.php';
$me = api_user();
api_post_only();

$in = json_body();
$ticketId = filter_var($in['ticket_id'] ?? null, FILTER_VALIDATE_INT);
$action = $in['action'] ?? '';

if (!$ticketId || !fetch_ticket($pdo, $ticketId)) {
    json_out(['error' => 'That ticket does not exist.'], 404);
}

if ($action === 'status') {
    $status = $in['status'] ?? '';
    if (!is_string($status) || !in_array($status, STATUSES, true)) {
        json_out(['error' => 'Choose a valid status.'], 422);
    }
    $stmt = $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?');
    $stmt->execute([$status, $ticketId]);
} elseif ($action === 'assign') {
    $stmt = $pdo->prepare('UPDATE tickets SET assigned_to = ? WHERE id = ?');
    $stmt->execute([$me['id'], $ticketId]);
} else {
    json_out(['error' => 'Unknown action.'], 400);
}

$t = fetch_ticket($pdo, $ticketId);
json_out(['ticket' => [
    'id'            => (int) $t['id'],
    'status'        => $t['status'],
    'assigned_to'   => $t['assigned_to'] === null ? null : (int) $t['assigned_to'],
    'assignee_name' => $t['assignee_name'],
    'updated_at'    => fmt_dt($t['updated_at']),
]]);
