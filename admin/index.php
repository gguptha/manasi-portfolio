<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

$error = '';
if (is_post()) {
    verify_csrf();
    $username = trim((string) posted('username'));
    $password = (string) posted('password');
    if ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } elseif (!attempt_login($username, $password)) {
        $error = 'Those credentials were not recognised.';
    } else {
        redirect('admin/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Outfit:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="login-body">
    <form class="login-card" method="post" action="">
        <p class="login-kicker">Studio</p>
        <h1><?= e(setting('site_name', 'Manasi')) ?></h1>
        <p class="login-sub">Sign in to manage photographs and master data.</p>
        <?php if ($error): ?><p class="flash flash-error"><?= e($error) ?></p><?php endif; ?>
        <?= csrf_field() ?>
        <label>
            Username
            <input type="text" name="username" autocomplete="username" required value="<?= e((string) posted('username')) ?>">
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn btn-gold">Sign in</button>
        <p class="login-back"><a href="<?= e(url()) ?>">← Website</a></p>
    </form>
</body>
</html>
