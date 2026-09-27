<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$photo = $id ? photo_by_id($id, true) : null;
if (!$photo) {
    http_response_code(404);
    $pageTitle = 'Photograph not found';
    $currentNav = '';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="empty-state"><p>This photograph is not available.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$navPair = adjacent_photo_ids($photo);
$detailRows = [];
if (!empty($photo['park_name'])) {
    $parkValue = (string) $photo['park_name'];
    if (!empty($photo['park_location'])) {
        $parkValue .= ' (' . $photo['park_location'] . ')';
    }
    $detailRows[] = ['label' => 'Location', 'value' => $parkValue];
}
if (!empty($photo['year_label'])) {
    $detailRows[] = ['label' => 'Year', 'value' => (string) $photo['year_label']];
}

$pageTitle = $photo['title'] . ' — ' . setting('site_name', 'Manasi');
$metaDescription = $photo['description'] ? (string) $photo['description'] : $photo['title'];
$bodyClass = 'page-photo';
if (!empty($photo['category_slug'])) {
    $currentNav = 'categories';
}

require __DIR__ . '/includes/header.php';
?>
<article class="photo-view">
    <figure class="photo-stage">
        <?php if ($navPair['prev']): ?>
            <a class="photo-arrow photo-arrow-prev" href="<?= e(url('photo.php?id=' . (int) $navPair['prev']['id'])) ?>" aria-label="Previous photograph">‹</a>
        <?php endif; ?>
        <img src="<?= e(photo_original($photo)) ?>" alt="<?= e($photo['title']) ?>">
        <?php if ($navPair['next']): ?>
            <a class="photo-arrow photo-arrow-next" href="<?= e(url('photo.php?id=' . (int) $navPair['next']['id'])) ?>" aria-label="Next photograph">›</a>
        <?php endif; ?>
    </figure>
    <aside class="photo-sheet">
        <?php if (!empty($photo['category_name']) && !empty($photo['category_slug'])): ?>
            <p class="photo-back"><a class="text-link" href="<?= e(url('gallery.php?type=category&slug=' . urlencode((string) $photo['category_slug']))) ?>"><?= e($photo['category_name']) ?></a></p>
        <?php endif; ?>
        <h1><?= e($photo['title']) ?></h1>
        <?php if (!empty($photo['description'])): ?>
            <div class="photo-description">
                <?= nl2br(e($photo['description'])) ?>
            </div>
        <?php endif; ?>

        <?php if ($detailRows): ?>
            <dl class="exif-sheet">
                <?php foreach ($detailRows as $row): ?>
                    <div>
                        <dt><?= e($row['label']) ?></dt>
                        <dd><?= e($row['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>

        <div class="photo-nav">
            <?php if ($navPair['prev']): ?>
                <a href="<?= e(url('photo.php?id=' . (int) $navPair['prev']['id'])) ?>">← <?= e($navPair['prev']['title']) ?></a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if ($navPair['next']): ?>
                <a href="<?= e(url('photo.php?id=' . (int) $navPair['next']['id'])) ?>"><?= e($navPair['next']['title']) ?> →</a>
            <?php endif; ?>
        </div>
    </aside>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
