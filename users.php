<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$errors = [];
$firstname = '';
$lastname = '';
$email = '';
$role = 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim((string) ($_POST['firstname'] ?? ''));
    $lastname = trim((string) ($_POST['lastname'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $roleInput = (string) ($_POST['role'] ?? 'member');
    $role = in_array($roleInput, ['admin', 'member'], true) ? $roleInput : 'member';
    $csrfInput = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token(is_string($csrfInput) ? $csrfInput : null)) {
        $errors[] = 'Your session token expired or was invalid. Please try again.';
    }

    if ($firstname === '') {
        $errors[] = 'First name is required.';
    } elseif (text_len($firstname) > 50) {
        $errors[] = 'First name must be 50 characters or fewer.';
    }

    if ($lastname === '') {
        $errors[] = 'Last name is required.';
    } elseif (text_len($lastname) > 50) {
        $errors[] = 'Last name must be 50 characters or fewer.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || text_len($email) > 255) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (!in_array($roleInput, ['admin', 'member'], true)) {
        $errors[] = 'Please select a valid role.';
    }

    if (empty($errors)) {
        try {
            $pdo = get_db();

            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch()) {
                $errors[] = 'A user with that email address already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (firstname, lastname, email, password, role)
                     VALUES (:firstname, :lastname, :email, :password, :role)'
                );
                $insertStmt->execute([
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => $role,
                ]);

                set_flash('success', 'User account created for ' . $firstname . ' ' . $lastname . '.');
                header('Location: users.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to create user right now. Please try again later.';
        }
    }
}

$users = [];
try {
    $pdo = get_db();
    $listStmt = $pdo->query(
        'SELECT id, firstname, lastname, email, role, created_at
         FROM users
         ORDER BY created_at DESC, id DESC'
    );
    $users = $listStmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Unable to load users list.';
}

$pageTitle = 'Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Users</h1>
        <p class="text-muted">Manage help desk accounts and roles.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid-two-col">
    <section class="card">
        <h2>Add New User</h2>
        <form method="post" action="users.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="firstname">First Name</label>
                <input
                    type="text"
                    id="firstname"
                    name="firstname"
                    maxlength="50"
                    value="<?= e($firstname) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input
                    type="text"
                    id="lastname"
                    name="lastname"
                    maxlength="50"
                    value="<?= e($lastname) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="255"
                    value="<?= e($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password (min 8 characters)</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Member</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create User</button>
        </form>
    </section>

    <section class="card">
        <h2>Existing Users (<?= count($users) ?>)</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" class="text-muted">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= e($u['firstname'] . ' ' . $u['lastname']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="role-badge"><?= e($u['role']) ?></span></td>
                                <td><?= e($u['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>