<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$type = (string) ($_GET['type'] ?? 'category');
$slug = trim((string) ($_GET['slug'] ?? ''));
$allowed = ['category', 'park', 'year'];
if (!in_array($type, $allowed, true)) {
    $type = 'category';
}

$filter = null;
$title = 'Photographs';
$intro = '';
$currentNav = $type === 'category' ? 'categories' : ($type === 'park' ? 'parks' : 'years');
$where = 'p.is_published = 1';
$params = [];

if ($type === 'category') {
    $title = 'Categories';
    $intro = 'Wildlife organised by taxon — reptiles, amphibians, birds, and mammals.';
    if ($slug !== '') {
        $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $filter = $stmt->fetch();
        if (!$filter) {
            http_response_code(404);
            $pageTitle = 'Category not found';
            require __DIR__ . '/includes/header.php';
            echo '<section class="section"><div class="empty-state"><p>This category does not exist.</p></div></section>';
            require __DIR__ . '/includes/footer.php';
            exit;
        }
        $title = $filter['name'];
        $intro = (string) ($filter['description'] ?? '');
        $where .= ' AND p.category_id = ?';
        $params[] = (int) $filter['id'];
    }
} elseif ($type === 'park') {
    $title = 'National Parks';
    $intro = 'Forests, floodplains, and reserves where the photographs were made.';
    if ($slug !== '') {
        $stmt = db()->prepare('SELECT * FROM parks WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $filter = $stmt->fetch();
        if (!$filter) {
            http_response_code(404);
            $pageTitle = 'Park not found';
            require __DIR__ . '/includes/header.php';
            echo '<section class="section"><div class="empty-state"><p>This national park does not exist.</p></div></section>';
            require __DIR__ . '/includes/footer.php';
            exit;
        }
        $title = $filter['name'];
        $loc = trim((string) ($filter['location'] ?? ''));
        $intro = trim($loc . ($loc && $filter['description'] ? ' — ' : '') . (string) ($filter['description'] ?? ''));
        $where .= ' AND p.park_id = ?';
        $params[] = (int) $filter['id'];
    }
} else {
    $title = 'Year';
    $intro = 'The archive by field season.';
    if ($slug !== '') {
        $stmt = db()->prepare('SELECT * FROM years WHERE year = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([(int) $slug]);
        $filter = $stmt->fetch();
        if (!$filter) {
            http_response_code(404);
            $pageTitle = 'Year not found';
            require __DIR__ . '/includes/header.php';
            echo '<section class="section"><div class="empty-state"><p>This year is not in the archive.</p></div></section>';
            require __DIR__ . '/includes/footer.php';
            exit;
        }
        $title = (string) $filter['year'];
        $intro = (string) ($filter['description'] ?? 'Photographs from ' . $filter['year'] . '.');
        $where .= ' AND p.year_id = ?';
        $params[] = (int) $filter['id'];
    }
}

if ($slug === '') {
    $masters = [];
    if ($type === 'category') {
        $masters = db()->query(
            'SELECT c.*, COUNT(p.id) AS photo_count
             FROM categories c
             LEFT JOIN photos p ON p.category_id = c.id AND p.is_published = 1
             WHERE c.is_active = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC'
        )->fetchAll();
    } elseif ($type === 'park') {
        $masters = db()->query(
            'SELECT pk.*, COUNT(p.id) AS photo_count
             FROM parks pk
             LEFT JOIN photos p ON p.park_id = pk.id AND p.is_published = 1
             WHERE pk.is_active = 1
             GROUP BY pk.id
             ORDER BY pk.sort_order ASC, pk.name ASC'
        )->fetchAll();
    } else {
        $masters = db()->query(
            'SELECT y.*, COUNT(p.id) AS photo_count
             FROM years y
             LEFT JOIN photos p ON p.year_id = y.id AND p.is_published = 1
             WHERE y.is_active = 1
             GROUP BY y.id
             ORDER BY y.year DESC'
        )->fetchAll();
    }

    $pageTitle = $title . ' — ' . setting('site_name', 'Manasi');
    $metaDescription = $intro;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-hero">
        <h1><?= e($title) ?>.</h1>
        <p><?= e($intro) ?></p>
    </section>
    <section class="section">
        <div class="index-grid">
            <?php foreach ($masters as $row): ?>
                <?php
                if ($type === 'year') {
                    $label = (string) $row['year'];
                    $href = url('gallery.php?type=year&slug=' . urlencode((string) $row['year']));
                    $sub = (string) ($row['description'] ?? '');
                } elseif ($type === 'park') {
                    $label = $row['name'];
                    $href = url('gallery.php?type=park&slug=' . urlencode($row['slug']));
                    $sub = (string) ($row['location'] ?? '');
                } else {
                    $label = $row['name'];
                    $href = url('gallery.php?type=category&slug=' . urlencode($row['slug']));
                    $sub = (string) ($row['description'] ?? '');
                }
                ?>
                <a class="index-card" href="<?= e($href) ?>">
                    <strong><?= e($label) ?></strong>
                    <?php if ($sub !== ''): ?><p><?= e($sub) ?></p><?php endif; ?>
                    <span><?= (int) $row['photo_count'] ?> photograph<?= (int) $row['photo_count'] === 1 ? '' : 's' ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (!$masters): ?>
                <div class="empty-state"><p>No entries yet. Add them from the admin panel.</p></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM photos p WHERE {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = page_int();
[$page, $pages, $offset] = paginate($total, 24, $page);

$sql = "SELECT p.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
        FROM photos p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN parks pk ON pk.id = p.park_id
        LEFT JOIN years y ON y.id = p.year_id
        WHERE {$where}
        ORDER BY y.year DESC, p.sort_order ASC, p.created_at DESC
        LIMIT 24 OFFSET {$offset}";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll();

$pageTitle = $title . ' — ' . setting('site_name', 'Manasi');
$metaDescription = $intro !== '' ? $intro : $title;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <h1><?= e($title) ?>.</h1>
    <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    <p class="count-line"><?= $total ?> photograph<?= $total === 1 ? '' : 's' ?></p>
</section>
<section class="section">
    <?php
    $emptyText = 'No photographs in this collection yet.';
    require __DIR__ . '/includes/gallery-grid.php';
    echo render_pagination($page, $pages, url('gallery.php?type=' . urlencode($type) . '&slug=' . urlencode($slug)));
    ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
