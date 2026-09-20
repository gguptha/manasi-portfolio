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
$nav = nav_master();

$counts = [
    'photos' => (int) db()->query('SELECT COUNT(*) FROM photos WHERE is_published = 1')->fetchColumn(),
    'videos' => (int) db()->query('SELECT COUNT(*) FROM videos WHERE is_published = 1')->fetchColumn(),
    'designs' => (int) db()->query('SELECT COUNT(*) FROM designs WHERE is_published = 1')->fetchColumn(),
];

require __DIR__ . '/includes/header.php';
?>

<section class="hero <?= $hero ? 'has-image' : '' ?>">
    <?php if ($hero): ?>
        <img class="hero-image" src="<?= e(photo_original($hero)) ?>" alt="<?= e($hero['title']) ?>">
    <?php endif; ?>
    <div class="hero-veil"></div>
    <div class="hero-copy">
        <p class="eyebrow">Field portfolio</p>
        <h1><?= e($siteName) ?></h1>
        <p class="hero-tag"><?= e($tagline) ?></p>
        <?php if (setting('about_text')): ?>
            <p class="hero-about"><?= e(setting('about_text')) ?></p>
        <?php endif; ?>
        <div class="hero-actions">
            <a class="btn btn-gold" href="<?= e(url('gallery.php?type=category')) ?>">Browse photographs</a>
            <a class="btn btn-ghost" href="<?= e(url('videography.php')) ?>">Videography</a>
        </div>
    </div>
</section>

<section class="section collections">
    <div class="section-head">
        <h2>Collections</h2>
        <p>Work is organised the way it is made — by creature, by forest, and by year.</p>
    </div>
    <div class="collection-cards">
        <a class="collection-card" href="<?= e(url('gallery.php?type=category')) ?>">
            <span>01</span>
            <strong>Categories</strong>
            <em><?= count($nav['categories']) ? e(implode(' · ', array_column($nav['categories'], 'name'))) : 'Reptiles, amphibians, birds, mammals' ?></em>
        </a>
        <a class="collection-card" href="<?= e(url('gallery.php?type=park')) ?>">
            <span>02</span>
            <strong>National Parks</strong>
            <em><?= count($nav['parks']) ? e(implode(' · ', array_slice(array_column($nav['parks'], 'name'), 0, 4))) : 'Ranthambore, Tadoba and more' ?></em>
        </a>
        <a class="collection-card" href="<?= e(url('gallery.php?type=year')) ?>">
            <span>03</span>
            <strong>Year</strong>
            <em>Archives arranged by field season</em>
        </a>
        <a class="collection-card" href="<?= e(url('design.php')) ?>">
            <span>04</span>
            <strong>Design & film</strong>
            <em><?= (int) $counts['videos'] ?> films · <?= (int) $counts['designs'] ?> design works</em>
        </a>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="section-head">
        <h2>Selected work</h2>
        <p>A short edit from the archive.</p>
    </div>
    <?php $photos = $featured; require __DIR__ . '/includes/gallery-grid.php'; ?>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head">
        <h2>Recent photographs</h2>
        <a class="text-link" href="<?= e(url('gallery.php?type=category')) ?>">View all</a>
    </div>
    <?php
    $photos = $recent;
    $emptyText = 'The gallery is empty. Sign in to Admin to upload the first photograph.';
    require __DIR__ . '/includes/gallery-grid.php';
    ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
