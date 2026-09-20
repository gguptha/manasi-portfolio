<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$video = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->execute([$id]);
    $video = $stmt->fetch() ?: null;
    if (!$video) {
        flash_set('error', 'Film not found.');
        redirect('admin/videos.php');
    }
}

$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$parks = db()->query('SELECT id, name FROM parks ORDER BY sort_order, name')->fetchAll();
$years = db()->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll();

if (oversized_post()) {
    flash_set('error', 'The file is larger than the server allows (post_max_size). Prefer a YouTube URL for long films, or raise the limit in cPanel.');
} elseif (is_post()) {
    verify_csrf();
    $title = trim((string) posted('title'));
    $description = trim((string) posted('description'));
    $videoUrl = trim((string) posted('video_url'));
    $duration = trim((string) posted('duration'));
    $categoryId = posted_int('category_id');
    $parkId = posted_int('park_id');
    $yearId = posted_int('year_id');
    $published = posted('is_published') ? 1 : 0;
    $sort = (int) posted('sort_order', 0);

    if ($title === '') {
        flash_set('error', 'A title is required.');
    } elseif ($videoUrl === '' && empty($_FILES['video_file']['name']) && empty($video['video_filename'])) {
        flash_set('error', 'Add a YouTube/Vimeo URL or upload a video file.');
    } else {
        try {
            $thumbName = $video['thumb_filename'] ?? null;
            $videoFile = $video['video_filename'] ?? null;

            if (!empty($_FILES['thumb']['name'])) {
                $thumbMeta = store_uploaded_image($_FILES['thumb'], 'videos');
                if ($thumbName) {
                    foreach (['thumbs', 'originals'] as $folder) {
                        $old = UPLOAD_PATH . '/videos/' . $folder . '/' . $thumbName;
                        if (is_file($old)) {
                            @unlink($old);
                        }
                    }
                }
                $thumbName = $thumbMeta['thumb_filename'];
                $unusedOrig = UPLOAD_PATH . '/videos/originals/' . $thumbMeta['stored_filename'];
                if (is_file($unusedOrig)) {
                    @unlink($unusedOrig);
                }
            }

            if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $origName = (string) $_FILES['video_file']['name'];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['mp4', 'webm', 'mov'], true)) {
                    throw new RuntimeException('Video file must be MP4, WebM, or MOV.');
                }
                ensure_dir(UPLOAD_PATH . '/videos/files');
                $videoFile = random_filename($ext);
                $dest = UPLOAD_PATH . '/videos/files/' . $videoFile;
                if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $dest)) {
                    throw new RuntimeException('Could not store the video file. Shared hosting often limits size — prefer a YouTube URL.');
                }
            }

            $slug = unique_slug('videos', slugify($title), $id ?: null);
            if ($video) {
                db()->prepare(
                    'UPDATE videos SET title=?, slug=?, description=?, category_id=?, park_id=?, year_id=?,
                     video_url=?, video_filename=?, thumb_filename=?, duration=?, is_published=?, sort_order=? WHERE id=?'
                )->execute([$title, $slug, $description, $categoryId, $parkId, $yearId, $videoUrl ?: null, $videoFile, $thumbName, $duration ?: null, $published, $sort, $id]);
                flash_set('success', 'Film updated.');
                redirect('admin/video-form.php?id=' . $id);
            } else {
                db()->prepare(
                    'INSERT INTO videos (title, slug, description, category_id, park_id, year_id, video_url, video_filename, thumb_filename, duration, is_published, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([$title, $slug, $description, $categoryId, $parkId, $yearId, $videoUrl ?: null, $videoFile, $thumbName, $duration ?: null, $published, $sort]);
                flash_set('success', 'Film added.');
                redirect('admin/videos.php');
            }
        } catch (Throwable $ex) {
            flash_set('error', $ex->getMessage());
        }
    }
}

$adminTitle = $video ? 'Edit film' : 'Add film';
$adminNav = 'videos';
require dirname(__DIR__) . '/includes/admin-header.php';
$val = function (string $key, $fallback = '') use ($video) {
    return $_POST[$key] ?? ($video[$key] ?? $fallback);
};
?>
<form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <label class="span-2">Title
        <input type="text" name="title" required value="<?= e((string) $val('title')) ?>">
    </label>
    <label class="span-2">Description
        <textarea name="description" rows="5"><?= e((string) $val('description')) ?></textarea>
    </label>
    <label class="span-2">YouTube or Vimeo URL
        <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=…" value="<?= e((string) $val('video_url')) ?>">
    </label>
    <label>Category
        <select name="category_id">
            <option value="">—</option>
            <?php foreach ($categories as $row): ?>
                <option value="<?= (int) $row['id'] ?>" <?= (string) $val('category_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>National park
        <select name="park_id">
            <option value="">—</option>
            <?php foreach ($parks as $row): ?>
                <option value="<?= (int) $row['id'] ?>" <?= (string) $val('park_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Year
        <select name="year_id">
            <option value="">—</option>
            <?php foreach ($years as $row): ?>
                <option value="<?= (int) $row['id'] ?>" <?= (string) $val('year_id') === (string) $row['id'] ? 'selected' : '' ?>><?= e((string) $row['year']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Duration
        <input type="text" name="duration" placeholder="4:12" value="<?= e((string) $val('duration')) ?>">
    </label>
    <label>Poster / thumbnail
        <input type="file" name="thumb" accept="image/*">
    </label>
    <label>Video file (optional, MP4 preferred)
        <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime">
    </label>
    <label>Sort order
        <input type="number" name="sort_order" value="<?= e((string) $val('sort_order', '0')) ?>">
    </label>
    <label class="check">
        <input type="checkbox" name="is_published" value="1" <?= $val('is_published', 1) ? 'checked' : '' ?>> Published
    </label>
    <div class="form-actions span-2">
        <button class="btn btn-gold" type="submit"><?= $video ? 'Save' : 'Add film' ?></button>
        <a class="btn" href="<?= e(url('admin/videos.php')) ?>">Back</a>
    </div>
    <p class="hint span-2">GoDaddy shared plans often cap uploads at 32–128 MB. Host long films on YouTube or Vimeo and paste the link.</p>
</form>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
