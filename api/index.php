<?php
declare(strict_types=1);

/**
 * OPAC — home page (landing).  GET /
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

// ----------------------------------------------------------------------- stats
$stats = db()->query(
    'SELECT
        COUNT(*)                             AS titles,
        COALESCE(SUM(copies), 0)             AS copies,
        COUNT(DISTINCT NULLIF(category, "")) AS categories
     FROM books'
)->fetch();

$recent = db()->query('SELECT * FROM books ORDER BY created_at DESC, id DESC LIMIT 8')->fetchAll();

$categories = db()->query(
    'SELECT category, COUNT(*) AS n FROM books
     WHERE category IS NOT NULL AND category <> ""
     GROUP BY category ORDER BY n DESC, category LIMIT 12'
)->fetchAll();

layout_header('Home — Online Public Access Catalog', ['active' => 'home']);
?>
<section class="hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-5 mb-2">Welcome to the library catalog</h1>
                <p class="lead mb-4 opacity-75">
                    Search the collection, browse the shelves, and check what's available — before you visit the desk.
                </p>
                <form class="row g-2 justify-content-center mb-4" action="<?= e(url('/search')) ?>" method="get" role="search">
                    <div class="col-12 col-md-8">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" class="form-control border-start-0 ps-0"
                                   placeholder="Search by title, author, ISBN…" aria-label="Search the catalog">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-light btn-lg px-4">Search</button>
                    </div>
                </form>
                <a href="<?= e(url('/browse')) ?>" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-journals me-1"></i>Browse Books
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="container py-5">
    <div class="row g-3 justify-content-center text-center">
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="h3 mb-0 text-primary"><?= (int) $stats['titles'] ?></div>
                    <div class="text-muted small">Titles in the catalog</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="h3 mb-0 text-success"><?= (int) $stats['copies'] ?></div>
                    <div class="text-muted small">Copies on the shelves</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="h3 mb-0 text-info"><?= (int) $stats['categories'] ?></div>
                    <div class="text-muted small">Categories to explore</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="h3 mb-0 text-warning">24/7</div>
                    <div class="text-muted small">Open — online, anywhere</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent arrivals -->
<section class="bg-white border-top border-bottom py-5">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <h2 class="h4 mb-0">
                <i class="bi bi-stars me-1 text-primary"></i>Recent arrivals
            </h2>
            <a href="<?= e(url('/browse')) ?>" class="btn btn-sm btn-outline-primary">
                View all books <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if ($recent): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3">
                <?php foreach ($recent as $book): ?>
                    <?= book_card_html($book) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                No books in the catalog yet.
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Browse by category -->
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <h2 class="h4 mb-1">
                <i class="bi bi-tags me-1 text-primary"></i>Browse by category
            </h2>
            <p class="text-muted small mb-4">Pick a shelf and see everything the library has there.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <?php foreach ($categories as $cat): ?>
                    <a class="btn btn-outline-secondary rounded-pill category-chip"
                       href="<?= e(url('/browse')) ?>?category=<?= urlencode((string) $cat['category']) ?>">
                        <?= e((string) $cat['category']) ?>
                        <span class="badge text-bg-light ms-1"><?= (int) $cat['n'] ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$categories): ?>
                    <span class="text-muted">No categories yet.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
layout_footer();
