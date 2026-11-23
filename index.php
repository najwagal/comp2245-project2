<?php
require __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$tickets = fetch_tickets($pdo, 'all', $me['id']);
$flash = take_flash();
$pageTitle = 'Tickets';
$activeNav = 'tickets';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>Tickets</h1>
  <a class="btn btn-primary" href="ticket-new.php">New ticket</a>
</div>
<?php if ($flash): ?><p class="notice" role="status"><?= e($flash) ?></p><?php endif; ?>

<div class="filters" role="group" aria-label="Filter tickets">
  <button type="button" class="chip active" data-filter="all" aria-pressed="true">All</button>
  <button type="button" class="chip" data-filter="open" aria-pressed="false">Open</button>
  <button type="button" class="chip" data-filter="mine" aria-pressed="false">My tickets</button>
</div>
<p class="muted" id="ticket-count" aria-live="polite"><?= count($tickets) ?> ticket<?= count($tickets) === 1 ? '' : 's' ?></p>
<p class="error" id="list-error" role="alert" hidden></p>

<table class="table" id="ticket-table">
  <thead>
    <tr><th>#</th><th>Title</th><th>Priority</th><th>Status</th><th>Created by</th><th>Assigned to</th><th>Created</th></tr>
  </thead>
  <tbody id="ticket-rows">
  <?php foreach ($tickets as $t): ?>
    <tr>
      <td data-label="#"><?= (int) $t['id'] ?></td>
      <td data-label="Title"><a href="ticket.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a></td>
      <td data-label="Priority"><span class="pill priority-<?= e(slug($t['priority'])) ?>"><?= e($t['priority']) ?></span></td>
      <td data-label="Status"><span class="pill status-<?= e(slug($t['status'])) ?>"><?= e($t['status']) ?></span></td>
      <td data-label="Created by"><?= e($t['creator_name']) ?></td>
      <td data-label="Assigned to"><?= $t['assignee_name'] === null ? '<span class="muted">Nobody</span>' : e($t['assignee_name']) ?></td>
      <td data-label="Created"><?= e(fmt_dt($t['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>

