<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT v.*, c.name AS category_name, c.slug AS category_slug,
            pk.name AS park_name, pk.slug AS park_slug, y.year AS year_label
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     LEFT JOIN parks pk ON pk.id = v.park_id
     LEFT JOIN years y ON y.id = v.year_id
     WHERE v.id = ? AND v.is_published = 1
     LIMIT 1'
);
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    $pageTitle = 'Film not found';
    $currentNav = 'videography';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="empty-state"><p>This film is not available.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$embed = parse_video_embed((string) ($video['video_url'] ?? ''));
$pageTitle = $video['title'] . ' — ' . setting('site_name', 'Manasi');
$metaDescription = (string) ($video['description'] ?? $video['title']);
$currentNav = 'videography';
require __DIR__ . '/includes/header.php';
?>
<article class="film-view">
    <div class="film-stage">
        <?php if ($embed['embed']): ?>
            <iframe src="<?= e($embed['embed']) ?>" title="<?= e($video['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        <?php elseif (!empty($video['video_filename'])): ?>
            <video controls preload="metadata" poster="<?= !empty($video['thumb_filename']) ? e(upload_url('videos/thumbs/' . $video['thumb_filename'])) : '' ?>">
                <source src="<?= e(upload_url('videos/files/' . $video['video_filename'])) ?>">
                Your browser does not support this video.
            </video>
        <?php else: ?>
            <div class="empty-state"><p>No playable source is attached to this film.</p></div>
        <?php endif; ?>
    </div>
    <div class="film-copy">
        <p class="eyebrow">Videography</p>
        <h1><?= e($video['title']) ?></h1>
        <ul class="photo-crumbs">
            <?php if (!empty($video['category_name'])): ?>
                <li><?= e($video['category_name']) ?></li>
            <?php endif; ?>
            <?php if (!empty($video['park_name'])): ?>
                <li><?= e($video['park_name']) ?></li>
            <?php endif; ?>
            <?php if (!empty($video['year_label'])): ?>
                <li><?= e((string) $video['year_label']) ?></li>
            <?php endif; ?>
            <?php if (!empty($video['duration'])): ?>
                <li><?= e($video['duration']) ?></li>
            <?php endif; ?>
        </ul>
        <?php if (!empty($video['description'])): ?>
            <div class="photo-description"><?= nl2br(e($video['description'])) ?></div>
        <?php endif; ?>
        <p><a class="text-link" href="<?= e(url('videography.php')) ?>">← All films</a></p>
    </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
