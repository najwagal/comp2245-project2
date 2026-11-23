<?php
require __DIR__ . '/../includes/bootstrap.php';
$me = api_user();

$filter = $_GET['filter'] ?? 'all';
if (!is_string($filter) || !in_array($filter, ['all', 'open', 'mine'], true)) {
    json_out(['error' => 'Unknown filter.'], 400);
}

$rows = fetch_tickets($pdo, $filter, $me['id']);
$tickets = array_map(fn($t) => [
    'id'            => (int) $t['id'],
    'title'         => $t['title'],
    'priority'      => $t['priority'],
    'status'        => $t['status'],
    'creator_name'  => $t['creator_name'],
    'assignee_name' => $t['assignee_name'],
    'created_at'    => fmt_dt($t['created_at']),
], $rows);

json_out(['filter' => $filter, 'count' => count($tickets), 'tickets' => $tickets]);
