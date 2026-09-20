<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM parks WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if (is_post()) {
    verify_csrf();
    $action = (string) posted('action');
    if ($action === 'delete') {
        db()->prepare('DELETE FROM parks WHERE id = ?')->execute([(int) posted('id')]);
        flash_set('success', 'National park removed.');
        redirect('admin/parks.php');
    }

    $name = trim((string) posted('name'));
    $location = trim((string) posted('location'));
    $description = trim((string) posted('description'));
    $sort = (int) posted('sort_order', 0);
    $active = posted('is_active') ? 1 : 0;
    $id = posted_int('id');
    if ($name === '') {
        flash_set('error', 'Name is required.');
    } else {
        $slug = unique_slug('parks', slugify($name), $id);
        if ($id) {
            db()->prepare('UPDATE parks SET name=?, slug=?, location=?, description=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $slug, $location, $description, $sort, $active, $id]);
            flash_set('success', 'Park updated.');
        } else {
            db()->prepare('INSERT INTO parks (name, slug, location, description, sort_order, is_active) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $slug, $location, $description, $sort, $active]);
            flash_set('success', 'Park added.');
        }
        redirect('admin/parks.php');
    }
}

$rows = db()->query(
    'SELECT pk.*, COUNT(p.id) AS photo_count
     FROM parks pk
     LEFT JOIN photos p ON p.park_id = pk.id
     GROUP BY pk.id
     ORDER BY pk.sort_order ASC, pk.name ASC'
)->fetchAll();

$adminTitle = 'National Parks';
$adminNav = 'parks';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="split">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : '' ?>">
        <h2><?= $editing ? 'Edit park' : 'Add national park' ?></h2>
        <label>Name
            <input type="text" name="name" required value="<?= e($editing['name'] ?? (string) posted('name')) ?>">
        </label>
        <label>State / location
            <input type="text" name="location" value="<?= e($editing['location'] ?? (string) posted('location')) ?>" placeholder="Rajasthan">
        </label>
        <label>Description
            <textarea name="description" rows="4"><?= e($editing['description'] ?? (string) posted('description')) ?></textarea>
        </label>
        <label>Sort order
            <input type="number" name="sort_order" value="<?= e((string) ($editing['sort_order'] ?? posted('sort_order', '0'))) ?>">
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Active (shown in the menu)
        </label>
        <div class="form-actions">
            <button class="btn btn-gold" type="submit"><?= $editing ? 'Save' : 'Add park' ?></button>
            <?php if ($editing): ?><a class="btn" href="<?= e(url('admin/parks.php')) ?>">Cancel</a><?php endif; ?>
        </div>
    </form>
    <section class="panel">
        <table class="data">
            <thead>
                <tr><th>Park</th><th>Photos</th><th>Menu</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <strong><?= e($row['name']) ?></strong>
                        <div class="muted"><?= e($row['location'] ?? '') ?></div>
                    </td>
                    <td><?= (int) $row['photo_count'] ?></td>
                    <td><?= $row['is_active'] ? 'Yes' : 'Hidden' ?></td>
                    <td class="row-actions">
                        <a href="<?= e(url('admin/parks.php?edit=' . (int) $row['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Remove this park?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="link-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
