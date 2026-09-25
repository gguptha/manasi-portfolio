<?php
$nav = nav_master();
$siteName = setting('site_name', 'Manasi');
$tagline = setting('tagline', 'Wildlife Photography');
$pageTitle = $pageTitle ?? $siteName;
$metaDescription = $metaDescription ?? ($tagline . ' by ' . setting('photographer_name', $siteName));
$bodyClass = $bodyClass ?? '';
$currentNav = $currentNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Josefin+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="header-inner">
        <a class="wordmark" href="<?= e(url()) ?>"><?= e($siteName) ?></a>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span>
        </button>
        <nav class="site-nav" aria-label="Primary">
            <div class="nav-item has-dropdown <?= $currentNav === 'categories' ? 'is-current' : '' ?>">
                <a href="<?= e(url('gallery.php?type=category')) ?>">Categories</a>
                <div class="dropdown">
                    <?php foreach ($nav['categories'] as $item): ?>
                        <a href="<?= e(url('gallery.php?type=category&slug=' . urlencode($item['slug']))) ?>"><?= e($item['name']) ?></a>
                    <?php endforeach; ?>
                    <?php if (!$nav['categories']): ?>
                        <span class="dropdown-empty">Add categories in Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nav-item has-dropdown <?= $currentNav === 'parks' ? 'is-current' : '' ?>">
                <a href="<?= e(url('gallery.php?type=park')) ?>">National Parks</a>
                <div class="dropdown">
                    <?php foreach ($nav['parks'] as $item): ?>
                        <a href="<?= e(url('gallery.php?type=park&slug=' . urlencode($item['slug']))) ?>">
                            <?= e($item['name']) ?>
                            <?php if (!empty($item['location'])): ?>
                                <small><?= e($item['location']) ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$nav['parks']): ?>
                        <span class="dropdown-empty">Add parks in Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nav-item has-dropdown <?= $currentNav === 'years' ? 'is-current' : '' ?>">
                <a href="<?= e(url('gallery.php?type=year')) ?>">Year</a>
                <div class="dropdown dropdown-compact">
                    <?php foreach ($nav['years'] as $item): ?>
                        <a href="<?= e(url('gallery.php?type=year&slug=' . urlencode((string) $item['year']))) ?>"><?= e((string) $item['year']) ?></a>
                    <?php endforeach; ?>
                    <?php if (!$nav['years']): ?>
                        <span class="dropdown-empty">Add years in Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nav-item <?= $currentNav === 'videography' ? 'is-current' : '' ?>">
                <a href="<?= e(url('videography.php')) ?>">Videography</a>
            </div>
            <div class="nav-item <?= $currentNav === 'design' ? 'is-current' : '' ?>">
                <a href="<?= e(url('design.php')) ?>">Design</a>
            </div>
        </nav>
    </div>
</header>
<main class="site-main">
