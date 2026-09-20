<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if (is_post() && posted('action') === 'delete') {
    verify_csrf();
    $id = (int) posted('id');
    $stmt = db()->prepare('SELECT stored_filename, thumb_filename FROM photos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_stored_image('photos', $row['stored_filename'], $row['thumb_filename']);
        db()->prepare('DELETE FROM photos WHERE id = ?')->execute([$id]);
        flash_set('success', 'Photograph deleted.');
    }
    redirect('admin/photos.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT p.*, c.name AS category_name, pk.name AS park_name, y.year AS year_label
        FROM photos p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN parks pk ON pk.id = p.park_id
        LEFT JOIN years y ON y.id = p.year_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE p.title LIKE ? OR p.description LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$countSql = 'SELECT COUNT(*) FROM photos p' . ($q !== '' ? ' WHERE p.title LIKE ? OR p.description LIKE ?' : '');
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$page = page_int();
[$page, $pages, $offset] = paginate($total, 20, $page);
$sql .= " ORDER BY p.created_at DESC LIMIT 20 OFFSET {$offset}";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$adminTitle = 'Photographs';
$adminNav = 'photos';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="toolbar">
    <form method="get" class="search-form">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title or description">
        <button class="btn" type="submit">Search</button>
    </form>
    <a class="btn btn-gold" href="<?= e(url('admin/photo-form.php')) ?>">Upload photograph</a>
</div>
<section class="panel">
    <table class="data">
        <thead>
            <tr>
                <th></th>
                <th>Title</th>
                <th>Master data</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="thumb-cell">
                    <img src="<?= e(photo_thumb($row)) ?>" alt="">
                </td>
                <td>
                    <strong><?= e($row['title']) ?></strong>
                    <div class="muted"><?= e($row['camera_model'] ?? $row['original_filename']) ?></div>
                </td>
                <td>
                    <?= e(implode(' · ', array_filter([
                        $row['category_name'] ?? null,
                        $row['park_name'] ?? null,
                        isset($row['year_label']) ? (string) $row['year_label'] : null,
                    ]))) ?>
                </td>
                <td>
                    <?= $row['is_published'] ? 'Live' : 'Draft' ?>
                    <?= $row['is_featured'] ? ' · Featured' : '' ?>
                </td>
                <td class="row-actions">
                    <a href="<?= e(url('photo.php?id=' . (int) $row['id'])) ?>" target="_blank" rel="noopener">View</a>
                    <a href="<?= e(url('admin/photo-form.php?id=' . (int) $row['id'])) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this photograph and its files?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="link-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="muted">No photographs yet. Upload the first one.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?= render_pagination($page, $pages, url('admin/photos.php') . ($q !== '' ? '?q=' . urlencode($q) : '')) ?>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
