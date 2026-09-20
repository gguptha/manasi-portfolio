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
        if ($newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
            $upd->execute([$hash, (int) $_SESSION['admin_id']]);
        }
        flash_set('success', $newPass !== '' ? 'Settings and password saved.' : 'Settings saved.');
        redirect('admin/settings.php');
    }
}

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
