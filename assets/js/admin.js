/* OPAC — admin dashboard (book CRUD with SweetAlert2). */

(function () {
    'use strict';

    const API = `${window.APP_BASE}/admin/books.json`;

    const escapeHtml = (s) => String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    const FIELDS = ['title', 'author', 'isbn', 'publisher', 'category', 'year',
                    'copies', 'available_copies', 'description'];

    let books = [];

    const modal = new bootstrap.Modal(document.getElementById('book-modal'));
    const modalEl = document.getElementById('book-modal');
    const form = document.getElementById('book-form');
    const tbody = document.getElementById('books-tbody');
    const filter = document.getElementById('filter-input');
    const countBadge = document.getElementById('book-count');
    const modalMode = document.getElementById('book-modal-mode');

    // ---- helpers ------------------------------------------------------------

    const fieldEl = (name) => document.getElementById(`field-${name}`);

    function clearValidation() {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach((el) => (el.textContent = ''));
    }

    function handleUnauthorized(res) {
        if (res && res.status === 401) {
            window.Swal.fire({
                icon: 'warning',
                title: 'Session expired',
                text: 'Please log in again.',
                timer: 1200,
                showConfirmButton: false,
            }).then(() => {
                window.location.assign(`${window.APP_BASE}/admin/login`);
            });
            return true;
        }
        return false;
    }

    async function apiGet() {
        const res = await fetch(API, { headers: { 'Accept': 'application/json' } });
        if (handleUnauthorized(res)) return null;
        const data = await res.json();
        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'Could not load books.');
        }
        return data.books;
    }

    async function apiPost(payload) {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        if (handleUnauthorized(res)) return null;
        return { res, data: await res.json().catch(() => ({})) };
    }

    function refreshStats() {
        const titles = books.length;
        const copiesTotal = books.reduce((sum, b) => sum + b.copies, 0);
        const copiesAvailable = books.reduce((sum, b) => sum + b.available_copies, 0);
        const categories = new Set(books.map((b) => (b.category || '').trim()).filter(Boolean)).size;

        const statEls = document.querySelectorAll('#stats-row .h4');
        if (statEls.length >= 4) {
            statEls[0].textContent = titles;
            statEls[1].textContent = copiesTotal;
            statEls[2].textContent = copiesAvailable;
            statEls[3].textContent = categories;
        }
    }

    // ---- rendering ----------------------------------------------------------

    function copiesCell(book) {
        if (book.available_copies <= 0) {
            return '<span class="badge text-bg-danger">0/' + book.copies + '</span>';
        }
        if (book.available_copies < book.copies) {
            return `<span class="badge text-bg-warning">${book.available_copies}/${book.copies}</span>`;
        }
        return `<span class="badge text-bg-success">${book.available_copies}/${book.copies}</span>`;
    }

    function row(book) {
        return `
        <tr data-id="${book.id}">
            <td>
                <a class="fw-semibold text-decoration-none" href="${window.APP_BASE}/book/${book.id}" target="_blank" rel="noopener">${escapeHtml(book.title)}</a>
                <div class="small text-muted">${escapeHtml(book.author)}</div>
            </td>
            <td class="d-none d-md-table-cell">
                ${book.category ? `<span class="badge rounded-pill text-bg-light border">${escapeHtml(book.category)}</span>` : '<span class="text-muted">—</span>'}
            </td>
            <td class="d-none d-lg-table-cell">${book.year ?? '—'}</td>
            <td class="d-none d-lg-table-cell">${escapeHtml(book.isbn || '—')}</td>
            <td>${copiesCell(book)}</td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-edit="${book.id}" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" data-delete="${book.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    }

    function render() {
        const q = filter.value.trim().toLowerCase();
        const visible = q === ''
            ? books
            : books.filter((b) => [b.title, b.author, b.isbn, b.category, b.publisher]
                .some((v) => String(v || '').toLowerCase().includes(q)));

        tbody.innerHTML = visible.length
            ? visible.map(row).join('')
            : `<tr><td colspan="6" class="text-center text-muted py-5">
                   <i class="bi bi-inbox display-6 d-block mb-2"></i>No books found.
               </td></tr>`;

        countBadge.textContent = `${visible.length} book${visible.length === 1 ? '' : 's'}`;
        refreshStats();
    }

    async function load() {
        try {
            books = await apiGet();
            if (books !== null) render();
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-5">${escapeHtml(err.message)}</td></tr>`;
            window.Swal.fire({ icon: 'error', title: 'Could not load books', text: err.message });
        }
    }

    // ---- modal (add / edit) ---------------------------------------------------

    function openModal(book) {
        clearValidation();
        form.reset();
        fieldEl('id').value = book ? book.id : '';

        if (book) {
            modalMode.textContent = 'Edit book';
            for (const name of FIELDS) {
                const el = fieldEl(name);
                if (el) el.value = book[name] ?? '';
            }
        } else {
            modalMode.textContent = 'Add a new book';
            fieldEl('copies').value = 1;
            fieldEl('available_copies').value = 1;
        }
        modal.show();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearValidation();

        const id = fieldEl('id').value;
        const payload = { action: id ? 'update' : 'create' };
        if (id) payload.id = Number(id);
        for (const name of FIELDS) {
            const value = String(fieldEl(name)?.value ?? '').trim();
            if (value !== '') payload[name] = value;
        }

        const saveBtn = document.getElementById('save-btn');
        saveBtn.disabled = true;
        try {
            const { res, data } = await apiPost(payload);
            if (!res || data === null) return;

            if (res.ok && data.ok) {
                modal.hide();
                window.toast.fire({ icon: 'success', title: data.message || 'Saved.' });
                await load();
            } else if (res.status === 422 && data.errors) {
                for (const [field, message] of Object.entries(data.errors)) {
                    const input = fieldEl(field);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentElement.querySelector('.invalid-feedback');
                        if (feedback) feedback.textContent = message;
                    }
                }
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Check the form',
                    text: data.message || 'Please fix the highlighted fields.',
                });
            } else {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Could not save',
                    text: data.message || 'Unexpected server error. Please try again.',
                });
            }
        } catch {
            window.Swal.fire({
                icon: 'error',
                title: 'Network error',
                text: 'Could not reach the server. Please try again.',
            });
        } finally {
            saveBtn.disabled = false;
        }
    });

    // ---- delete ---------------------------------------------------------------

    async function deleteBook(book) {
        const result = await window.Swal.fire({
            icon: 'warning',
            title: 'Delete this book?',
            html: `<strong>${escapeHtml(book.title)}</strong><br><span class="text-muted">by ${escapeHtml(book.author)}</span><br><br>This cannot be undone.`,
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '<i class="bi bi-trash"></i> Delete',
            cancelButtonText: 'Cancel',
            focusCancel: true,
        });
        if (!result.isConfirmed) return;

        try {
            const { res, data } = await apiPost({ action: 'delete', id: book.id });
            if (!res || data === null) return;
            if (res.ok && data.ok) {
                window.toast.fire({ icon: 'success', title: data.message || 'Deleted.' });
                await load();
            } else {
                window.Swal.fire({ icon: 'error', title: 'Could not delete', text: data.message || 'Please try again.' });
            }
        } catch {
            window.Swal.fire({ icon: 'error', title: 'Network error', text: 'Please try again.' });
        }
    }

    // ---- events ----------------------------------------------------------------

    document.getElementById('btn-add').addEventListener('click', () => openModal(null));

    tbody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('button[data-edit]');
        const deleteBtn = e.target.closest('button[data-delete]');
        if (editBtn) {
            const book = books.find((b) => b.id === Number(editBtn.dataset.edit));
            if (book) openModal(book);
        } else if (deleteBtn) {
            const book = books.find((b) => b.id === Number(deleteBtn.dataset.delete));
            if (book) deleteBook(book);
        }
    });

    filter.addEventListener('input', render);

    modalEl.addEventListener('hidden.bs.modal', clearValidation);

    load();
})();
