<?php
declare(strict_types=1);

/**
 * OPAC — About page.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

layout_header('About — TAGOLOAN COMMUNITY COLLEGE Library OPAC', ['active' => 'about']);
?>
<section class="bg-white border-bottom">
    <div class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <i class="bi bi-journals display-4 text-primary"></i>
                <h1 class="mt-3 mb-2">About the library catalog</h1>
                <p class="text-muted mb-0 lead" style="font-size: 1.05rem !important;">
                    The TAGOLOAN COMMUNITY COLLEGE Library's Online Public Access Catalog (OPAC) lets
                    visitors explore the collection from anywhere — search for a specific title, browse
                    by category, and check availability before you visit the desk.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: .9rem;">
                <div class="card-body p-4">
                    <span class="stat-icon text-bg-primary d-inline-flex align-items-center justify-content-center mb-3" style="width:3rem;height:3rem;border-radius:.75rem;font-size:1.25rem;">
                        <i class="bi bi-search"></i>
                    </span>
                    <h2 class="h5 mb-2">Fast search</h2>
                    <p class="text-muted small mb-0">
                        Look up books by title, author, ISBN, publisher, category or keywords —
                        results update as you type.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: .9rem;">
                <div class="card-body p-4">
                    <span class="stat-icon text-bg-success d-inline-flex align-items-center justify-content-center mb-3" style="width:3rem;height:3rem;border-radius:.75rem;font-size:1.25rem;">
                        <i class="bi bi-journals"></i>
                    </span>
                    <h2 class="h5 mb-2">Browse collection</h2>
                    <p class="text-muted small mb-0">
                        Not sure what you're looking for? Wander the shelves by category and
                        sort by title or publication year.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: .9rem;">
                <div class="card-body p-4">
                    <span class="stat-icon text-bg-info d-inline-flex align-items-center justify-content-center mb-3" style="width:3rem;height:3rem;border-radius:.75rem;font-size:1.25rem;">
                        <i class="bi bi-check2-circle"></i>
                    </span>
                    <h2 class="h5 mb-2">Live availability</h2>
                    <p class="text-muted small mb-0">
                        Every record shows how many copies the library holds and how many are
                        on the shelf right now.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: .9rem;">
                <div class="card-body p-4">
                    <span class="stat-icon text-bg-warning d-inline-flex align-items-center justify-content-center mb-3" style="width:3rem;height:3rem;border-radius:.75rem;font-size:1.25rem;">
                        <i class="bi bi-person-gear"></i>
                    </span>
                    <h2 class="h5 mb-2">Managed catalog</h2>
                    <p class="text-muted small mb-0">
                        Library staff sign in through My Account to keep the catalog current —
                        adding titles and updating availability.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border-top">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="h4 mb-3">How to use it</h2>
                <ol class="list-unstyled text-muted mb-0">
                    <li class="d-flex gap-3 mb-3">
                        <span class="badge rounded-circle text-bg-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:1.75rem;height:1.75rem;">1</span>
                        <span>Use <a href="<?= e(url('/search')) ?>">Search Catalog</a> to find a known title, or
                              <a href="<?= e(url('/browse')) ?>">Browse Books</a> to explore by category.</span>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <span class="badge rounded-circle text-bg-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:1.75rem;height:1.75rem;">2</span>
                        <span>Open a book's page to see its full details — publisher, year, ISBN and description.</span>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <span class="badge rounded-circle text-bg-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:1.75rem;height:1.75rem;">3</span>
                        <span>Check the availability badge to see whether a copy is on the shelf.</span>
                    </li>
                    <li class="d-flex gap-3">
                        <span class="badge rounded-circle text-bg-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:1.75rem;height:1.75rem;">4</span>
                        <span>Library staff: manage everything from <a href="<?= e(url('/account')) ?>">My Account</a>.</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
<?php
layout_footer();
