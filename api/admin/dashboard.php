<?php
declare(strict_types=1);

/**
 * OPAC — admin dashboard.  GET /admin
 *
 * Server-rendered page; book CRUD is handled client-side (admin.js) against
 * /admin/books.json (JSON) with SweetAlert2 confirmations and feedback.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_admin_page();

// --------------------------------------------------------------- stats (SQL)
$stats = db()->query(
    'SELECT
        COUNT(*)                                              AS titles,
        COALESCE(SUM(copies), 0)                             AS copies_total,
        COALESCE(SUM(available_copies), 0)                    AS copies_available,
        COUNT(DISTINCT NULLIF(category, ""))                  AS categories
     FROM books'
)->fetch();

$categories = db()
    ->query('SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> "" ORDER BY category')
    ->fetchAll(PDO::FETCH_COLUMN);

layout_header('Dashboard — TAGOLOAN COMMUNITY COLLEGE Library OPAC', ['active' => 'account']);?>
<div class="container py-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Catalog dashboard</h1>
            <p class="text-muted mb-0">Add, edit and remove books in the public catalog.</p>
        </div>
        <button class="btn btn-primary" id="btn-add" data-bs-toggle="modal" data-bs-target="#book-modal">
            <i class="bi bi-plus-lg me-1"></i>Add book
        </button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4" id="stats-row">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon text-bg-primary"><i class="bi bi-journal-bookmark"></i></span>
                    <div>
                        <div class="h4 mb-0" id="stat-titles"><?= (int) $stats['titles'] ?></div>
                        <div class="text-muted small">Titles</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon text-bg-success"><i class="bi bi-stack"></i></span>
                    <div>
                        <div class="h4 mb-0"><?= (int) $stats['copies_total'] ?></div>
                        <div class="text-muted small">Total copies</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon text-bg-info"><i class="bi bi-check2-circle"></i></span>
                    <div>
                        <div class="h4 mb-0"><?= (int) $stats['copies_available'] ?></div>
                        <div class="text-muted small">Available copies</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon text-bg-warning"><i class="bi bi-tags"></i></span>
                    <div>
                        <div class="h4 mb-0"><?= (int) $stats['categories'] ?></div>
                        <div class="text-muted small">Categories</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Books table -->
    <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
        <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2 py-3" style="border-radius: 1rem 1rem 0 0;">
            <i class="bi bi-journals me-1 text-primary"></i>
            <strong>Books</strong>
            <span class="badge rounded-pill text-bg-light" id="book-count">…</span>
            <div class="ms-auto position-relative">
                <input type="search" id="filter-input" class="form-control" placeholder="Filter…" autocomplete="off">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th class="d-none d-lg-table-cell">Year</th>
                    <th class="d-none d-lg-table-cell">ISBN</th>
                    <th>Copies</th>
                    <th class="text-end" style="width: 120px;">Actions</th>
                </tr>
                </thead>
                <tbody id="books-tbody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-secondary" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / edit modal -->
<div class="modal fade" id="book-modal" tabindex="-1" aria-labelledby="book-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 1rem;">
            <div class="modal-header">
                <h5 class="modal-title" id="book-modal-title">
                    <i class="bi bi-plus-lg me-1"></i><span id="book-modal-mode">Add a new book</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="book-form" novalidate>
                    <input type="hidden" id="field-id" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="field-title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="field-title" maxlength="255" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="field-author">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="field-author" maxlength="255" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-isbn">ISBN</label>
                            <input type="text" class="form-control" id="field-isbn" maxlength="20" placeholder="978…">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-category">Category</label>
                            <input type="text" class="form-control" id="field-category" maxlength="100" list="category-list"
                                   placeholder="e.g. Fiction">
                            <datalist id="category-list">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-publisher">Publisher</label>
                            <input type="text" class="form-control" id="field-publisher" maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-year">Year</label>
                            <input type="number" class="form-control" id="field-year" min="1000" max="2100"
                                   placeholder="e.g. 2024">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-copies">Total copies <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="field-copies" min="1" max="10000" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="field-available_copies">Available copies</label>
                            <input type="number" class="form-control" id="field-available_copies" min="0" max="10000" value="1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="field-description">Description</label>
                            <textarea class="form-control" id="field-description" rows="4" maxlength="65535"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="book-form" class="btn btn-primary" id="save-btn">
                    <i class="bi bi-check-lg me-1"></i>Save book
                </button>
            </div>
        </div>
    </div>
</div>
<?php
layout_footer(['scripts' => ['assets/js/admin.js']]);
