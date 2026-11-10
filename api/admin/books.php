<?php
declare(strict_types=1);

/**
 * OPAC — admin books CRUD (JSON API).
 *
 * GET  /admin/books.json                     -> list all books
 * POST /admin/books.json  {action:create|update|delete, ...}
 *
 * Requires a signed admin session cookie.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_json();

/** Normalize a book row for JSON output. */
function book_to_json(array $b): array
{
    return [
        'id'               => (int) $b['id'],
        'title'            => (string) $b['title'],
        'author'           => (string) $b['author'],
        'isbn'            => $b['isbn'],
        'publisher'        => $b['publisher'],
        'category'         => $b['category'],
        'year'             => $b['year'] !== null ? (int) $b['year'] : null,
        'copies'           => (int) $b['copies'],
        'available_copies' => (int) $b['available_copies'],
        'description'      => $b['description'],
    ];
}

/** Validate book fields; returns [values, errors]. */
function validate_book(array $data): array
{
    $values = [];
    $errors = [];

    // ---- title (required)
    $title = trim((string) ($data['title'] ?? ''));
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Title must be at most 255 characters.';
    } else {
        $values['title'] = $title;
    }

    // ---- author (required)
    $author = trim((string) ($data['author'] ?? ''));
    if ($author === '') {
        $errors['author'] = 'Author is required.';
    } elseif (mb_strlen($author) > 255) {
        $errors['author'] = 'Author must be at most 255 characters.';
    } else {
        $values['author'] = $author;
    }

    // ---- optional strings: isbn, publisher, category
    foreach ([
        'isbn'      => ['isbn', 20, 'ISBN'],
        'publisher' => ['publisher', 255, 'Publisher'],
        'category'  => ['category', 100, 'Category'],
    ] as $field => [$column, $max, $label]) {
        $value = trim((string) ($data[$field] ?? ''));
        if (mb_strlen($value) > $max) {
            $errors[$field] = "$label must be at most $max characters.";
        } else {
            $values[$column] = ($value !== '') ? $value : null;
        }
    }

    // ---- year (optional)
    $yearRaw = $data['year'] ?? '';
    if ($yearRaw === '' || $yearRaw === null) {
        $values['year'] = null;
    } else {
        $year = filter_var($yearRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 2100]]);
        if ($year === false) {
            $errors['year'] = 'Year must be a number between 1000 and 2100.';
        } else {
            $values['year'] = $year;
        }
    }

    // ---- copies (required)
    $copies = filter_var($data['copies'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    if ($copies === false) {
        $errors['copies'] = 'Total copies must be a number between 1 and 10,000.';
    } else {
        $values['copies'] = $copies;
    }

    // ---- available copies (optional, defaults to total copies)
    $availRaw = $data['available_copies'] ?? '';
    if ($availRaw === '' || $availRaw === null) {
        $values['available_copies'] = isset($values['copies']) ? $values['copies'] : 0;
    } else {
        $avail = filter_var($availRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]);
        if ($avail === false) {
            $errors['available_copies'] = 'Available copies must be a number between 0 and 10,000.';
        } else {
            $values['available_copies'] = $avail;
        }
    }
    if (!isset($errors['available_copies'])
        && isset($values['copies'], $values['available_copies'])
        && $values['available_copies'] > $values['copies']) {
        $errors['available_copies'] = 'Available copies cannot exceed total copies.';
    }

    // ---- description (optional)
    $description = trim((string) ($data['description'] ?? ''));
    if (mb_strlen($description) > 65535) {
        $errors['description'] = 'Description is too long.';
    } else {
        $values['description'] = ($description !== '') ? $description : null;
    }

    return [$values, $errors];
}

// ---------------------------------------------------------------------------
// GET: list
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $books = db()->query('SELECT * FROM books ORDER BY id DESC')->fetchAll();
    json_out(['ok' => true, 'books' => array_map('book_to_json', $books)]);
}

// ---------------------------------------------------------------------------
// POST: create / update / delete
// ---------------------------------------------------------------------------
verify_same_origin();

$data   = json_input();
$action = (string) ($data['action'] ?? '');

if ($action === 'delete') {
    $id = filter_var($data['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        json_error('Invalid book id.');
    }
    $stmt = db()->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Book not found.', 404);
    }
    json_out(['ok' => true, 'message' => 'Book deleted.']);
}

if ($action !== 'create' && $action !== 'update') {
    json_error('Unknown action.');
}

[$values, $errors] = validate_book($data);
if ($errors !== []) {
    json_error('Please fix the highlighted fields.', 422, $errors);
}

if ($action === 'update') {
    $id = filter_var($data['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        json_error('Invalid book id.');
    }
    $stmt = db()->prepare('SELECT id FROM books WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Book not found.', 404);
    }

    $sql = 'UPDATE books SET title = ?, author = ?, isbn = ?, publisher = ?, category = ?,
                             year = ?, copies = ?, available_copies = ?, description = ?
                        WHERE id = ?';
    $params = array_merge(array_values($values), [$id]);
    db()->prepare($sql)->execute($params);

    $stmt = db()->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    json_out(['ok' => true, 'message' => 'Book updated.', 'book' => book_to_json($stmt->fetch())]);
}

$columns = 'title, author, isbn, publisher, category, year, copies, available_copies, description';
$marks   = rtrim(str_repeat('?, ', 9), ', ');
db()->prepare("INSERT INTO books ($columns) VALUES ($marks)")->execute(array_values($values));
$id = (int) db()->lastInsertId();

$stmt = db()->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
json_out(['ok' => true, 'message' => 'Book added.', 'book' => book_to_json($stmt->fetch())]);
