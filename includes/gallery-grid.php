<?php
/** @var array $photos */
$photos = $photos ?? [];
$emptyText = $emptyText ?? 'No photographs in this collection yet.';
?>
<?php if (!$photos): ?>
    <div class="empty-state">
        <p><?= e($emptyText) ?></p>
    </div>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($photos as $photo): ?>
            <a class="thumb" href="<?= e(url('photo.php?id=' . (int) $photo['id'])) ?>">
                <span class="thumb-frame">
                    <img src="<?= e(photo_thumb($photo)) ?>" alt="<?= e($photo['title']) ?>" loading="lazy">
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
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
