<?php
// Shared by ticket-new.php and ticket-edit.php.
// Expects: $values, $errors, $users, $submitLabel, $cancelUrl
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<p class="error" id="' . $key . '-error">' . e($errors[$key]) . '</p>' : '';
}
function err_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' class="has-error" aria-invalid="true"' : '';
}
?>
<form class="card form-card" method="post" novalidate>
  <?= csrf_field() ?>
  <label for="title">Title</label>
  <input type="text" id="title" name="title" value="<?= e($values['title']) ?>"<?= err_class($errors, 'title') ?>>
  <?= field_error($errors, 'title') ?>

  <label for="description">Description</label>
  <textarea id="description" name="description" rows="6"<?= err_class($errors, 'description') ?>><?= e($values['description']) ?></textarea>
  <?= field_error($errors, 'description') ?>

  <div class="two-col">
    <div>
      <label for="priority">Priority</label>
      <select id="priority" name="priority"<?= err_class($errors, 'priority') ?>>
        <option value="">Choose...</option>
        <?php foreach (PRIORITIES as $p): ?>
          <option value="<?= e($p) ?>"<?= $values['priority'] === $p ? ' selected' : '' ?>><?= e($p) ?></option>
        <?php endforeach; ?>
      </select>
      <?= field_error($errors, 'priority') ?>
    </div>
    <div>
      <label for="assigned_to">Assign to</label>
      <select id="assigned_to" name="assigned_to"<?= err_class($errors, 'assigned_to') ?>>
        <option value="">Nobody yet</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"<?= (string) $values['assigned_to'] === (string) $u['id'] ? ' selected' : '' ?>><?= e($u['firstname'] . ' ' . $u['lastname']) ?></option>
        <?php endforeach; ?>
      </select>
      <?= field_error($errors, 'assigned_to') ?>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary"><?= e($submitLabel) ?></button>
    <a href="<?= e($cancelUrl) ?>">Cancel</a>
  </div>
</form>
