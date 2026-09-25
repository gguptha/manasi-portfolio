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
$exifRows = [];
$map = [
    'Camera' => trim(implode(' ', array_filter([$photo['camera_make'], $photo['camera_model']]))),
    'Lens' => $photo['lens'],
    'Focal length' => $photo['focal_length'],
    'Aperture' => $photo['aperture'],
    'Shutter' => $photo['shutter_speed'],
    'ISO' => $photo['iso'] !== null && $photo['iso'] !== '' ? 'ISO ' . $photo['iso'] : '',
    'Captured' => $photo['taken_at'] ? date('d M Y, H:i', strtotime($photo['taken_at'])) : '',
    'Dimensions' => ($photo['width'] && $photo['height']) ? $photo['width'] . ' × ' . $photo['height'] : '',
];
foreach ($map as $label => $value) {
    if ($value !== null && trim((string) $value) !== '') {
        $exifRows[] = ['label' => $label, 'value' => $value];
    }
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
        <img src="<?= e(photo_original($photo)) ?>" alt="<?= e($photo['title']) ?>">
    </figure>
    <aside class="photo-sheet">
        <h1><?= e($photo['title']) ?></h1>
        <ul class="photo-crumbs">
            <?php if (!empty($photo['category_name'])): ?>
                <li><a href="<?= e(url('gallery.php?type=category&slug=' . urlencode($photo['category_slug']))) ?>"><?= e($photo['category_name']) ?></a></li>
            <?php endif; ?>
            <?php if (!empty($photo['park_name'])): ?>
                <li><a href="<?= e(url('gallery.php?type=park&slug=' . urlencode($photo['park_slug']))) ?>"><?= e($photo['park_name']) ?><?= !empty($photo['park_location']) ? ', ' . e($photo['park_location']) : '' ?></a></li>
            <?php endif; ?>
            <?php if (!empty($photo['year_label'])): ?>
                <li><a href="<?= e(url('gallery.php?type=year&slug=' . urlencode((string) $photo['year_label']))) ?>"><?= e((string) $photo['year_label']) ?></a></li>
            <?php endif; ?>
        </ul>
        <?php if (!empty($photo['description'])): ?>
            <div class="photo-description">
                <?= nl2br(e($photo['description'])) ?>
            </div>
        <?php endif; ?>

        <h2>Exposure</h2>
        <?php if ($exifRows): ?>
            <dl class="exif-sheet">
                <?php foreach ($exifRows as $row): ?>
                    <div>
                        <dt><?= e($row['label']) ?></dt>
                        <dd><?= e((string) $row['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php else: ?>
            <p class="muted">No EXIF data was embedded in this file.</p>
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
