<?php
declare(strict_types=1);

/**
 * OPAC — public JSON search endpoint.
 *
 * GET /search.json?q=&category=&sort=&page=
 * Returns { ok, total, page, pages, books: [...] }
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$perPage = 12;

// --------------------------------------------------------------------- input
$q        = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$sort     = (string) ($_GET['sort'] ?? 'newest');
$page     = max(1, (int) ($_GET['page'] ?? 1));

$allowedSorts = [
    'newest'    => 'b.created_at DESC, b.id DESC',
    'title'     => 'b.title ASC',
    'year_desc' => 'b.year DESC, b.title ASC',
    'year_asc'  => 'b.year ASC, b.title ASC',
];
$orderBy = $allowedSorts[$sort] ?? $allowedSorts['newest'];

// ------------------------------------------------------------------- filters
$where  = [];
$params = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ? OR b.description LIKE ? OR b.category LIKE ?)';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($category !== '') {
    $where[] = 'b.category = ?';
    $params[] = $category;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------------------------------------------------------------------- count
$stmt = db()->prepare("SELECT COUNT(*) FROM books b $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

// --------------------------------------------------------------------- select
$columns = 'b.id, b.title, b.author, b.isbn, b.publisher, b.category, b.year,
            b.copies, b.available_copies, b.description';

$stmt = db()->prepare("SELECT $columns FROM books b $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

json_out([
    'ok'     => true,
    'total'  => $total,
    'page'   => $page,
    'pages'  => $pages,
    'books'  => array_map(static function (array $book): array {
        return [
            'id'              => (int) $book['id'],
            'title'           => $book['title'],
            'author'          => $book['author'],
            'isbn'            => $book['isbn'],
            'category'        => $book['category'],
            'year'            => $book['year'] !== null ? (int) $book['year'] : null,
            'available_copies' => (int) $book['available_copies'],
            'copies'          => (int) $book['copies'],
            'excerpt'         => excerpt($book['description']),
        ];
    }, $books),
]);
