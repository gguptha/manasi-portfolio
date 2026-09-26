<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$keys = [
    'site_name' => 'Site name',
    'photographer_name' => 'Photographer name',
    'tagline' => 'Tagline',
    'about_text' => 'About / intro',
    'contact_email' => 'Contact email',
    'instagram_url' => 'Instagram URL',
    'youtube_url' => 'YouTube URL',
    'footer_text' => 'Footer credit',
];

if (is_post()) {
    verify_csrf();
    $newPass = (string) posted('new_password');
    $newPass2 = (string) posted('new_password_confirm');
    if ($newPass !== '' && strlen($newPass) < 8) {
        flash_set('error', 'New password must be at least 8 characters.');
    } elseif ($newPass !== '' && $newPass !== $newPass2) {
        flash_set('error', 'Password confirmation did not match.');
    } else {
        $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($keys as $key => $label) {
            $stmt->execute([$key, trim((string) posted($key))]);
        }
        $homePhotoId = posted_int('homepage_photo_id') ?? 0;
        if ($homePhotoId > 0) {
            $check = db()->prepare('SELECT id FROM photos WHERE id = ? AND is_published = 1');
            $check->execute([$homePhotoId]);
            if (!$check->fetch()) {
                $homePhotoId = 0;
            }
        }
        $stmt->execute(['homepage_photo_id', $homePhotoId > 0 ? (string) $homePhotoId : '']);
        $stmt->execute(['homepage_text', trim((string) posted('homepage_text'))]);
        $stmt->execute(['homepage_photography_text', trim((string) posted('homepage_photography_text'))]);
        $stmt->execute(['homepage_videography_text', trim((string) posted('homepage_videography_text'))]);
        $stmt->execute(['homepage_design_text', trim((string) posted('homepage_design_text'))]);
        $stmt->execute(['page_videography_text', trim((string) posted('page_videography_text'))]);
        $stmt->execute(['page_design_text', trim((string) posted('page_design_text'))]);
        if ($newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
            $upd->execute([$hash, (int) $_SESSION['admin_id']]);
        }
        flash_set('success', $newPass !== '' ? 'Settings and password saved.' : 'Settings saved.');
        redirect('admin/settings.php');
    }
}

$homePhotos = db()->query('SELECT id, title FROM photos WHERE is_published = 1 ORDER BY title ASC, id DESC')->fetchAll();
$homePhotoId = setting('homepage_photo_id', '');
$homeTextDefault = 'Wildlife stills from the field, made with attention to habitat and light.';
$homeCardDefaults = [
    'homepage_photography_text' => 'Wildlife stills from the field, made with attention to habitat and light.',
    'homepage_videography_text' => 'Field films, behavioural notes, and landscape sequences.',
    'homepage_design_text' => 'Studio stills, composites, and related graphic work.',
];

$adminTitle = 'Settings';
$adminNav = 'settings';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<form method="post" class="form-grid">
    <?= csrf_field() ?>
    <?php foreach ($keys as $key => $label): ?>
        <label class="<?= in_array($key, ['about_text', 'footer_text'], true) ? 'span-2' : '' ?>">
            <?= e($label) ?>
            <?php if ($key === 'about_text' || $key === 'footer_text'): ?>
                <textarea name="<?= e($key) ?>" rows="<?= $key === 'about_text' ? 5 : 3 ?>"><?= e(setting($key)) ?></textarea>
            <?php else: ?>
                <input type="<?= $key === 'contact_email' ? 'email' : 'text' ?>" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>

    <fieldset class="span-2">
        <legend>Homepage</legend>
        <div class="form-grid">
            <label class="span-2">Display photograph
                <select name="homepage_photo_id">
                    <option value="">Latest featured photograph</option>
                    <?php foreach ($homePhotos as $photo): ?>
                        <option value="<?= (int) $photo['id'] ?>" <?= (string) $homePhotoId === (string) $photo['id'] ? 'selected' : '' ?>><?= e($photo['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">Text under the photograph
                <textarea name="homepage_text" rows="5"><?= e(setting('homepage_text', $homeTextDefault)) ?></textarea>
            </label>
            <label class="span-2">Photography
                <textarea name="homepage_photography_text" rows="3"><?= e(setting('homepage_photography_text', $homeCardDefaults['homepage_photography_text'])) ?></textarea>
            </label>
            <label class="span-2">Videography
                <textarea name="homepage_videography_text" rows="3"><?= e(setting('homepage_videography_text', $homeCardDefaults['homepage_videography_text'])) ?></textarea>
            </label>
            <label class="span-2">Design
                <textarea name="homepage_design_text" rows="3"><?= e(setting('homepage_design_text', $homeCardDefaults['homepage_design_text'])) ?></textarea>
            </label>
            <label class="span-2">Videography page description
                <textarea name="page_videography_text" rows="3"><?= e(setting('page_videography_text', 'Field films, behavioural notes, and landscape sequences.')) ?></textarea>
            </label>
            <label class="span-2">Design page description
                <textarea name="page_design_text" rows="3"><?= e(setting('page_design_text', 'Graphic work, composites, and related stills.')) ?></textarea>
            </label>
        </div>
    </fieldset>

    <fieldset class="span-2">
        <legend>Change password</legend>
        <div class="form-grid">
            <label>New password
                <input type="password" name="new_password" autocomplete="new-password">
            </label>
            <label>Confirm password
                <input type="password" name="new_password_confirm" autocomplete="new-password">
            </label>
        </div>
        <p class="hint">Leave blank to keep the current password.</p>
    </fieldset>

    <div class="form-actions span-2">
        <button class="btn btn-gold" type="submit">Save settings</button>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
