<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable $ex) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $configured = defined('BASE_URL') ? (string) BASE_URL : 'auto';
        if ($configured !== '' && $configured !== 'auto') {
            $base = rtrim($configured, '/');
        } else {
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
            $dir = rtrim(dirname($script), '/');
            if (substr($dir, -6) === '/admin') {
                $dir = substr($dir, 0, -6);
            }
            $base = $dir === '/' ? '' : $dir;
        }
    }
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function url(string $path = ''): string
{
    return base_url($path);
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function upload_url(string $relative): string
{
    return base_url('uploads/' . ltrim(str_replace('\\', '/', $relative), '/'));
}

function redirect(string $to): void
{
    if (!preg_match('#^https?://#i', $to) && strpos($to, '/') !== 0) {
        $to = base_url($to);
    }
    header('Location: ' . $to);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Invalid request token. Please go back and try again.');
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($items) ? $items : [];
}

function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function unique_slug(string $table, string $slug, ?int $excludeId = null): string
{
    $allowed = ['photos', 'videos', 'designs', 'categories', 'parks'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table');
    }
    $base = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM `{$table}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function nav_master(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $pdo = db();
    $data = [
        'categories' => $pdo->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll(),
        'parks' => $pdo->query('SELECT id, name, slug, location FROM parks WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll(),
        'years' => $pdo->query('SELECT id, year FROM years WHERE is_active = 1 ORDER BY year DESC')->fetchAll(),
    ];
    return $data;
}

function page_int(string $key = 'page', int $min = 1): int
{
    $n = (int) ($_GET[$key] ?? 1);
    return max($min, $n);
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    return [$page, $pages, $offset];
}

function render_pagination(int $page, int $pages, string $baseQuery): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $sep = strpos($baseQuery, '?') === false ? '?' : '&';
        $href = e($baseQuery . $sep . 'page=' . $i);
        $cls = $i === $page ? ' class="is-active"' : '';
        $html .= '<a href="' . $href . '"' . $cls . '>' . $i . '</a>';
    }
    $html .= '</nav>';
    return $html;
}

function photo_thumb(array $photo): string
{
    return upload_url('photos/thumbs/' . $photo['thumb_filename']);
}

function photo_original(array $photo): string
{
    return upload_url('photos/originals/' . $photo['stored_filename']);
}

function design_thumb(array $row): string
{
    return upload_url('designs/thumbs/' . $row['thumb_filename']);
}

function design_original(array $row): string
{
    return upload_url('designs/originals/' . $row['stored_filename']);
}

function allowed_image_mimes(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
}

function detect_image_mime(string $tmpPath, string $fallbackName = ''): ?string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath) ?: '';
    $allowed = allowed_image_mimes();
    if (isset($allowed[$mime])) {
        return $mime;
    }
    $ext = strtolower(pathinfo($fallbackName, PATHINFO_EXTENSION));
    $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    return $map[$ext] ?? null;
}

function ensure_dir(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
        throw new RuntimeException('Cannot create directory: ' . $path);
    }
}

function random_filename(string $ext): string
{
    return date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
}

function exif_frac_to_float($value): ?float
{
    if (is_numeric($value)) {
        return (float) $value;
    }
    if (is_string($value) && strpos($value, '/') !== false) {
        [$n, $d] = array_pad(explode('/', $value, 2), 2, '0');
        $d = (float) $d;
        if ($d == 0.0) {
            return null;
        }
        return (float) $n / $d;
    }
    return null;
}

function format_shutter($value): ?string
{
    $n = exif_frac_to_float($value);
    if ($n === null) {
        return is_string($value) ? $value : null;
    }
    if ($n >= 1) {
        return rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.') . 's';
    }
    if ($n > 0) {
        return '1/' . (string) max(1, (int) round(1 / $n)) . 's';
    }
    return null;
}

function format_aperture($value): ?string
{
    $n = exif_frac_to_float($value);
    if ($n === null) {
        return is_scalar($value) ? (string) $value : null;
    }
    return 'f/' . rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
}

function format_focal($value): ?string
{
    $n = exif_frac_to_float($value);
    if ($n === null) {
        return is_scalar($value) ? (string) $value : null;
    }
    return round($n) . 'mm';
}

function gps_to_decimal($coord, $ref): ?float
{
    if (!is_array($coord) || count($coord) < 3) {
        return null;
    }
    $d = exif_frac_to_float($coord[0]);
    $m = exif_frac_to_float($coord[1]);
    $s = exif_frac_to_float($coord[2]);
    if ($d === null || $m === null || $s === null) {
        return null;
    }
    $dec = $d + ($m / 60) + ($s / 3600);
    $ref = strtoupper((string) $ref);
    if ($ref === 'S' || $ref === 'W') {
        $dec *= -1;
    }
    return $dec;
}

function flatten_exif_value($value)
{
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            if (is_string($v) && (!preg_match('//u', $v) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $v))) {
                continue;
            }
            $out[$k] = flatten_exif_value($v);
        }
        return $out;
    }
    if (is_string($value)) {
        if (!preg_match('//u', $value)) {
            if (function_exists('iconv')) {
                $value = iconv('ISO-8859-1', 'UTF-8//IGNORE', $value) ?: '';
            } else {
                $value = '';
            }
        }
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;
        return trim($value);
    }
    if (is_numeric($value) || is_bool($value) || $value === null) {
        return $value;
    }
    return (string) $value;
}

function extract_exif(string $path): array
{
    $result = [
        'camera_make' => null,
        'camera_model' => null,
        'lens' => null,
        'focal_length' => null,
        'aperture' => null,
        'shutter_speed' => null,
        'iso' => null,
        'taken_at' => null,
        'gps_lat' => null,
        'gps_lng' => null,
        'orientation' => 1,
        'exif_json' => null,
    ];

    if (!function_exists('exif_read_data')) {
        return $result;
    }

    $raw = @exif_read_data($path, 'ANY_TAG', true);
    if (!is_array($raw)) {
        return $result;
    }

    $ifd0 = $raw['IFD0'] ?? [];
    $exif = $raw['EXIF'] ?? [];
    $gps = $raw['GPS'] ?? [];
    $computed = $raw['COMPUTED'] ?? [];

    $make = trim((string) ($ifd0['Make'] ?? ''));
    $model = trim((string) ($ifd0['Model'] ?? ''));
    $result['camera_make'] = $make !== '' ? $make : null;
    $result['camera_model'] = $model !== '' ? $model : null;

    $lens = $exif['UndefinedTag:0xA434'] ?? $exif['LensModel'] ?? $ifd0['UndefinedTag:0xA434'] ?? $computed['LensModel'] ?? null;
    if (is_string($lens) && trim($lens) !== '') {
        $result['lens'] = trim($lens);
    }

    $focal = $exif['FocalLength'] ?? $computed['FocalLength'] ?? null;
    $result['focal_length'] = $focal !== null ? format_focal($focal) : null;

    $aperture = $computed['ApertureFNumber'] ?? $exif['FNumber'] ?? $exif['ApertureValue'] ?? null;
    if (is_string($aperture) && stripos($aperture, 'f/') === 0) {
        $result['aperture'] = $aperture;
    } else {
        $result['aperture'] = $aperture !== null ? format_aperture($aperture) : null;
    }

    $shutter = $exif['ExposureTime'] ?? $exif['ShutterSpeedValue'] ?? null;
    $result['shutter_speed'] = $shutter !== null ? format_shutter($shutter) : null;

    $iso = $exif['ISOSpeedRatings'] ?? $exif['PhotographicSensitivity'] ?? null;
    if (is_array($iso)) {
        $iso = $iso[0] ?? null;
    }
    $result['iso'] = $iso !== null && $iso !== '' ? (string) $iso : null;

    $date = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? $ifd0['DateTime'] ?? null;
    if (is_string($date) && preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $date, $m)) {
        $result['taken_at'] = "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}";
    }

    if (!empty($gps['GPSLatitude']) && !empty($gps['GPSLongitude'])) {
        $result['gps_lat'] = gps_to_decimal($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
        $result['gps_lng'] = gps_to_decimal($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');
    }

    $orientation = (int) ($ifd0['Orientation'] ?? $exif['Orientation'] ?? 1);
    $result['orientation'] = $orientation >= 1 && $orientation <= 8 ? $orientation : 1;

    $clean = flatten_exif_value([
        'IFD0' => $ifd0,
        'EXIF' => $exif,
        'GPS' => $gps,
        'COMPUTED' => $computed,
    ]);
    $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    $result['exif_json'] = $json !== false ? $json : null;

    return $result;
}

function gd_image_from_file(string $path, string $mime)
{
    switch ($mime) {
        case 'image/jpeg':
            return @imagecreatefromjpeg($path);
        case 'image/png':
            return @imagecreatefrompng($path);
        case 'image/gif':
            return @imagecreatefromgif($path);
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                return @imagecreatefromwebp($path);
            }
            return false;
        default:
            return false;
    }
}

function apply_orientation($im, int $orientation)
{
    if (!$im || $orientation <= 1) {
        return $im;
    }
    switch ($orientation) {
        case 2:
            imageflip($im, IMG_FLIP_HORIZONTAL);
            break;
        case 3:
            $im = imagerotate($im, 180, 0);
            break;
        case 4:
            imageflip($im, IMG_FLIP_VERTICAL);
            break;
        case 5:
            imageflip($im, IMG_FLIP_VERTICAL);
            $im = imagerotate($im, -90, 0);
            break;
        case 6:
            $im = imagerotate($im, -90, 0);
            break;
        case 7:
            imageflip($im, IMG_FLIP_HORIZONTAL);
            $im = imagerotate($im, -90, 0);
            break;
        case 8:
            $im = imagerotate($im, 90, 0);
            break;
    }
    return $im;
}

function save_gd_image($im, string $path, string $mime, int $quality = 82): bool
{
    switch ($mime) {
        case 'image/jpeg':
            return imagejpeg($im, $path, $quality);
        case 'image/png':
            imagesavealpha($im, true);
            return imagepng($im, $path, 6);
        case 'image/gif':
            return imagegif($im, $path);
        case 'image/webp':
            if (function_exists('imagewebp')) {
                return imagewebp($im, $path, $quality);
            }
            return imagejpeg($im, $path, $quality);
        default:
            return false;
    }
}

function create_cover_thumb(string $src, string $dest, string $mime, int $orientation, int $targetW = 900, int $targetH = 675): bool
{
    $srcIm = gd_image_from_file($src, $mime);
    if (!$srcIm) {
        return false;
    }
    $srcIm = apply_orientation($srcIm, $orientation);
    $sw = imagesx($srcIm);
    $sh = imagesy($srcIm);
    $srcRatio = $sw / max(1, $sh);
    $dstRatio = $targetW / $targetH;

    if ($srcRatio > $dstRatio) {
        $cropH = $sh;
        $cropW = (int) round($sh * $dstRatio);
        $sx = (int) (($sw - $cropW) / 2);
        $sy = 0;
    } else {
        $cropW = $sw;
        $cropH = (int) round($sw / $dstRatio);
        $sx = 0;
        $sy = (int) (($sh - $cropH) / 2);
    }

    $dst = imagecreatetruecolor($targetW, $targetH);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
    }
    imagecopyresampled($dst, $srcIm, 0, 0, $sx, $sy, $targetW, $targetH, $cropW, $cropH);
    $ok = save_gd_image($dst, $dest, $mime === 'image/gif' ? 'image/jpeg' : $mime);
    imagedestroy($srcIm);
    imagedestroy($dst);
    return $ok;
}

function store_uploaded_image(array $file, string $kind = 'photos'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message((int) $file['error']));
    }

    $tmp = (string) $file['tmp_name'];
    $originalName = (string) $file['name'];
    $mime = detect_image_mime($tmp, $originalName);
    $allowed = allowed_image_mimes();
    if ($mime === null || !isset($allowed[$mime])) {
        throw new RuntimeException('Please upload a JPEG, PNG, WebP, or GIF image.');
    }

    $ext = $allowed[$mime];
    $stored = random_filename($ext);
    $thumbName = $stored;

    $origDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'originals';
    $thumbDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'thumbs';
    ensure_dir($origDir);
    ensure_dir($thumbDir);

    $origPath = $origDir . DIRECTORY_SEPARATOR . $stored;
    $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $thumbName;

    if (!move_uploaded_file($tmp, $origPath)) {
        throw new RuntimeException('Could not save the uploaded file. Check folder permissions on uploads/.');
    }

    $exif = extract_exif($origPath);
    $size = @getimagesize($origPath);
    $width = $size[0] ?? null;
    $height = $size[1] ?? null;

    $thumbOk = create_cover_thumb($origPath, $thumbPath, $mime, (int) $exif['orientation']);
    if (!$thumbOk) {
        copy($origPath, $thumbPath);
    }

    return [
        'original_filename' => $originalName,
        'stored_filename' => $stored,
        'thumb_filename' => $thumbName,
        'mime_type' => $mime,
        'file_size' => (int) filesize($origPath),
        'width' => $width,
        'height' => $height,
        'exif' => $exif,
    ];
}

function delete_stored_image(string $kind, ?string $stored, ?string $thumb): void
{
    if ($stored) {
        $p = UPLOAD_PATH . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR . $stored;
        if (is_file($p)) {
            @unlink($p);
        }
    }
    if ($thumb) {
        $p = UPLOAD_PATH . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $thumb;
        if (is_file($p)) {
            @unlink($p);
        }
    }
}

function upload_error_message(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'The file is larger than the server allows. On GoDaddy, raise upload_max_filesize in cPanel or compress the JPEG.';
        case UPLOAD_ERR_PARTIAL:
            return 'The file was only partially uploaded. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was selected.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server temp folder is missing.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write the file to disk.';
        default:
            return 'Upload failed (error ' . $code . ').';
    }
}

function parse_video_embed(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        return ['provider' => null, 'id' => null, 'embed' => null];
    }
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        $id = $m[1];
        return ['provider' => 'youtube', 'id' => $id, 'embed' => 'https://www.youtube.com/embed/' . $id];
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        $id = $m[1];
        return ['provider' => 'vimeo', 'id' => $id, 'embed' => 'https://player.vimeo.com/video/' . $id];
    }
    return ['provider' => 'other', 'id' => null, 'embed' => null];
}

function find_year_id_from_date(?string $takenAt): ?int
{
    if (!$takenAt || strlen($takenAt) < 4) {
        return null;
    }
    $year = (int) substr($takenAt, 0, 4);
    if ($year < 1990 || $year > 2100) {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM years WHERE year = ? LIMIT 1');
    $stmt->execute([$year]);
    $row = $stmt->fetch();
    return $row ? (int) $row['id'] : null;
}

function photo_by_id(int $id, bool $publishedOnly = true): ?array
{
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   pk.name AS park_name, pk.slug AS park_slug, pk.location AS park_location,
                   y.year AS year_label
            FROM photos p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN parks pk ON pk.id = p.park_id
            LEFT JOIN years y ON y.id = p.year_id
            WHERE p.id = ?';
    if ($publishedOnly) {
        $sql .= ' AND p.is_published = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adjacent_photo_ids(array $photo): array
{
    $prev = db()->prepare('SELECT id, title, slug FROM photos WHERE is_published = 1 AND id < ? ORDER BY id DESC LIMIT 1');
    $prev->execute([(int) $photo['id']]);
    $next = db()->prepare('SELECT id, title, slug FROM photos WHERE is_published = 1 AND id > ? ORDER BY id ASC LIMIT 1');
    $next->execute([(int) $photo['id']]);
    return ['prev' => $prev->fetch() ?: null, 'next' => $next->fetch() ?: null];
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function oversized_post(): bool
{
    return is_post() && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function posted(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function posted_int(string $key): ?int
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }
    return (int) $v;
}
