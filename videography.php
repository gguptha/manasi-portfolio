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
    <h1 class="page-title">Videography</h1>
    <?php $pageIntro = setting('page_videography_text', 'Field films, behavioural notes, and landscape sequences.'); ?>
    <?php if ($pageIntro !== ''): ?><p class="page-intro"><?= nl2br(e($pageIntro)) ?></p><?php endif; ?>
</section>
<section class="section">
    <?php if (!$videos): ?>
        <div class="empty-state"><p>No films have been published yet.</p></div>
    <?php else: ?>
        <div class="video-rows">
            <?php foreach ($videos as $index => $video): ?>
                <?php
                $embed = parse_video_embed((string) ($video['video_url'] ?? ''));
                $poster = !empty($video['thumb_filename']) ? upload_url('videos/thumbs/' . $video['thumb_filename']) : '';
                $file = !empty($video['video_filename']) ? upload_url('videos/files/' . $video['video_filename']) : '';
                ?>
                <article class="video-row<?= $index % 2 === 1 ? ' is-flipped' : '' ?>">
                    <button
                        class="video-poster<?= $poster === '' ? ' is-empty' : '' ?>"
                        type="button"
                        data-embed="<?= e((string) ($embed['embed'] ?? '')) ?>"
                        data-file="<?= e($file) ?>"
                        data-title="<?= e($video['title']) ?>"
                        <?= ($embed['embed'] || $file !== '') ? '' : 'disabled' ?>
                    >
                        <?php if ($poster !== ''): ?>
                            <img src="<?= e($poster) ?>" alt="<?= e($video['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="video-play" aria-hidden="true">▶</span>
                    </button>
                    <div class="video-copy">
                        <h2><?= e($video['title']) ?></h2>
                        <?php if (!empty($video['description'])): ?>
                            <p><?= nl2br(e($video['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($page, $pages, url('videography.php')) ?>
        <div class="video-lightbox" hidden>
            <button class="video-lightbox-close" type="button" aria-label="Close video">×</button>
            <div class="video-lightbox-stage"></div>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
