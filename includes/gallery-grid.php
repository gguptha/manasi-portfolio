<?php
/** @var array $photos */
$photos = $photos ?? [];
$emptyText = $emptyText ?? 'No photographs in this collection yet.';
$showMeta = $showMeta ?? true;
$galleryClass = $galleryClass ?? 'gallery-grid';
$pairedRows = $pairedRows ?? false;
?>
<?php if (!$photos): ?>
    <div class="empty-state">
        <p><?= e($emptyText) ?></p>
    </div>
<?php elseif ($pairedRows): ?>
    <div class="gallery-rows">
        <?php foreach (gallery_rows($photos) as $row): ?>
            <div class="gallery-row<?= $row['single'] ? ' is-single' : '' ?>" style="grid-template-columns: <?= e($row['columns']) ?>;<?= $row['single'] ? '' : ' aspect-ratio: ' . e(number_format((float) $row['ratio'], 4, '.', '')) . ';' ?>">
                <?php foreach ($row['photos'] as $photo): ?>
                    <a class="thumb" href="<?= e(url('photo.php?id=' . (int) $photo['id'])) ?>">
                        <span class="thumb-frame">
                            <img src="<?= e(photo_thumb($photo)) ?>" alt="<?= e($photo['title']) ?>" loading="lazy">
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="<?= e($galleryClass) ?>">
        <?php foreach ($photos as $photo): ?>
            <a class="thumb" href="<?= e(url('photo.php?id=' . (int) $photo['id'])) ?>">
                <span class="thumb-frame">
                    <img src="<?= e(photo_thumb($photo)) ?>" alt="<?= e($photo['title']) ?>" loading="lazy">
                </span>
                <?php if ($showMeta): ?>
                    <span class="thumb-meta">
                        <span class="thumb-title"><?= e($photo['title']) ?></span>
                        <span class="thumb-sub">
                            <?php
                            $bits = array_filter([
                                $photo['category_name'] ?? null,
                                $photo['park_name'] ?? null,
                                isset($photo['year_label']) ? (string) $photo['year_label'] : null,
                            ]);
                            echo e(implode(' · ', $bits));
                            ?>
                        </span>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
