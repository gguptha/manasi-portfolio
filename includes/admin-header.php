<?php
$admin = current_admin();
$adminTitle = $adminTitle ?? 'Admin';
$adminNav = $adminNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> · Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=4">
    <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="admin-body">
<aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('admin/dashboard.php')) ?>">
        <strong><?= e(setting('site_name', 'Manasi')) ?></strong>
        <span>Studio Admin</span>
    </a>
    <nav>
        <a class="<?= $adminNav === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a>
        <p class="nav-label">Library</p>
        <a class="<?= $adminNav === 'photos' ? 'is-active' : '' ?>" href="<?= e(url('admin/photos.php')) ?>">Photographs</a>
        <a class="<?= $adminNav === 'videos' ? 'is-active' : '' ?>" href="<?= e(url('admin/videos.php')) ?>">Videography</a>
        <a class="<?= $adminNav === 'designs' ? 'is-active' : '' ?>" href="<?= e(url('admin/designs.php')) ?>">Design</a>
        <p class="nav-label">Master data</p>
        <a class="<?= $adminNav === 'categories' ? 'is-active' : '' ?>" href="<?= e(url('admin/categories.php')) ?>">Categories</a>
        <a class="<?= $adminNav === 'parks' ? 'is-active' : '' ?>" href="<?= e(url('admin/parks.php')) ?>">National Parks</a>
        <a class="<?= $adminNav === 'years' ? 'is-active' : '' ?>" href="<?= e(url('admin/years.php')) ?>">Years</a>
        <p class="nav-label">Site</p>
        <a class="<?= $adminNav === 'photography' ? 'is-active' : '' ?>" href="<?= e(url('admin/photography.php')) ?>">Photography page</a>
        <a class="<?= $adminNav === 'settings' ? 'is-active' : '' ?>" href="<?= e(url('admin/settings.php')) ?>">Settings</a>
        <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View website</a>
        <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
    </nav>
</aside>
<div class="admin-content">
    <header class="admin-top">
        <h1><?= e($adminTitle) ?></h1>
        <p class="admin-user"><?= e($admin['display_name'] ?? $admin['username'] ?? '') ?></p>
    </header>
    <?php foreach (flash_get() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <div class="admin-main">
