<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if (is_post() && posted('action') === 'delete') {
    verify_csrf();
    $id = (int) posted('id');
    $stmt = db()->prepare('SELECT stored_filename, thumb_filename FROM designs WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_stored_image('designs', $row['stored_filename'], $row['thumb_filename']);
        db()->prepare('DELETE FROM designs WHERE id = ?')->execute([$id]);
        flash_set('success', 'Design work deleted.');
    }
    redirect('admin/designs.php');
}

$rows = db()->query(
    'SELECT d.*, y.year AS year_label FROM designs d LEFT JOIN years y ON y.id = d.year_id ORDER BY d.created_at DESC'
)->fetchAll();

$adminTitle = 'Design';
$adminNav = 'designs';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="toolbar">
    <span></span>
    <a class="btn btn-gold" href="<?= e(url('admin/design-form.php')) ?>">Add design work</a>
</div>
<section class="panel">
    <table class="data">
        <thead>
            <tr><th></th><th>Title</th><th>Year</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="thumb-cell"><img src="<?= e(design_thumb($row)) ?>" alt=""></td>
                <td><strong><?= e($row['title']) ?></strong></td>
                <td><?= e((string) ($row['year_label'] ?? '—')) ?></td>
                <td><?= $row['is_published'] ? 'Live' : 'Draft' ?></td>
                <td class="row-actions">
                    <a href="<?= e(url('admin/design-form.php?id=' . (int) $row['id'])) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this work?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="link-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="muted">No design pieces yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
