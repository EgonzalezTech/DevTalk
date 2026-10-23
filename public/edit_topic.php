<?php
require_once __DIR__ . '/../src/helpers.php';
require_login();

$id = filter_var(
    $_POST['id'] ?? $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$userId = (int) $_SESSION['user_id'];

if ($id === false || $id === null) {
    http_response_code(404);
    render_header('Not found');
    echo '<div class="alert alert-warning">Topic not found.</div>';
    render_footer();
    exit;
}

// Only load a topic owned by the logged-in user.
$stmt = db()->prepare(
    'SELECT * FROM topics WHERE id = ? AND user_id = ?'
);
$stmt->execute([$id, $userId]);
$topic = $stmt->fetch();

if (!$topic) {
    http_response_code(404);
    render_header('Not found');
    echo '<div class="alert alert-warning">Topic not found.</div>';
    render_footer();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = $_POST['body'] ?? '';
    $image = trim($_POST['image_url'] ?? '');

    if ($title === '' || trim($body) === '') {
        $error = 'Title and body are required.';
    } else {
        // Check ownership again when updating the topic.
        $upd = db()->prepare(
            'UPDATE topics
             SET title = ?, body = ?, image_url = ?,
                 updated_at = datetime("now")
             WHERE id = ? AND user_id = ?'
        );

        $upd->execute([
            $title,
            $body,
            $image,
            $id,
            $userId
        ]);

        header('Location: topic.php?id=' . $id);
        exit;
    }
}

render_header('Edit Topic');
?>

<h1 class="h4 mb-3">Edit topic</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="post" class="card card-body shadow-sm">
    <input type="hidden" name="id" value="<?= (int) $topic['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Title</label>
        <input
            name="title"
            class="form-control"
            value="<?= htmlspecialchars(
                        $topic['title'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            required>
    </div>

    <div class="mb-3">
        <label class="form-label">Body</label>
        <textarea
            name="body"
            class="form-control"
            rows="8"
            required><?= htmlspecialchars(
                            $topic['body'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Image URL</label>
        <input
            name="image_url"
            class="form-control"
            value="<?= htmlspecialchars(
                        $topic['image_url'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">
    </div>

    <div>
        <button class="btn btn-primary">Save changes</button>
    </div>
</form>

<?php render_footer(); ?>