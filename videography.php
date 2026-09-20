<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$page = page_int();
$total = (int) db()->query('SELECT COUNT(*) FROM videos WHERE is_published = 1')->fetchColumn();
[$page, $pages, $offset] = paginate($total, 12, $page);

$stmt = db()->query(
    "SELECT v.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     LEFT JOIN parks pk ON pk.id = v.park_id
     LEFT JOIN years y ON y.id = v.year_id
     WHERE v.is_published = 1
     ORDER BY v.sort_order ASC, v.created_at DESC
     LIMIT 12 OFFSET {$offset}"
);
$videos = $stmt->fetchAll();

$pageTitle = 'Videography — ' . setting('site_name', 'Manasi');
$metaDescription = 'Wildlife films and field video.';
$currentNav = 'videography';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <p class="eyebrow">Moving image</p>
    <h1>Videography</h1>
    <p>Field films, behavioural notes, and landscape sequences.</p>
</section>
<section class="section">
    <?php if (!$videos): ?>
        <div class="empty-state"><p>No films have been published yet.</p></div>
    <?php else: ?>
        <div class="video-grid">
            <?php foreach ($videos as $video): ?>
                <a class="video-card" href="<?= e(url('video.php?id=' . (int) $video['id'])) ?>">
                    <span class="video-thumb">
                        <?php if (!empty($video['thumb_filename'])): ?>
                            <img src="<?= e(upload_url('videos/thumbs/' . $video['thumb_filename'])) ?>" alt="<?= e($video['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="video-placeholder">▶</span>
                        <?php endif; ?>
                    </span>
                    <span class="thumb-meta">
                        <span class="thumb-title"><?= e($video['title']) ?></span>
                        <span class="thumb-sub">
                            <?php
                            echo e(implode(' · ', array_filter([
                                $video['category_name'] ?? null,
                                $video['park_name'] ?? null,
                                isset($video['year_label']) ? (string) $video['year_label'] : null,
                            ])));
                            ?>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($page, $pages, url('videography.php')) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
