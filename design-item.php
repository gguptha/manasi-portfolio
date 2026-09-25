<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT d.*, y.year AS year_label
     FROM designs d
     LEFT JOIN years y ON y.id = d.year_id
     WHERE d.id = ? AND d.is_published = 1
     LIMIT 1'
);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Work not found';
    $currentNav = 'design';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="empty-state"><p>This piece is not available.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $item['title'] . ' — ' . setting('site_name', 'Manasi');
$metaDescription = (string) ($item['description'] ?? $item['title']);
$currentNav = 'design';
$bodyClass = 'page-photo';
require __DIR__ . '/includes/header.php';
?>
<article class="photo-view">
    <figure class="photo-stage">
        <img src="<?= e(design_original($item)) ?>" alt="<?= e($item['title']) ?>">
    </figure>
    <aside class="photo-sheet">
        <h1><?= e($item['title']) ?></h1>
        <?php if (!empty($item['year_label'])): ?>
            <ul class="photo-crumbs"><li><?= e((string) $item['year_label']) ?></li></ul>
        <?php endif; ?>
        <?php if (!empty($item['description'])): ?>
            <div class="photo-description"><?= nl2br(e($item['description'])) ?></div>
        <?php endif; ?>
        <?php if ($item['width'] && $item['height']): ?>
            <dl class="exif-sheet">
                <div>
                    <dt>Dimensions</dt>
                    <dd><?= (int) $item['width'] ?> × <?= (int) $item['height'] ?></dd>
                </div>
            </dl>
        <?php endif; ?>
        <p><a class="text-link" href="<?= e(url('design.php')) ?>">← All design work</a></p>
    </aside>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
