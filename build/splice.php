<?php
declare(strict_types=1);

/**
 * OPAC offline edition — build script.
 *
 * Assembles the final opac-offline.html from the template in the repo root:
 *   - inlines Bootstrap 5.3.3 CSS (the same build the online app uses),
 *   - inlines the site theme (assets/css/styles.css),
 *   - replaces the hand-drawn icon sprite with the REAL Bootstrap Icons 1.11.3
 *     SVGs (only the ~36 icons the app uses), taken from the official sprite,
 *   - strips the legacy hand-rolled CSS replica.
 *
 * Downloads are cached in build/cache/ so reruns are instant and rate-limit safe.
 * Usage:  C:\xampp\php\php.exe build\splice.php
 * The result is still ONE file with zero external dependencies.
 */

$root     = dirname(__DIR__);
$htmlPath = $root . DIRECTORY_SEPARATOR . 'tcc-opac.html';
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

/** HTTP GET with curl fallback + retries, cached on disk. */
function fetch_cached(string $url, string $cacheFile): string
{
    if (is_file($cacheFile) && filesize($cacheFile) > 0) {
        return file_get_contents($cacheFile);
    }

    $attempt = static function () use ($url): string|false {
        $ctx = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: opac-build\r\n"]]);
        $data = @file_get_contents($url, false, $ctx);
        if (is_string($data) && $data !== '') {
            return $data;
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'opac-build',
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($data) && $data !== '' && $code >= 200 && $code < 300) {
                return $data;
            }
        }
        return false;
    };

    for ($i = 0; $i < 3; $i++) {
        $data = $attempt();
        if ($data !== false) {
            file_put_contents($cacheFile, $data);
            return $data;
        }
        sleep(2 + $i); // back off and retry (rate limits are transient)
    }
    fwrite(STDERR, "FAILED to fetch {$url}\n");
    exit(1);
}

ini_set('pcre.backtrack_limit', '10000000'); // 1.1 MB single-line sprite

$html = file_get_contents($htmlPath);
if ($html === false) {
    fwrite(STDERR, "cannot read tcc-opac.html\n");
    exit(1);
}

// 1) Bootstrap 5.3.3 CSS — same version the online app loads from the CDN.
$bootstrap = fetch_cached(
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    $cacheDir . '/bootstrap-5.3.3.min.css'
);
if (strpos($bootstrap, '.navbar') === false) {
    fwrite(STDERR, "bootstrap css looks wrong (cache poisoned?)\n");
    exit(1);
}
$bootstrap = preg_replace('~/\*# sourceMappingURL=[^*]*\*/~', '', $bootstrap);

// 2) Site theme — identical to assets/css/styles.css.
$theme = file_get_contents($root . '/assets/css/styles.css');
if ($theme === false) {
    fwrite(STDERR, "cannot read assets/css/styles.css\n");
    exit(1);
}

// 3) Bootstrap Icons 1.11.3 — ONE request for the official sprite, then keep
//    only the icons the app actually uses (ids are renamed bi-* -> i-*).
$icons = [
    'house-door', 'search', 'journals', 'person-circle', 'info-circle',
    'box-arrow-right', 'x-circle', 'hourglass-split', 'check-circle',
    'tag', 'book-half', 'arrow-right', 'stars', 'inbox', 'tags',
    'person-badge', 'person', 'key', 'box-arrow-in-right', 'arrow-left',
    'journal-x', 'person-gear', 'shield-check', 'calendar3',
    'speedometer2', 'journal-plus', 'check2-circle', 'plus-lg',
    'journal-bookmark', 'stack', 'check-lg', 'pencil', 'trash',
    'exclamation-triangle', 'question-circle',
];
$spriteRaw = fetch_cached(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/bootstrap-icons.svg',
    $cacheDir . '/bootstrap-icons-1.11.3.svg'
);
// Official sprite format: <symbol class="bi bi-name" viewBox="0 0 16 16" id="name">
if (!preg_match_all('~<symbol[^>]*\bid="([a-z0-9-]+)"[^>]*>(.*?)</symbol>~s', $spriteRaw, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "icon sprite has no symbols (cache poisoned?)\n");
    exit(1);
}
$available = [];
foreach ($matches as $m) {
    $available[$m[1]] = $m[2];
}

$symbols = '';
foreach ($icons as $name) {
    if (!isset($available[$name])) {
        fwrite(STDERR, "icon '{$name}' not found in the sprite\n");
        exit(1);
    }
    $symbols .= '  <symbol id="i-' . $name . '" viewBox="0 0 16 16">'
        . trim($available[$name]) . "</symbol>\n";
}
$sprite = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">' . "\n"
    . $symbols . '</svg>';

// 4) Splice everything into the template.
$html = str_replace('/*__BOOTSTRAP__*/', $bootstrap, $html);
$html = str_replace('/*__THEME__*/', $theme, $html);

// Strip the legacy hand-rolled CSS block (marker ... end marker).
$html = preg_replace('~/\*__LEGACY_CSS__.*?__LEGACY_END__\*/\s*~s', '', $html);
if (strpos($html, '__LEGACY_CSS__') !== false || strpos($html, '__LEGACY_END__') !== false) {
    fwrite(STDERR, "legacy CSS was not fully stripped\n");
    exit(1);
}

// Replace the icon-sprite placeholder (and any previously built sprite) with the real one.
$html = preg_replace('~<!--__ICONS__-->.*?</svg>~s', '<!--__ICONS__-->', $html);
$html = str_replace('<!--__ICONS__-->', $sprite, $html);

if (strpos($html, '<!--__ICONS__-->') !== false || strpos($html, '__BOOTSTRAP__') !== false) {
    fwrite(STDERR, "markers were not replaced\n");
    exit(1);
}

file_put_contents($htmlPath, $html);
echo 'built tcc-opac.html: ' . number_format(strlen($html)) . ' bytes, '
    . count($icons) . " icons inlined\n";
