<?php
require_once __DIR__ . '/site-init.php';
$S = getSettings($db);
$cartCount = cartCount($db);
$navActive = $navActive ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($pageTitle ?? $S->site_title) . ' — Zen Optik') ?></title>
    <meta name="description" content="<?= e($pageDesc ?? $S->site_description ?? '') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Manrope:wght@400;500;600;700;800&display=swap&subset=latin-ext" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<header class="nav" id="siteNav" data-dark-page="<?= (int)($darkHero ?? 0) ?>">
    <div class="container-wide nav-inner">
<a href="<?= url('index.php') ?>" class="brand" aria-label="Zen Optik ana sayfa">
        <?php if (!empty($S->site_logo) && file_exists(__DIR__ . '/../uploads/' . $S->site_logo)): ?>
        <img class="brand-logo" src="<?= url('uploads/' . e($S->site_logo)) ?>" alt="Zen Optik">
        <?php else: ?>
        <span class="brand-mark"><span></span></span>
        Zen Optik
        <?php endif; ?>
    </a>

        <nav aria-label="Ana menü">
            <ul class="nav-links" id="navLinks">
                <li><a href="<?= url('index.php') ?>">Anasayfa</a></li>
                <li><a href="<?= url('products.php') ?>">Koleksiyon</a></li>
                <li><a href="<?= url('index.php#hikaye') ?>">Hikaye</a></li>
                <li><a href="<?= url('index.php#hizmet') ?>">Hizmet</a></li>
                <li><a href="<?= url('index.php#iletisim') ?>">İletişim</a></li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="<?= url('cart.php') ?>" class="cart-btn" aria-label="Sepet">
                <span>Sepet</span>
                <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
            </a>
            <button class="nav-burger" id="navBurger" aria-label="Menüyü aç" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
