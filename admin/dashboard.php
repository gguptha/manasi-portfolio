<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$adminTitle = 'Dashboard';
$adminNav = 'dashboard';

$stats = [
    'photos' => (int) db()->query('SELECT COUNT(*) FROM photos')->fetchColumn(),
    'published' => (int) db()->query('SELECT COUNT(*) FROM photos WHERE is_published = 1')->fetchColumn(),
    'videos' => (int) db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'designs' => (int) db()->query('SELECT COUNT(*) FROM designs')->fetchColumn(),
    'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'parks' => (int) db()->query('SELECT COUNT(*) FROM parks')->fetchColumn(),
];

$recent = db()->query(
    'SELECT id, title, created_at, is_published FROM photos ORDER BY created_at DESC LIMIT 8'
)->fetchAll();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="stat-grid">
    <div class="stat-card"><span>Photographs</span><strong><?= $stats['photos'] ?></strong><em><?= $stats['published'] ?> published</em></div>
    <div class="stat-card"><span>Films</span><strong><?= $stats['videos'] ?></strong></div>
    <div class="stat-card"><span>Design works</span><strong><?= $stats['designs'] ?></strong></div>
    <div class="stat-card"><span>Master data</span><strong><?= $stats['categories'] ?> / <?= $stats['parks'] ?></strong><em>categories / parks</em></div>
</div>

<div class="panel-row">
    <section class="panel">
        <header>
            <h2>Quick actions</h2>
        </header>
        <div class="action-row">
            <a class="btn btn-gold" href="<?= e(url('admin/photo-form.php')) ?>">Upload photograph</a>
            <a class="btn" href="<?= e(url('admin/video-form.php')) ?>">Add film</a>
            <a class="btn" href="<?= e(url('admin/design-form.php')) ?>">Add design work</a>
            <a class="btn" href="<?= e(url('admin/parks.php')) ?>">Manage parks</a>
        </div>
        <p class="hint">JPEG/PNG/WebP files are stored on disk under <code>uploads/</code>. EXIF (camera, lens, exposure, GPS) is read automatically on upload.</p>
    </section>
    <section class="panel">
        <header>
            <h2>Recently added</h2>
            <a href="<?= e(url('admin/photos.php')) ?>">All photographs</a>
        </header>
        <?php if (!$recent): ?>
            <p class="muted">No photographs yet.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($recent as $row): ?>
                    <li>
                        <a href="<?= e(url('admin/photo-form.php?id=' . (int) $row['id'])) ?>"><?= e($row['title']) ?></a>
                        <span><?= e(substr($row['created_at'], 0, 10)) ?> · <?= $row['is_published'] ? 'Live' : 'Draft' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
