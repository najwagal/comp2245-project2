<?php
require __DIR__ . '/../includes/bootstrap.php';
$me = api_user();
api_post_only();

$in = json_body();
$ticketId = filter_var($in['ticket_id'] ?? null, FILTER_VALIDATE_INT);
$body = isset($in['body']) && is_string($in['body']) ? trim($in['body']) : '';

if (!$ticketId || !fetch_ticket($pdo, $ticketId)) {
    json_out(['error' => 'That ticket does not exist.'], 404);
}
if ($body === '') {
    json_out(['error' => 'Write something before adding a note.'], 422);
}
if (text_len($body) > 5000) {
    json_out(['error' => 'Notes can be at most 5000 characters.'], 422);
}

$stmt = $pdo->prepare('INSERT INTO notes (ticket_id, body, created_by) VALUES (?, ?, ?)');
$stmt->execute([$ticketId, $body, $me['id']]);
$noteId = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT created_at FROM notes WHERE id = ?');
$stmt->execute([$noteId]);

json_out(['note' => [
    'id'         => $noteId,
    'author'     => $me['name'],
    'body'       => $body,
    'created_at' => fmt_dt($stmt->fetchColumn()),
]], 201);
