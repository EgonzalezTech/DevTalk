<?php
require_once __DIR__ . '/../src/helpers.php';

$q = trim($_GET['q'] ?? '');

$results = [];
$sqlError = '';

if ($q !== '') {
    try {
        $sql = "SELECT id, title, body
                FROM topics
                WHERE title LIKE ? OR body LIKE ?
                ORDER BY created_at DESC";

        $stmt = db()->prepare($sql);

        $searchTerm = '%' . $q . '%';

        $stmt->execute([
            $searchTerm,
            $searchTerm
        ]);

        $results = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Search database error: ' . $e->getMessage());
        $sqlError = 'Search is temporarily unavailable. Please try again.';
    }
}

render_header('Search');
?>

<h1 class="h4 mb-3">Search</h1>

<form method="get" class="mb-4">
    <div class="input-group">
        <input
            name="q"
            class="form-control"
            value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Search topics…">
        <button class="btn btn-primary">Search</button>
    </div>
</form>

<?php if ($q !== ''): ?>
    <p class="text-body-secondary">
        Results for:
        <?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if ($sqlError): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($sqlError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($q !== '' && !$results && !$sqlError): ?>
    <p class="text-body-secondary">No matches found.</p>
<?php endif; ?>

<div class="vstack gap-3">
    <?php foreach ($results as $r): ?>
        <div class="card">
            <div class="card-body">

                <h2 class="h6 mb-1">
                    <a href="topic.php?id=<?= (int) $r['id'] ?>">
                        <?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </h2>

                <p class="small text-body-secondary mb-0">
                    <?= htmlspecialchars(
                        mb_substr(strip_tags($r['body']), 0, 160),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php render_footer(); ?>