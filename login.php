<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$emailInput = '';

if (!empty($_SESSION['flash_error']) && is_string($_SESSION['flash_error'])) {
    $errors[] = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = trim((string) ($_POST['email'] ?? ''));
    $passwordInput = (string) ($_POST['password'] ?? '');
    $csrfInput = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token(is_string($csrfInput) ? $csrfInput : null)) {
        $errors[] = 'Your session token expired or was invalid. Please try again.';
    }

    if ($emailInput === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($passwordInput === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare(
                'SELECT id, firstname, lastname, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute(['email' => strtolower($emailInput)]);
            $userRow = $stmt->fetch();

            if ($userRow && password_verify($passwordInput, (string) $userRow['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $userRow['id'],
                    'firstname' => (string) $userRow['firstname'],
                    'lastname' => (string) $userRow['lastname'],
                    'email' => (string) $userRow['email'],
                    'role' => (string) $userRow['role'],
                ];

                set_flash('success', 'Welcome back, ' . $_SESSION['user']['firstname'] . '!');
                header('Location: index.php');
                exit;
            }

            $errors[] = 'Invalid email or password.';
        } catch (PDOException $e) {
            $errors[] = 'Unable to sign in right now. Please try again later.';
        }
    }
}

$pageTitle = 'Log In';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-card">
    <h1>Log In to Fix-It Desk</h1>
    <p class="text-muted">Sign in with your help desk account to view and manage tickets.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($emailInput) ?>"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary">Log In</button>
    </form>

    <div class="demo-accounts">
        <p class="demo-title">Seeded Test Accounts (password: <code>password123</code>)</p>
        <ul>
            <li><strong>Admin:</strong> <code>admin@fixit.test</code></li>
            <li><strong>Member:</strong> <code>keisha@fixit.test</code></li>
            <li><strong>Member:</strong> <code>marcus@fixit.test</code></li>
        </ul>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>