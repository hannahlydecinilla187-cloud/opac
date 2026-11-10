<?php
declare(strict_types=1);

/**
 * OPAC — book detail page.  GET /book/{id}
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$stmt = $id
    ? db()->prepare('SELECT * FROM books WHERE id = ? LIMIT 1')
    : null;
if ($stmt) {
    $stmt->execute([$id]);
    $book = $stmt->fetch();
} else {
    $book = false;
}

if (!$book) {
    http_response_code(404);
    layout_header('Book not found — TAGOLOAN COMMUNITY COLLEGE Library OPAC', ['active' => 'catalog']);
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <i class="bi bi-book-x display-1 text-secondary"></i>
                <h1 class="h3 mt-3">Book not found</h1>
                <p class="text-muted">The book you are looking for does not exist or has been removed.</p>
                <a href="<?= e(url('/')) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to catalog
                </a>
            </div>
        </div>
    </div>
    <?php
    layout_footer();
    exit;
}

$availability = availability($book);
layout_header($book['title'] . ' — TAGOLOAN COMMUNITY COLLEGE Library OPAC', ['active' => 'browse']);
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('/browse')) ?>">Browse Books</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($book['title']) ?></li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div>
                            <i class="bi bi-book-half text-primary fs-2 d-block mb-2"></i>
                            <h1 class="h2 mb-1"><?= e($book['title']) ?></h1>
                            <p class="text-muted fs-5 mb-2">by <?= e($book['author']) ?></p>
                        </div>
                        <span class="badge text-bg-<?= e($availability['class']) ?> fs-6 align-self-center">
                            <i class="bi bi-<?= e($availability['icon']) ?> me-1"></i><?= e($availability['label']) ?>
                        </span>
                    </div>

                    <?php if ($book['category']): ?>
                        <span class="badge rounded-pill text-bg-secondary mb-3">
                            <i class="bi bi-tag me-1"></i><?= e($book['category']) ?>
                        </span>
                    <?php endif; ?>

                    <table class="meta-table table table-sm">
                        <tbody>
                        <?php if ($book['isbn']): ?>
                            <tr>
                                <td>ISBN</td>
                                <td><?= e($book['isbn']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($book['publisher']): ?>
                            <tr>
                                <td>Publisher</td>
                                <td><?= e($book['publisher']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($book['year']): ?>
                            <tr>
                                <td>Year</td>
                                <td><?= e((string) $book['year']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Copies</td>
                            <td><?= (int) $book['available_copies'] ?> of <?= (int) $book['copies'] ?> available</td>
                        </tr>
                        </tbody>
                    </table>

                    <?php if (trim((string) $book['description']) !== ''): ?>
                        <h2 class="h6 text-uppercase text-muted mt-4">Description</h2>
                        <p class="mb-0" style="white-space: pre-line;"><?= e($book['description']) ?></p>
                    <?php endif; ?>

                    <hr class="my-4">
                    <a href="<?= e(url('/search')) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to search
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
layout_footer();
