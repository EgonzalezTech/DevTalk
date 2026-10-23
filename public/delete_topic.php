
<?php
require_once __DIR__ . '/../src/helpers.php';
require_login();

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

// Verify CSRF token.
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('Invalid topic ID.');
}

// Only delete topics owned by the logged-in user.
$stmt = db()->prepare(
    'DELETE FROM topics WHERE id = ? AND user_id = ?'
);

$stmt->execute([
    $id,
    (int) $_SESSION['user_id']
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit('Topic not found.');
}

header('Location: index.php');
exit;
