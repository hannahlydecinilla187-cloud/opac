<?php
declare(strict_types=1);

/**
 * OPAC — Search Catalog page.  GET /search
 *
 * Client-side search (catalog.js) against api/search.php.
 * Deep links supported: /search?q=...&category=...&sort=...&page=...
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$categories = db()
    ->query('SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> "" ORDER BY category')
    ->fetchAll(PDO::FETCH_COLUMN);

layout_header('Search Catalog — Online Public Access Catalog', ['active' => 'search']);
?>
<section class="hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-6 mb-2">Search the catalog</h1>
                <p class="lead mb-4 opacity-75">
                    Find books by title, author, ISBN, publisher, category or keywords.
                </p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card search-card">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12 col-md">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="search" id="search-input" class="form-control border-start-0 ps-0"
                                           placeholder="Search books, authors, ISBN…" autocomplete="off" autofocus>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <select id="category-filter" class="form-select form-select-lg text-truncate">
                                    <option value="">All categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= e($category) ?>"><?= e($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select id="sort-select" class="form-select form-select-lg">
                                    <option value="newest">Newest first</option>
                                    <option value="title">Title A–Z</option>
                                    <option value="year_desc">Year, new to old</option>
                                    <option value="year_asc">Year, old to new</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0">Results</h2>
        <span class="result-count text-muted" id="result-count"></span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3" id="results">
        <div class="col-12 text-center py-5" id="loading">
            <div class="spinner-border text-secondary" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
        </div>
    </div>

    <nav aria-label="Search results pages" class="mt-4 d-none" id="pagination-wrap">
        <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
</section>
<?php
layout_footer(['scripts' => ['assets/js/catalog.js']]);
