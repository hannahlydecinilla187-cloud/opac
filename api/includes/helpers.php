<?php
declare(strict_types=1);

/** HTML-escape a value for output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Send a JSON response and stop. */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** JSON error response. */
function json_error(string $message, int $status = 400, array $errors = []): never
{
    json_out(['ok' => false, 'message' => $message, 'errors' => $errors], $status);
}

/** Read and decode a JSON request body (returns [] when empty/invalid). */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Redirect (browser or fetch caller) and stop. */
function redirect(string $location): never
{
    header('Location: ' . $location, true, 302);
    exit;
}

/** Availability info for a book row. */
function availability(array $book): array
{
    $total = (int) $book['copies'];
    $avail = (int) $book['available_copies'];
    if ($avail <= 0) {
        return ['label' => 'Not available', 'class' => 'danger', 'icon' => 'x-circle'];
    }
    if ($avail < $total) {
        return ['label' => "$avail of $total available", 'class' => 'warning', 'icon' => 'hourglass-split'];
    }
    return ['label' => 'Available', 'class' => 'success', 'icon' => 'check-circle'];
}

/** Short plain-text excerpt of a description. */
function excerpt(?string $text, int $length = 160): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length)) . '…';
}

/** Server-rendered book card (home page & other non-JS grids). */
function book_card_html(array $book): string
{
    $availability = availability($book);
    $detail = e(url('/book/' . (int) $book['id']));
    $title = e($book['title']);
    $author = e($book['author']);
    $category = $book['category']
        ? '<span class="badge rounded-pill text-bg-light border"><i class="bi bi-tag me-1"></i>' . e($book['category']) . '</span>'
        : '';
    $badge = '<span class="badge text-bg-' . e($availability['class']) . '">'
           . '<i class="bi bi-' . e($availability['icon']) . ' me-1"></i>' . e($availability['label']) . '</span>';

    ob_start();
    ?>
    <div class="col">
        <div class="card book-card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <i class="bi bi-book-half text-primary fs-3"></i>
                    <?= $category ?>
                </div>
                <a class="book-title text-decoration-none" href="<?= $detail ?>"><?= $title ?></a>
                <div class="text-muted small"><?= $author ?></div>
                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center gap-2">
                    <?= $badge ?>
                    <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="<?= $detail ?>">
                        View details <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
