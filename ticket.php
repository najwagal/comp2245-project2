<?php
require __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$ticket = $id ? fetch_ticket($pdo, $id) : null;
if (!$ticket) {
    abort_page(404, 'That ticket does not exist.');
}
$notes = fetch_notes($pdo, (int) $ticket['id']);
$canDelete = user_can_delete($me, $ticket);
$flash = take_flash();

$pageTitle = '#' . $ticket['id'] . ' ' . $ticket['title'];
$activeNav = 'tickets';
require __DIR__ . '/includes/header.php';
?>
<div id="ticket-page" data-ticket-id="<?= (int) $ticket['id'] ?>" data-user-id="<?= (int) $me['id'] ?>" data-user-name="<?= e($me['name']) ?>">
  <a class="back" href="index.php">&larr; All tickets</a>
  <div class="page-head">
    <h1><span class="num">#<?= (int) $ticket['id'] ?></span> <?= e($ticket['title']) ?></h1>
    <div class="actions">
      <a class="btn btn-soft" href="ticket-edit.php?id=<?= (int) $ticket['id'] ?>">Edit</a>
      <?php if ($canDelete): ?><button type="button" class="btn btn-danger" id="delete-btn">Delete</button><?php endif; ?>
    </div>
  </div>
  <?php if ($flash): ?><p class="notice" role="status"><?= e($flash) ?></p><?php endif; ?>

  <?php if ($canDelete): ?>
  <div class="confirm" id="delete-confirm" hidden>
    <p><strong>Delete this ticket?</strong> Its notes will be deleted too. This can't be undone.</p>
    <form method="post" action="ticket-delete.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
      <button class="btn btn-danger">Yes, delete it</button>
      <button type="button" class="btn btn-soft" id="delete-cancel">Cancel</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="ticket-layout">
    <div class="ticket-main">
      <div class="card description"><?= e($ticket['description']) ?></div>

      <h2>Notes</h2>
      <div id="notes">
        <?php foreach ($notes as $n): ?>
          <article class="note">
            <p class="note-meta"><strong><?= e($n['author']) ?></strong> · <?= e(fmt_dt($n['created_at'])) ?></p>
            <p class="note-body"><?= e($n['body']) ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (!$notes): ?><p class="muted" id="notes-empty">No notes yet.</p><?php endif; ?>
      </div>

      <form class="card" id="note-form">
        <label for="note-body">Add a note</label>
        <textarea id="note-body" rows="4"></textarea>
        <p class="error" id="note-error" role="alert" hidden></p>
        <button class="btn btn-primary">Add note</button>
      </form>
    </div>

    <aside class="card side">
      <dl>
        <dt>Status</dt><dd><span id="status-pill" class="pill status-<?= e(slug($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></dd>
        <dt>Priority</dt><dd><span class="pill priority-<?= e(slug($ticket['priority'])) ?>"><?= e($ticket['priority']) ?></span></dd>
        <dt>Assigned to</dt><dd><strong id="assigned-name"><?= $ticket['assignee_name'] === null ? 'Nobody' : e($ticket['assignee_name']) ?></strong></dd>
        <dt>Created by</dt><dd><strong><?= e($ticket['creator_name']) ?></strong></dd>
        <dt>Created</dt><dd><strong><?= e(fmt_dt($ticket['created_at'])) ?></strong></dd>
        <dt>Last updated</dt><dd><strong id="updated-at"><?= e(fmt_dt($ticket['updated_at'])) ?></strong></dd>
      </dl>
      <label for="status-select">Change status</label>
      <select id="status-select">
        <?php foreach (STATUSES as $s): ?>
          <option value="<?= e($s) ?>"<?= $ticket['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-soft" id="assign-btn"<?= (int) $ticket['assigned_to'] === (int) $me['id'] ? ' hidden' : '' ?>>Assign to me</button>
      <p class="muted" id="action-msg" role="status" aria-live="polite"></p>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
