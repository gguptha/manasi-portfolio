<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$item = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM designs WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch() ?: null;
    if (!$item) {
        flash_set('error', 'Design work not found.');
        redirect('admin/designs.php');
    }
}

$years = db()->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll();

if (oversized_post()) {
    flash_set('error', 'The file is larger than the server allows (post_max_size). Export a smaller image or raise the limit in .user.ini / cPanel.');
} elseif (is_post()) {
    verify_csrf();
    $title = trim((string) posted('title'));
    $description = trim((string) posted('description'));
    $yearId = posted_int('year_id');
    $published = posted('is_published') ? 1 : 0;
    $sort = (int) posted('sort_order', 0);

    if ($title === '') {
        flash_set('error', 'A title is required.');
    } elseif (!$item && empty($_FILES['image']['name'])) {
        flash_set('error', 'Please upload an image.');
    } else {
        try {
            $fileMeta = null;
            if (!empty($_FILES['image']['name'])) {
                $fileMeta = store_uploaded_image($_FILES['image'], 'designs');
            }
            $slug = unique_slug('designs', slugify($title), $id ?: null);

            if ($item) {
                if ($fileMeta) {
                    delete_stored_image('designs', $item['stored_filename'], $item['thumb_filename']);
                    db()->prepare(
                        'UPDATE designs SET title=?, slug=?, description=?, year_id=?, original_filename=?, stored_filename=?,
                         thumb_filename=?, mime_type=?, file_size=?, width=?, height=?, is_published=?, sort_order=? WHERE id=?'
                    )->execute([
                        $title, $slug, $description, $yearId, $fileMeta['original_filename'], $fileMeta['stored_filename'],
                        $fileMeta['thumb_filename'], $fileMeta['mime_type'], $fileMeta['file_size'], $fileMeta['width'],
                        $fileMeta['height'], $published, $sort, $id,
                    ]);
                } else {
                    db()->prepare(
                        'UPDATE designs SET title=?, slug=?, description=?, year_id=?, is_published=?, sort_order=? WHERE id=?'
                    )->execute([$title, $slug, $description, $yearId, $published, $sort, $id]);
                }
                flash_set('success', 'Design work updated.');
                redirect('admin/design-form.php?id=' . $id);
            } else {
                db()->prepare(
                    'INSERT INTO designs (title, slug, description, year_id, original_filename, stored_filename, thumb_filename, mime_type, file_size, width, height, is_published, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $title, $slug, $description, $yearId, $fileMeta['original_filename'], $fileMeta['stored_filename'],
                    $fileMeta['thumb_filename'], $fileMeta['mime_type'], $fileMeta['file_size'], $fileMeta['width'],
                    $fileMeta['height'], $published, $sort,
                ]);
                flash_set('success', 'Design work uploaded.');
                redirect('admin/designs.php');
            }
        } catch (Throwable $ex) {
            flash_set('error', $ex->getMessage());
        }
    }
}

$adminTitle = $item ? 'Edit design work' : 'Add design work';
$adminNav = 'designs';
require dirname(__DIR__) . '/includes/admin-header.php';
$val = function (string $key, $fallback = '') use ($item) {
    return $_POST[$key] ?? ($item[$key] ?? $fallback);
};
?>
<form method="post" enctype="multipart/form-data" class="form-grid media-form">
    <?= csrf_field() ?>
    <div class="span-2 panel form-stack">
        <label>Title
            <input type="text" name="title" required value="<?= e((string) $val('title')) ?>">
        </label>
        <label>Description
            <textarea name="description" rows="6"><?= e((string) $val('description')) ?></textarea>
        </label>
        <div class="form-grid">
            <label>Year
                <select name="year_id">
                    <option value="">—</option>
                    <?php foreach ($years as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= (string) $val('year_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e((string) $row['year']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sort order
                <input type="number" name="sort_order" value="<?= e((string) $val('sort_order', '0')) ?>">
            </label>
        </div>
        <label class="check">
            <input type="checkbox" name="is_published" value="1" <?= $val('is_published', 1) ? 'checked' : '' ?>> Published
        </label>
        <label>
            <?= $item ? 'Replace image (optional)' : 'Image' ?>
            <input type="file" name="image" accept="image/*" <?= $item ? '' : 'required' ?>>
        </label>
        <div class="form-actions">
            <button class="btn btn-gold" type="submit"><?= $item ? 'Save' : 'Upload' ?></button>
            <a class="btn" href="<?= e(url('admin/designs.php')) ?>">Back</a>
        </div>
    </div>
    <?php if ($item): ?>
        <aside class="panel">
            <img class="preview" src="<?= e(design_thumb($item)) ?>" alt="">
        </aside>
    <?php endif; ?>
</form>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
