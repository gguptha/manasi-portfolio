<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM years WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if (is_post()) {
    verify_csrf();
    $action = (string) posted('action');
    if ($action === 'delete') {
        db()->prepare('DELETE FROM years WHERE id = ?')->execute([(int) posted('id')]);
        flash_set('success', 'Year removed.');
        redirect('admin/years.php');
    }

    $year = (int) posted('year');
    $description = trim((string) posted('description'));
    $active = posted('is_active') ? 1 : 0;
    $id = posted_int('id');
    if ($year < 1980 || $year > 2100) {
        flash_set('error', 'Enter a valid year.');
    } else {
        try {
            if ($id) {
                db()->prepare('UPDATE years SET year=?, description=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$year, $description, $year, $active, $id]);
                flash_set('success', 'Year updated.');
            } else {
                db()->prepare('INSERT INTO years (year, description, sort_order, is_active) VALUES (?,?,?,?)')
                    ->execute([$year, $description, $year, $active]);
                flash_set('success', 'Year added.');
            }
            redirect('admin/years.php');
        } catch (PDOException $ex) {
            flash_set('error', 'That year already exists.');
        }
    }
}

$rows = db()->query(
    'SELECT y.*, COUNT(p.id) AS photo_count
     FROM years y
     LEFT JOIN photos p ON p.year_id = y.id
     GROUP BY y.id
     ORDER BY y.year DESC'
)->fetchAll();

$adminTitle = 'Years';
$adminNav = 'years';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="split">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : '' ?>">
        <h2><?= $editing ? 'Edit year' : 'Add year' ?></h2>
        <label>Year
            <input type="number" name="year" required min="1980" max="2100" value="<?= e((string) ($editing['year'] ?? posted('year', (string) date('Y')))) ?>">
        </label>
        <label>Note
            <input type="text" name="description" value="<?= e($editing['description'] ?? (string) posted('description')) ?>">
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Active (shown in the menu)
        </label>
        <div class="form-actions">
            <button class="btn btn-gold" type="submit"><?= $editing ? 'Save' : 'Add year' ?></button>
            <?php if ($editing): ?><a class="btn" href="<?= e(url('admin/years.php')) ?>">Cancel</a><?php endif; ?>
        </div>
    </form>
    <section class="panel">
        <table class="data">
            <thead>
                <tr><th>Year</th><th>Photos</th><th>Menu</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?= e((string) $row['year']) ?></strong></td>
                    <td><?= (int) $row['photo_count'] ?></td>
                    <td><?= $row['is_active'] ? 'Yes' : 'Hidden' ?></td>
                    <td class="row-actions">
                        <a href="<?= e(url('admin/years.php?edit=' . (int) $row['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Remove this year?');">
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
