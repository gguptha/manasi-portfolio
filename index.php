<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteName = setting('site_name', 'Manasi');
$tagline = setting('tagline', 'Wildlife Photography');
$pageTitle = $siteName . ' — ' . $tagline;
$metaDescription = setting('about_text', $tagline);
$bodyClass = 'page-home';

$homePhotoId = (int) setting('homepage_photo_id', '0');
$hero = $homePhotoId > 0 ? photo_by_id($homePhotoId, true) : null;
if (!$hero) {
    $hero = db()->query(
        'SELECT p.* FROM photos p
         WHERE p.is_published = 1
         ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC
         LIMIT 1'
    )->fetch() ?: null;
}
$homeText = setting('homepage_text', 'Wildlife stills from the field, made with attention to habitat and light.');
$homePhotography = setting('homepage_photography_text', 'Wildlife stills from the field, made with attention to habitat and light.');
$homeVideography = setting('homepage_videography_text', 'Field films, behavioural notes, and landscape sequences.');
$homeDesign = setting('homepage_design_text', 'Studio stills, composites, and related graphic work.');

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

<?php if ($homeText !== ''): ?>
<section class="section home-text">
    <div class="home-copy"><?= nl2br(e($homeText)) ?></div>
</section>
<?php endif; ?>

<section class="section practice">
    <div class="practice-grid">
        <a class="practice-card" href="<?= e(url('photography.php')) ?>">
            <h3>Photography</h3>
            <?php if ($homePhotography !== ''): ?><p><?= nl2br(e($homePhotography)) ?></p><?php endif; ?>
        </a>
        <a class="practice-card" href="<?= e(url('videography.php')) ?>">
            <h3>Videography</h3>
            <?php if ($homeVideography !== ''): ?><p><?= nl2br(e($homeVideography)) ?></p><?php endif; ?>
        </a>
        <a class="practice-card" href="<?= e(url('design.php')) ?>">
            <h3>Design</h3>
            <?php if ($homeDesign !== ''): ?><p><?= nl2br(e($homeDesign)) ?></p><?php endif; ?>
        </a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
