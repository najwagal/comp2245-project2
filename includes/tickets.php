<?php
declare(strict_types=1);

const PRIORITIES = ['Low', 'Medium', 'High'];
const STATUSES   = ['Open', 'In progress', 'Closed'];

const TICKET_SELECT = "SELECT t.id, t.title, t.description, t.priority, t.status, t.created_by, t.assigned_to,
        t.created_at, t.updated_at,
        CONCAT(c.firstname, ' ', c.lastname) AS creator_name,
        CONCAT(a.firstname, ' ', a.lastname) AS assignee_name
    FROM tickets t
    JOIN users c      ON c.id = t.created_by
    LEFT JOIN users a ON a.id = t.assigned_to";

function fetch_tickets(PDO $pdo, string $filter, int $userId): array
{
    $sql = TICKET_SELECT;
    $params = [];
    if ($filter === 'open') {
        $sql .= " WHERE t.status <> 'Closed'";
    } elseif ($filter === 'mine') {
        $sql .= ' WHERE t.assigned_to = ?';
        $params[] = $userId;
    }
    $sql .= ' ORDER BY t.created_at DESC, t.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_ticket(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(TICKET_SELECT . ' WHERE t.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function fetch_notes(PDO $pdo, int $ticketId): array
{
    $stmt = $pdo->prepare("SELECT n.id, n.body, n.created_at, CONCAT(u.firstname, ' ', u.lastname) AS author
        FROM notes n JOIN users u ON u.id = n.created_by
        WHERE n.ticket_id = ? ORDER BY n.created_at, n.id");
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

function fetch_users(PDO $pdo): array
{
    return $pdo->query("SELECT id, firstname, lastname, email, role, created_at FROM users ORDER BY firstname, lastname")->fetchAll();
}

function user_can_delete(array $me, array $ticket): bool
{
    return $me['role'] === 'admin' || (int) $ticket['created_by'] === (int) $me['id'];
}

/** Validates the ticket form. Returns [values, errors, assigneeIdOrNull]. */
function validate_ticket(PDO $pdo): array
{
    $v = [
        'title'       => trim(post_str('title')),
        'description' => trim(post_str('description')),
        'priority'    => post_str('priority'),
        'assigned_to' => post_str('assigned_to'),
    ];
    $errors = [];
    if ($v['title'] === '') {
        $errors['title'] = 'Enter a title.';
    } elseif (text_len($v['title']) > 150) {
        $errors['title'] = 'The title can be at most 150 characters.';
    }
    if ($v['description'] === '') {
        $errors['description'] = 'Describe the problem.';
    }
    if (!in_array($v['priority'], PRIORITIES, true)) {
        $errors['priority'] = 'Choose a priority.';
    }
    $assignee = null;
    if ($v['assigned_to'] !== '') {
        $found = false;
        if (ctype_digit($v['assigned_to'])) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([(int) $v['assigned_to']]);
            $found = (bool) $stmt->fetch();
        }
        if ($found) {
            $assignee = (int) $v['assigned_to'];
        } else {
            $errors['assigned_to'] = 'Choose someone from the list.';
        }
    }
    return [$v, $errors, $assignee];
}
