<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$page = page_int();
$total = (int) db()->query('SELECT COUNT(*) FROM designs WHERE is_published = 1')->fetchColumn();
[$page, $pages, $offset] = paginate($total, 24, $page);

$stmt = db()->query(
    "SELECT d.*, y.year AS year_label
     FROM designs d
     LEFT JOIN years y ON y.id = d.year_id
     WHERE d.is_published = 1
     ORDER BY d.sort_order ASC, d.created_at DESC
     LIMIT 24 OFFSET {$offset}"
);
$items = $stmt->fetchAll();

$pageTitle = 'Design — ' . setting('site_name', 'Manasi');
$metaDescription = 'Design work and stills.';
$currentNav = 'design';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <h1>Design.</h1>
    <p>Graphic work, composites, and related stills.</p>
</section>
<section class="section">
    <?php if (!$items): ?>
        <div class="empty-state"><p>No design work has been published yet.</p></div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($items as $item): ?>
                <a class="thumb" href="<?= e(url('design-item.php?id=' . (int) $item['id'])) ?>">
                    <span class="thumb-frame">
                        <img src="<?= e(design_thumb($item)) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                        <span class="thumb-meta">
                            <span class="thumb-title"><?= e($item['title']) ?></span>
                            <span class="thumb-sub"><?= !empty($item['year_label']) ? e((string) $item['year_label']) : '' ?></span>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($page, $pages, url('design.php')) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
