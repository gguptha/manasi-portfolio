<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteName = setting('site_name', 'Manasi');
$tagline = setting('tagline', 'Wildlife Photography');
$pageTitle = $siteName . ' — ' . $tagline;
$metaDescription = setting('about_text', $tagline);
$bodyClass = 'page-home';

$featured = db()->query(
    'SELECT p.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
     FROM photos p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN parks pk ON pk.id = p.park_id
     LEFT JOIN years y ON y.id = p.year_id
     WHERE p.is_published = 1 AND p.is_featured = 1
     ORDER BY p.sort_order ASC, p.created_at DESC
     LIMIT 8'
)->fetchAll();

$recent = db()->query(
    'SELECT p.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
     FROM photos p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN parks pk ON pk.id = p.park_id
     LEFT JOIN years y ON y.id = p.year_id
     WHERE p.is_published = 1
     ORDER BY p.created_at DESC
     LIMIT 12'
)->fetchAll();

$hero = $featured[0] ?? $recent[0] ?? null;
$work = $featured ?: $recent;
$nav = nav_master();
$parkNames = array_slice(array_column($nav['parks'], 'name'), 0, 3);
$categoryNames = array_column($nav['categories'], 'name');

require __DIR__ . '/includes/header.php';
?>

<section class="intro">
    <h1><?= e($siteName) ?></h1>
    <p class="intro-tag"><?= e($tagline) ?></p>
</section>

<?php if ($hero): ?>
<section class="feature-image">
    <a href="<?= e(url('photo.php?id=' . (int) $hero['id'])) ?>">
        <img src="<?= e(photo_original($hero)) ?>" alt="<?= e($hero['title']) ?>">
    </a>
</section>
<?php endif; ?>

<section class="section practice">
    <h2 class="practice-title">What I do.</h2>
    <div class="practice-grid">
        <a class="practice-card" href="<?= e(url('gallery.php?type=category')) ?>">
            <h3>Photography.</h3>
            <p><?= count($categoryNames) ? e(implode(', ', $categoryNames)) . ' — stills from the field, made with attention to habitat and light.' : 'Wildlife stills from the field, made with attention to habitat and light.' ?></p>
        </a>
        <a class="practice-card" href="<?= e(url('gallery.php?type=park')) ?>">
            <h3>National Parks.</h3>
            <p><?= $parkNames ? 'Work from ' . e(implode(', ', $parkNames)) . ' and other Indian forests.' : 'Field work from Indian forests and tiger reserves.' ?></p>
        </a>
        <a class="practice-card" href="<?= e(url('videography.php')) ?>">
            <h3>Videography.</h3>
            <p>Field films, behavioural notes, and landscape sequences.</p>
        </a>
        <a class="practice-card" href="<?= e(url('design.php')) ?>">
            <h3>Design.</h3>
            <p>Studio stills, composites, and related graphic work.</p>
        </a>
    </div>
</section>

<?php if ($work): ?>
<section class="section work">
    <h2 class="practice-title">Selected work.</h2>
    <?php $photos = $work; require __DIR__ . '/includes/gallery-grid.php'; ?>
</section>
<?php else: ?>
<section class="section">
    <?php
    $photos = [];
    $emptyText = 'The gallery is empty. Sign in to Admin to upload the first photograph.';
    require __DIR__ . '/includes/gallery-grid.php';
    ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
