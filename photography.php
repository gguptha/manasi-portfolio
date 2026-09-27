<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$landing = photography_landing();
$photos = photos_by_ids($landing['slots'], true);

$pageTitle = 'Photography — ' . setting('site_name', 'Manasi');
$metaDescription = $landing['text'] !== '' ? $landing['text'] : 'Photography.';
$currentNav = 'categories';
require __DIR__ . '/includes/header.php';
?>
<section class="landing-hero">
    <div class="landing-hero-copy">
        <?php if ($landing['text'] !== ''): ?>
            <p><?= nl2br(e($landing['text'])) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="landing-grid-wrap">
    <div class="landing-grid" style="--landing-cols: <?= (int) $landing['cols'] ?>">
        <?php foreach ($landing['slots'] as $photoId): ?>
            <?php $photo = $photos[(int) $photoId] ?? null; ?>
            <?php if ($photo): ?>
                <?php
                $cellHref = !empty($photo['category_slug'])
                    ? url('gallery.php?type=category&slug=' . urlencode((string) $photo['category_slug']))
                    : url('photo.php?id=' . (int) $photo['id']);
                ?>
                <a class="landing-cell" href="<?= e($cellHref) ?>">
                    <img src="<?= e(photo_thumb($photo)) ?>" alt="<?= e($photo['title']) ?>" loading="lazy">
                    <?php if (!empty($photo['category_name'])): ?>
                        <span class="landing-cell-label"><?= e($photo['category_name']) ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="landing-cell is-empty"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
