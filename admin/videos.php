<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if (is_post() && posted('action') === 'delete') {
    verify_csrf();
    $id = (int) posted('id');
    $stmt = db()->prepare('SELECT video_filename, thumb_filename FROM videos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        if (!empty($row['video_filename'])) {
            $p = UPLOAD_PATH . '/videos/files/' . $row['video_filename'];
            if (is_file($p)) {
                @unlink($p);
            }
        }
        if (!empty($row['thumb_filename'])) {
            $p = UPLOAD_PATH . '/videos/thumbs/' . $row['thumb_filename'];
            if (is_file($p)) {
                @unlink($p);
            }
        }
        db()->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
        flash_set('success', 'Film deleted.');
    }
    redirect('admin/videos.php');
}

$rows = db()->query(
    'SELECT v.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     LEFT JOIN parks pk ON pk.id = v.park_id
     LEFT JOIN years y ON y.id = v.year_id
     ORDER BY v.created_at DESC'
)->fetchAll();

$adminTitle = 'Videography';
$adminNav = 'videos';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="toolbar">
    <span></span>
    <a class="btn btn-gold" href="<?= e(url('admin/video-form.php')) ?>">Add film</a>
</div>
<section class="panel">
    <table class="data">
        <thead>
            <tr><th>Title</th><th>Master data</th><th>Source</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><strong><?= e($row['title']) ?></strong></td>
                <td><?= e(implode(' · ', array_filter([$row['category_name'] ?? null, $row['park_name'] ?? null, isset($row['year_label']) ? (string) $row['year_label'] : null]))) ?></td>
                <td><?= $row['video_url'] ? 'URL' : ($row['video_filename'] ? 'File' : '—') ?></td>
                <td><?= $row['is_published'] ? 'Live' : 'Draft' ?></td>
                <td class="row-actions">
                    <a href="<?= e(url('admin/video-form.php?id=' . (int) $row['id'])) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this film?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="link-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="muted">No films yet. YouTube or Vimeo links work best on shared hosting.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
