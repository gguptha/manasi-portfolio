<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$photo = null;
if ($id) {
    $photo = photo_by_id($id, false);
    if (!$photo) {
        flash_set('error', 'Photograph not found.');
        redirect('admin/photos.php');
    }
}

$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$parks = db()->query('SELECT id, name FROM parks ORDER BY sort_order, name')->fetchAll();
$years = db()->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll();

if (oversized_post()) {
    flash_set('error', 'The file is larger than the server allows (post_max_size). Export a smaller JPEG or raise the limit in .user.ini / cPanel.');
} elseif (is_post()) {
    verify_csrf();
    $title = trim((string) posted('title'));
    $description = trim((string) posted('description'));
    $categoryId = posted_int('category_id');
    $parkId = posted_int('park_id');
    $yearId = posted_int('year_id');
    $featured = posted('is_featured') ? 1 : 0;
    $published = posted('is_published') ? 1 : 0;
    $sort = (int) posted('sort_order', 0);

    if (!$categoryId) {
        flash_set('error', 'Please choose a category.');
    } elseif (!$photo && empty($_FILES['image']['name'])) {
        flash_set('error', 'Please choose a photograph to upload.');
    } else {
        try {
            $fileMeta = null;
            if (!empty($_FILES['image']['name'])) {
                $fileMeta = store_uploaded_image($_FILES['image'], 'photos');
                if (!$yearId && !empty($fileMeta['exif']['taken_at'])) {
                    $yearId = find_year_id_from_date($fileMeta['exif']['taken_at']);
                }
            }

            if ($title === '') {
                $sourceName = (string) ($fileMeta['original_filename'] ?? ($photo['original_filename'] ?? ''));
                $title = trim(str_replace(['_', '-'], ' ', pathinfo($sourceName, PATHINFO_FILENAME)));
                if ($title === '') {
                    $title = 'Untitled';
                }
            }
            $title = mb_substr($title, 0, 200);
            if ($description === '') {
                $description = null;
            }

            $slug = unique_slug('photos', slugify($title), $id ?: null);

            if ($photo) {
                if ($fileMeta) {
                    delete_stored_image('photos', $photo['stored_filename'], $photo['thumb_filename']);
                    $exif = $fileMeta['exif'];
                    db()->prepare(
                        'UPDATE photos SET title=?, slug=?, description=?, category_id=?, park_id=?, year_id=?,
                         original_filename=?, stored_filename=?, thumb_filename=?, mime_type=?, file_size=?,
                         width=?, height=?, camera_make=?, camera_model=?, lens=?, focal_length=?, aperture=?,
                         shutter_speed=?, iso=?, taken_at=?, gps_lat=?, gps_lng=?, exif_json=?,
                         is_featured=?, is_published=?, sort_order=? WHERE id=?'
                    )->execute([
                        $title, $slug, $description, $categoryId, $parkId, $yearId,
                        $fileMeta['original_filename'], $fileMeta['stored_filename'], $fileMeta['thumb_filename'],
                        $fileMeta['mime_type'], $fileMeta['file_size'], $fileMeta['width'], $fileMeta['height'],
                        $exif['camera_make'], $exif['camera_model'], $exif['lens'], $exif['focal_length'],
                        $exif['aperture'], $exif['shutter_speed'], $exif['iso'], $exif['taken_at'],
                        $exif['gps_lat'], $exif['gps_lng'], $exif['exif_json'],
                        $featured, $published, $sort, $id,
                    ]);
                } else {
                    db()->prepare(
                        'UPDATE photos SET title=?, slug=?, description=?, category_id=?, park_id=?, year_id=?,
                         is_featured=?, is_published=?, sort_order=? WHERE id=?'
                    )->execute([$title, $slug, $description, $categoryId, $parkId, $yearId, $featured, $published, $sort, $id]);
                }
                flash_set('success', 'Photograph updated.');
                redirect('admin/photo-form.php?id=' . $id);
            } else {
                $exif = $fileMeta['exif'];
                db()->prepare(
                    'INSERT INTO photos (title, slug, description, category_id, park_id, year_id,
                     original_filename, stored_filename, thumb_filename, mime_type, file_size, width, height,
                     camera_make, camera_model, lens, focal_length, aperture, shutter_speed, iso, taken_at,
                     gps_lat, gps_lng, exif_json, is_featured, is_published, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $title, $slug, $description, $categoryId, $parkId, $yearId,
                    $fileMeta['original_filename'], $fileMeta['stored_filename'], $fileMeta['thumb_filename'],
                    $fileMeta['mime_type'], $fileMeta['file_size'], $fileMeta['width'], $fileMeta['height'],
                    $exif['camera_make'], $exif['camera_model'], $exif['lens'], $exif['focal_length'],
                    $exif['aperture'], $exif['shutter_speed'], $exif['iso'], $exif['taken_at'],
                    $exif['gps_lat'], $exif['gps_lng'], $exif['exif_json'],
                    $featured, $published, $sort,
                ]);
                $newId = (int) db()->lastInsertId();
                flash_set('success', 'Photograph uploaded. EXIF was read from the file where available.');
                redirect('admin/photo-form.php?id=' . $newId);
            }
        } catch (Throwable $ex) {
            flash_set('error', $ex->getMessage());
        }
    }
}

$adminTitle = $photo ? 'Edit photograph' : 'Upload photograph';
$adminNav = 'photos';
require dirname(__DIR__) . '/includes/admin-header.php';

$val = function (string $key, $fallback = '') use ($photo) {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    return $photo[$key] ?? $fallback;
};
?>
<form method="post" enctype="multipart/form-data" class="form-grid media-form">
    <?= csrf_field() ?>
    <div class="span-2 panel form-stack">
        <label>Title
            <input type="text" name="title" value="<?= e((string) $val('title')) ?>">
        </label>
        <label>Description
            <textarea name="description" rows="6" placeholder="Field note, species, behaviour, light…"><?= e((string) $val('description')) ?></textarea>
        </label>
        <div class="form-grid">
            <label>Category
                <select name="category_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($categories as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= (string) $val('category_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>National park
                <select name="park_id">
                    <option value="">— Optional —</option>
                    <?php foreach ($parks as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= (string) $val('park_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Year
                <select name="year_id">
                    <option value="">— Optional —</option>
                    <?php foreach ($years as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= (string) $val('year_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e((string) $row['year']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sort order
                <input type="number" name="sort_order" value="<?= e((string) $val('sort_order', '0')) ?>">
            </label>
        </div>
        <div class="check-row">
            <label class="check"><input type="checkbox" name="is_published" value="1" <?= $val('is_published', 1) ? 'checked' : '' ?>> Published</label>
            <label class="check"><input type="checkbox" name="is_featured" value="1" <?= $val('is_featured', 0) ? 'checked' : '' ?>> Featured on home</label>
        </div>
        <label>
            <?= $photo ? 'Replace image (optional)' : 'Photograph' ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?= $photo ? '' : 'required' ?>>
        </label>
        <p class="hint">Category and the image are required. Title, description, national park, and year can be left blank. Portrait and landscape photographs both keep their shape.</p>
        <div class="form-actions">
            <button class="btn btn-gold" type="submit"><?= $photo ? 'Save changes' : 'Upload' ?></button>
            <a class="btn" href="<?= e(url('admin/photos.php')) ?>">Back to list</a>
        </div>
    </div>
    <aside class="panel">
        <?php if ($photo): ?>
            <img class="preview" src="<?= e(photo_thumb($photo)) ?>" alt="">
            <h2>Stored EXIF</h2>
            <dl class="mini-dl">
                <div><dt>File</dt><dd><?= e($photo['original_filename']) ?></dd></div>
                <div><dt>Size</dt><dd><?= $photo['width'] ?> × <?= $photo['height'] ?> · <?= number_format(((int) $photo['file_size']) / 1024, 0) ?> KB</dd></div>
                <div><dt>Camera</dt><dd><?= e(trim(($photo['camera_make'] ?? '') . ' ' . ($photo['camera_model'] ?? '')) ?: '—') ?></dd></div>
                <div><dt>Lens</dt><dd><?= e($photo['lens'] ?: '—') ?></dd></div>
                <div><dt>Exposure</dt><dd><?= e(trim(implode(' · ', array_filter([$photo['focal_length'], $photo['aperture'], $photo['shutter_speed'], $photo['iso'] ? 'ISO ' . $photo['iso'] : null]))) ?: '—') ?></dd></div>
                <div><dt>Captured</dt><dd><?= e($photo['taken_at'] ?: '—') ?></dd></div>
            </dl>
            <p><a class="text-link" href="<?= e(url('photo.php?id=' . (int) $photo['id'])) ?>" target="_blank" rel="noopener">Open public page</a></p>
        <?php else: ?>
            <p class="muted">After upload, extracted EXIF will appear here so you can confirm camera data.</p>
        <?php endif; ?>
    </aside>
</form>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
