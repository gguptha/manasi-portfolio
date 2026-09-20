<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$lockFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'install.lock';
$configFile = INCLUDES_PATH . DIRECTORY_SEPARATOR . 'config.php';
$already = is_file($lockFile) && is_file($configFile);

function install_escape(string $value): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
}

function run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Cannot read ' . basename($path));
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $parts = preg_split('/;\s*$/m', $sql) ?: [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $pdo->exec($part);
    }
}

$errors = [];
$ok = [];
$posted = is_post();

if ($already && $posted) {
    $errors[] = 'This site is already installed. Delete install.lock only if you intend to reinstall.';
}

if ($posted && !$already) {
    verify_csrf();
    $dbHost = trim((string) posted('db_host', 'localhost'));
    $dbName = trim((string) posted('db_name'));
    $dbUser = trim((string) posted('db_user'));
    $dbPass = (string) posted('db_pass');
    $baseUrl = trim((string) posted('base_url', 'auto'));
    $adminUser = trim((string) posted('admin_user', 'admin'));
    $adminName = trim((string) posted('admin_name', 'Administrator'));
    $adminPass = (string) posted('admin_pass');
    $adminPass2 = (string) posted('admin_pass2');

    if ($dbName === '' || $dbUser === '') {
        $errors[] = 'Database name and username are required.';
    }
    if ($adminUser === '' || strlen($adminPass) < 8) {
        $errors[] = 'Admin username is required and the password must be at least 8 characters.';
    }
    if ($adminPass !== $adminPass2) {
        $errors[] = 'Admin passwords do not match.';
    }

    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $ok[] = 'Connected to MySQL.';

            $config = "<?php\n"
                . "define('DB_HOST', " . install_escape($dbHost) . ");\n"
                . "define('DB_NAME', " . install_escape($dbName) . ");\n"
                . "define('DB_USER', " . install_escape($dbUser) . ");\n"
                . "define('DB_PASS', " . install_escape($dbPass) . ");\n"
                . "define('DB_CHARSET', 'utf8mb4');\n"
                . "define('BASE_URL', " . install_escape($baseUrl !== '' ? $baseUrl : 'auto') . ");\n";

            if (file_put_contents($configFile, $config) === false) {
                throw new RuntimeException('Could not write includes/config.php. Create it manually from includes/config.example.php and set folder permissions to 755.');
            }
            $ok[] = 'Wrote includes/config.php.';

            run_sql_file($pdo, ROOT_PATH . '/sql/schema.sql');
            $ok[] = 'Created tables.';
            run_sql_file($pdo, ROOT_PATH . '/sql/seed.sql');
            $ok[] = 'Loaded categories, parks, years, and site settings.';

            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $exists = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
            $exists->execute([$adminUser]);
            if ($exists->fetch()) {
                $pdo->prepare('UPDATE admin_users SET password_hash = ?, display_name = ? WHERE username = ?')
                    ->execute([$hash, $adminName, $adminUser]);
                $ok[] = 'Updated the existing admin user.';
            } else {
                $pdo->prepare('INSERT INTO admin_users (username, password_hash, display_name) VALUES (?,?,?)')
                    ->execute([$adminUser, $hash, $adminName]);
                $ok[] = 'Created admin user.';
            }

            $dirs = [
                'uploads/photos/originals',
                'uploads/photos/thumbs',
                'uploads/designs/originals',
                'uploads/designs/thumbs',
                'uploads/videos/originals',
                'uploads/videos/thumbs',
                'uploads/videos/files',
            ];
            foreach ($dirs as $dir) {
                $full = ROOT_PATH . '/' . $dir;
                if (!is_dir($full) && !mkdir($full, 0755, true) && !is_dir($full)) {
                    throw new RuntimeException('Could not create ' . $dir . '. Set uploads/ to writable (755 or 775).');
                }
            }
            $ok[] = 'Prepared upload folders.';

            file_put_contents($lockFile, date('c') . "\n");
            $already = true;
            $ok[] = 'Installation complete. Delete install.php from the server now.';
        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$exts = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'gd' => extension_loaded('gd'),
    'exif' => function_exists('exif_read_data'),
    'fileinfo' => extension_loaded('fileinfo'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install portfolio</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-body">
<div class="login-card" style="width:min(640px,94vw)">
    <p class="login-kicker">Setup</p>
    <h1>Install</h1>
    <p class="login-sub">Creates the MySQL tables, default master data, and your admin login. Use the database name from GoDaddy cPanel → MySQL Databases.</p>

    <p class="hint">
        PHP <?= e(PHP_VERSION) ?>
        · PDO MySQL <?= $exts['pdo_mysql'] ? 'yes' : 'NO' ?>
        · GD <?= $exts['gd'] ? 'yes' : 'NO' ?>
        · EXIF <?= $exts['exif'] ? 'yes' : 'NO' ?>
        · Fileinfo <?= $exts['fileinfo'] ? 'yes' : 'NO' ?>
    </p>
    <?php if (!$exts['pdo_mysql'] || !$exts['gd'] || !$exts['fileinfo']): ?>
        <p class="flash flash-error">Enable missing extensions in cPanel → Select PHP Version → Extensions before continuing. EXIF is strongly recommended for camera data.</p>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <p class="flash flash-error"><?= e($err) ?></p>
    <?php endforeach; ?>
    <?php foreach ($ok as $line): ?>
        <p class="flash flash-success"><?= e($line) ?></p>
    <?php endforeach; ?>

    <?php if ($already && $ok): ?>
        <p><a class="btn btn-gold" href="admin/index.php">Open admin</a> <a class="btn" href="index.php">View website</a></p>
    <?php elseif ($already): ?>
        <p class="flash flash-success">Already installed. <a href="admin/index.php">Sign in</a> or <a href="index.php">view the site</a>.</p>
    <?php else: ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>MySQL host
                <input name="db_host" value="<?= e((string) posted('db_host', 'localhost')) ?>" required>
            </label>
            <label>Database name
                <input name="db_name" value="<?= e((string) posted('db_name')) ?>" required>
            </label>
            <label>Database user
                <input name="db_user" value="<?= e((string) posted('db_user')) ?>" required>
            </label>
            <label>Database password
                <input type="password" name="db_pass" value="<?= e((string) posted('db_pass')) ?>">
            </label>
            <label>Base URL (use auto, or /folder if not at domain root)
                <input name="base_url" value="<?= e((string) posted('base_url', 'auto')) ?>">
            </label>
            <label>Admin display name
                <input name="admin_name" value="<?= e((string) posted('admin_name', 'Manasi')) ?>">
            </label>
            <label>Admin username
                <input name="admin_user" value="<?= e((string) posted('admin_user', 'admin')) ?>" required>
            </label>
            <label>Admin password
                <input type="password" name="admin_pass" required minlength="8">
            </label>
            <label>Confirm password
                <input type="password" name="admin_pass2" required minlength="8">
            </label>
            <button class="btn btn-gold" type="submit">Install</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
