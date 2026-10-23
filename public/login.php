<?php
require_once __DIR__ . '/../src/helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = db()->prepare(
            'SELECT * FROM users WHERE username = ?'
        );

        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $row['id'];

            header('Location: index.php');
            exit;
        }

        $error = 'Invalid username or password.';
    } catch (PDOException $e) {
        error_log('Login database error: ' . $e->getMessage());
        $error = 'Unable to log in right now. Please try again later.';
    }
}

render_header('Log in');
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h4 mb-3">Log in</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="card card-body shadow-sm">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" class="form-control" autofocus required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>

            <button class="btn btn-primary">Log in</button>
            <p class="small mt-3 mb-0">
                No account? <a href="register.php">Register</a>.
            </p>
        </form>
    </div>
</div>
<?php render_footer(); ?>