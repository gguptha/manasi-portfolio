<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if (oversized_post()) {
    flash_set('error', 'The upload was larger than the server allows. Compress the image and try again.');
    redirect('admin/photography.php');
}

if (is_post()) {
    verify_csrf();
    try {
        $rows = max(1, min(6, (int) posted('rows', 2)));
        $cols = max(1, min(6, (int) posted('cols', 2)));
        $count = $rows * $cols;

        $rawSlots = posted('slots', []);
        if (!is_array($rawSlots)) {
            $rawSlots = [];
        }
        $requested = [];
        foreach ($rawSlots as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $requested[$id] = $id;
            }
        }
        $valid = $requested ? array_fill_keys(array_keys(photos_by_ids(array_values($requested), false)), true) : [];
        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            $id = (int) ($rawSlots[$i] ?? 0);
            $slots[] = isset($valid[$id]) ? $id : 0;
        }

        $current = photography_landing();
        $previousHero = $current['hero'];
        $heroName = $previousHero;
        $removeHero = posted('remove_hero') === '1';
        $heroFile = $_FILES['hero'] ?? null;
        $hasUpload = is_array($heroFile) && (int) ($heroFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasUpload) {
            $stored = store_uploaded_image($heroFile, 'landing');
            $heroName = $stored['stored_filename'];
        } elseif ($removeHero) {
            $heroName = '';
        }

        save_setting('photo_landing_text', trim((string) posted('hero_text')));
        save_setting('photo_landing_hero', $heroName);
        save_setting('photo_landing_rows', (string) $rows);
        save_setting('photo_landing_cols', (string) $cols);
        save_setting('photo_landing_slots', json_encode($slots) ?: '[]');

        if ($previousHero !== '' && $previousHero !== $heroName) {
            delete_stored_image('landing', $previousHero, $previousHero);
        }

        flash_set('success', 'Photography page saved.');
        redirect('admin/photography.php');
    } catch (Throwable $ex) {
        flash_set('error', $ex->getMessage());
    }
}

$landing = photography_landing();
$library = db()->query(
    'SELECT id, title, is_published, thumb_filename FROM photos ORDER BY title ASC, id DESC'
)->fetchAll();

$adminTitle = 'Photography page';
$adminNav = 'photography';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<form method="post" enctype="multipart/form-data" class="form-stack">
    <?= csrf_field() ?>

    <section class="panel">
        <h2>Featured image</h2>
        <p class="hint">This image fills about 80% of the photography page. The text sits in the remaining space on the right.</p>
        <?php if ($landing['hero'] !== ''): ?>
            <img class="landing-admin-hero" src="<?= e(landing_hero_url($landing['hero'])) ?>" alt="Current featured image">
        <?php endif; ?>
        <div class="form-grid">
            <label class="span-2">Replace image
                <input type="file" name="hero" accept="image/jpeg,image/png,image/webp,image/gif">
            </label>
            <label class="span-2">Text beside the image
                <textarea name="hero_text" rows="8"><?= e($landing['text']) ?></textarea>
            </label>
            <?php if ($landing['hero'] !== ''): ?>
                <label class="check span-2">
                    <input type="checkbox" name="remove_hero" value="1">
                    Remove the current image
                </label>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <h2>Image grid</h2>
        <p class="hint">Choose how many rows and columns to show under the featured image, then pick a photograph for each cell. Photographs are added under Photographs.</p>
        <div class="form-grid">
            <label>Rows
                <input id="landing-rows" type="number" name="rows" min="1" max="6" value="<?= (int) $landing['rows'] ?>">
            </label>
            <label>Columns
                <input id="landing-cols" type="number" name="cols" min="1" max="6" value="<?= (int) $landing['cols'] ?>">
            </label>
        </div>
        <div id="landing-slots" class="slot-grid" style="--slot-cols: <?= (int) $landing['cols'] ?>">
            <?php foreach ($landing['slots'] as $index => $photoId): ?>
                <?php
                $rowNum = intdiv($index, $landing['cols']) + 1;
                $colNum = ($index % $landing['cols']) + 1;
                ?>
                <label>
                    <span class="slot-label">Row <?= $rowNum ?>, column <?= $colNum ?></span>
                    <select name="slots[]">
                        <option value="0">Empty</option>
                        <?php foreach ($library as $photo): ?>
                            <option value="<?= (int) $photo['id'] ?>" <?= (int) $photoId === (int) $photo['id'] ? 'selected' : '' ?>>
                                <?= e($photo['title']) ?><?= $photo['is_published'] ? '' : ' (draft)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-gold" type="submit">Save photography page</button>
        <a class="btn" href="<?= e(url('photography.php')) ?>" target="_blank" rel="noopener">View page</a>
    </div>
</form>

<template id="slot-template">
    <label>
        <span class="slot-label"></span>
        <select name="slots[]">
            <option value="0">Empty</option>
            <?php foreach ($library as $photo): ?>
                <option value="<?= (int) $photo['id'] ?>"><?= e($photo['title']) ?><?= $photo['is_published'] ? '' : ' (draft)' ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</template>
<script>
(function () {
    var rowsInput = document.getElementById('landing-rows');
    var colsInput = document.getElementById('landing-cols');
    var grid = document.getElementById('landing-slots');
    var template = document.getElementById('slot-template');
    if (!rowsInput || !colsInput || !grid || !template) return;

    function clamp(input) {
        var n = parseInt(input.value, 10);
        if (!n || n < 1) n = 1;
        if (n > 6) n = 6;
        return n;
    }

    function rebuild() {
        var rows = clamp(rowsInput);
        var cols = clamp(colsInput);
        var existing = Array.prototype.map.call(grid.querySelectorAll('select'), function (sel) {
            return sel.value;
        });
        grid.innerHTML = '';
        grid.style.setProperty('--slot-cols', String(cols));
        var total = rows * cols;
        for (var i = 0; i < total; i++) {
            var node = template.content.cloneNode(true);
            var row = Math.floor(i / cols) + 1;
            var col = (i % cols) + 1;
            node.querySelector('.slot-label').textContent = 'Row ' + row + ', column ' + col;
            var select = node.querySelector('select');
            if (existing[i]) select.value = existing[i];
            grid.appendChild(node);
        }
    }

    rowsInput.addEventListener('change', rebuild);
    colsInput.addEventListener('change', rebuild);
})();
</script>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
