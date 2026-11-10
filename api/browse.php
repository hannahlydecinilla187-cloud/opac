<?php
declare(strict_types=1);

/**
 * OPAC — Browse Books page.  GET /browse
 *
 * Category chips + grid, driven by catalog.js (browse mode) against
 * /search.json.  Deep links supported: /browse?category=...&sort=...
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$categories = db()->query(
    'SELECT category, COUNT(*) AS n FROM books
     WHERE category IS NOT NULL AND category <> ""
     GROUP BY category ORDER BY n DESC, category'
)->fetchAll();

$initialCategory = trim((string) ($_GET['category'] ?? ''));

layout_header('Browse Books — TAGOLOAN COMMUNITY COLLEGE Library OPAC', ['active' => 'browse']);
?>
<section class="bg-white border-bottom py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <i class="bi bi-journals display-4 text-primary"></i>
                <h1 class="mt-2 mb-2">Browse the shelves</h1>
                <p class="text-muted mb-0">
                    Wander the collection by category — every title the library holds.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex flex-wrap gap-2" id="category-chips">
            <a href="#" class="btn btn-sm btn-outline-secondary category-chip <?= $initialCategory === '' ? 'active' : '' ?>"
               data-category="">All books</a>
            <?php foreach ($categories as $cat): ?>
                <a href="#" class="btn btn-sm btn-outline-secondary category-chip <?= $initialCategory === $cat['category'] ? 'active' : '' ?>"
                   data-category="<?= e((string) $cat['category']) ?>">
                    <?= e((string) $cat['category']) ?>
                    <span class="badge text-bg-light ms-1"><?= (int) $cat['n'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="col-6 col-md-3">
            <select id="sort-select" class="form-select form-select-sm" aria-label="Sort books">
                <option value="newest">Newest first</option>
                <option value="title">Title A–Z</option>
                <option value="year_desc">Year, new to old</option>
                <option value="year_asc">Year, old to new</option>
            </select>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0" id="browse-heading">All books</h2>
        <span class="result-count text-muted" id="result-count"></span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3" id="results">
        <div class="col-12 text-center py-5" id="loading">
            <div class="spinner-border text-secondary" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
        </div>
    </div>

    <nav aria-label="Books pages" class="mt-4 d-none" id="pagination-wrap">
        <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
</section>
<?php
layout_footer(['scripts' => ['assets/js/catalog.js']]);
