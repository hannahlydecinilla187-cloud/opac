/* OPAC — catalog search & browse (drives /search and /browse). */

(function () {
    'use strict';

    // ---- initial state from URL (?q=&category=&sort=&page=) ----------------
    const params = new URLSearchParams(window.location.search);
    const validSorts = ['newest', 'title', 'year_desc', 'year_asc'];
    const sortParam = params.get('sort');

    const state = {
        q: (params.get('q') || '').trim(),
        category: params.get('category') || '',
        sort: validSorts.includes(sortParam) ? sortParam : 'newest',
        page: Math.max(1, parseInt(params.get('page'), 10) || 1),
    };

    const els = {
        input:         document.getElementById('search-input'),       // /search only
        category:      document.getElementById('category-filter'),   // /search only
        sort:          document.getElementById('sort-select'),
        chips:         [...document.querySelectorAll('.category-chip')], // /browse only
        browseHeading: document.getElementById('browse-heading'),     // /browse only
        results:       document.getElementById('results'),
        count:         document.getElementById('result-count'),
        paginationWrap: document.getElementById('pagination-wrap'),
        pagination:    document.getElementById('pagination'),
    };

    const escapeHtml = (s) => String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    // ---- rendering ---------------------------------------------------------

    function availabilityBadge(book) {
        if (book.available_copies <= 0) {
            return '<span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Not available</span>';
        }
        if (book.available_copies < book.copies) {
            return `<span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>${book.available_copies} of ${book.copies} available</span>`;
        }
        return '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Available</span>';
    }

    function bookCard(book) {
        const detailUrl = `${window.APP_BASE}/book/${book.id}`;
        const category = book.category
            ? `<span class="badge rounded-pill text-bg-light border"><i class="bi bi-tag me-1"></i>${escapeHtml(book.category)}</span>`
            : '';
        return `
        <div class="col">
            <div class="card book-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <i class="bi bi-book-half text-primary fs-3"></i>
                        ${category}
                    </div>
                    <a class="book-title text-decoration-none" href="${detailUrl}">${escapeHtml(book.title)}</a>
                    <div class="text-muted small">${escapeHtml(book.author)}</div>
                    <div class="mt-auto pt-2">${availabilityBadge(book)}</div>
                </div>
            </div>
        </div>`;
    }

    function renderPagination(page, pages) {
        if (pages <= 1) {
            els.paginationWrap.classList.add('d-none');
            return;
        }
        els.paginationWrap.classList.remove('d-none');

        const windowPages = [];
        if (pages <= 7) {
            for (let p = 1; p <= pages; p++) windowPages.push(p);
        } else {
            windowPages.push(1);
            if (page > 3) windowPages.push('…');
            for (let p = Math.max(2, page - 1); p <= Math.min(pages - 1, page + 1); p++) windowPages.push(p);
            if (page < pages - 2) windowPages.push('…');
            windowPages.push(pages);
        }

        let html = `
            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page - 1}" aria-label="Previous">&laquo;</a>
            </li>`;
        for (const p of windowPages) {
            html += p === '…'
                ? '<li class="page-item disabled"><span class="page-link">…</span></li>'
                : `<li class="page-item ${p === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
        }
        html += `
            <li class="page-item ${page >= pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page + 1}" aria-label="Next">&raquo;</a>
            </li>`;
        els.pagination.innerHTML = html;
    }

    // Keep the visible controls in sync with the state (deep links, chips).
    function syncControls() {
        if (els.input) els.input.value = state.q;
        if (els.category) els.category.value = state.category;
        if (els.sort) els.sort.value = state.sort;
        for (const chip of els.chips) {
            chip.classList.toggle('active', chip.dataset.category === state.category);
        }
        if (els.browseHeading) {
            els.browseHeading.textContent = state.category ? state.category : 'All books';
        }
    }

    async function search() {
        const params = new URLSearchParams({
            q: state.q, category: state.category, sort: state.sort, page: String(state.page),
        });
        try {
            const res = await fetch(`${window.APP_BASE}/api/search.php?${params}`);
            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Search failed.');
            }
            state.page = data.page;

            els.results.innerHTML = data.books.length
                ? data.books.map(bookCard).join('')
                : `<div class="col-12 text-center py-5 text-muted">
                       <i class="bi bi-search display-4 d-block mb-2"></i>
                       No books match your search.
                   </div>`;

            const shown = data.total === 1 ? 'book' : 'books';
            els.count.textContent = data.pages > 1
                ? `${data.total} ${shown} found · page ${data.page} of ${data.pages}`
                : `${data.total} ${shown} found`;

            renderPagination(data.page, data.pages);
        } catch (err) {
            els.results.innerHTML = '';
            els.paginationWrap.classList.add('d-none');
            els.count.textContent = '';
            window.Swal.fire({
                icon: 'error',
                title: 'Something went wrong',
                text: err.message || 'Could not load the catalog. Please try again.',
            });
        } finally {
            syncControls();
        }
    }

    // ---- events -------------------------------------------------------------

    if (els.input) {
        let debounce = null;
        els.input.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                state.q = els.input.value.trim();
                state.page = 1;
                search();
            }, 300);
        });
    }

    if (els.category) {
        els.category.addEventListener('change', () => {
            state.category = els.category.value;
            state.page = 1;
            search();
        });
    }

    if (els.sort) {
        els.sort.addEventListener('change', () => {
            state.sort = els.sort.value;
            state.page = 1;
            search();
        });
    }

    for (const chip of els.chips) {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            if (chip.classList.contains('active')) return;
            state.category = chip.dataset.category || '';
            state.page = 1;
            search();
        });
    }

    if (els.pagination) {
        els.pagination.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-page]');
            if (!link) return;
            e.preventDefault();
            const page = Number(link.dataset.page);
            if (page >= 1 && page !== state.page) {
                state.page = page;
                search();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    syncControls();
    search();
})();
