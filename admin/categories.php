<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if (is_post()) {
    verify_csrf();
    $action = (string) posted('action');
    if ($action === 'delete') {
        $id = (int) posted('id');
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash_set('success', 'Category removed. Photographs keep their files; the category link is cleared.');
        redirect('admin/categories.php');
    }

    $name = trim((string) posted('name'));
    $description = trim((string) posted('description'));
    $sort = (int) posted('sort_order', 0);
    $active = posted('is_active') ? 1 : 0;
    $id = posted_int('id');
    if ($name === '') {
        flash_set('error', 'Name is required.');
    } else {
        $slug = unique_slug('categories', slugify($name), $id);
        if ($id) {
            db()->prepare('UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $slug, $description, $sort, $active, $id]);
            flash_set('success', 'Category updated.');
        } else {
            db()->prepare('INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?,?,?,?,?)')
                ->execute([$name, $slug, $description, $sort, $active]);
            flash_set('success', 'Category added.');
        }
        redirect('admin/categories.php');
    }
}

$rows = db()->query(
    'SELECT c.*, COUNT(p.id) AS photo_count
     FROM categories c
     LEFT JOIN photos p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.name ASC'
)->fetchAll();

$adminTitle = 'Categories';
$adminNav = 'categories';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="split">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : '' ?>">
        <h2><?= $editing ? 'Edit category' : 'Add category' ?></h2>
        <label>Name
            <input type="text" name="name" required value="<?= e($editing['name'] ?? (string) posted('name')) ?>">
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
            <button class="btn btn-gold" type="submit"><?= $editing ? 'Save' : 'Add category' ?></button>
            <?php if ($editing): ?><a class="btn" href="<?= e(url('admin/categories.php')) ?>">Cancel</a><?php endif; ?>
        </div>
    </form>
    <section class="panel">
        <table class="data">
            <thead>
                <tr><th>Name</th><th>Photos</th><th>Menu</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <strong><?= e($row['name']) ?></strong>
                        <div class="muted">/<?= e($row['slug']) ?></div>
                    </td>
                    <td><?= (int) $row['photo_count'] ?></td>
                    <td><?= $row['is_active'] ? 'Yes' : 'Hidden' ?></td>
                    <td class="row-actions">
                        <a href="<?= e(url('admin/categories.php?edit=' . (int) $row['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Remove this category?');">
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
