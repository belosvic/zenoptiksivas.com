<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$pageTitle = 'Sepet';
$cart = cartItems($db);

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container-wide">
        <div class="crumbs"><a href="<?= url('index.php') ?>">Anasayfa</a> / Sepet</div>
        <h1 class="display">Sepetim</h1>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container-wide">

        <?php if (empty($cart['items'])): ?>
        <div class="cart-empty">
            <h2 class="display">Sepetiniz boş</h2>
            <p class="muted mt-2">Koleksiyondan bir model seçerek başlayın.</p>
            <a class="btn btn-solid mt-4" href="<?= url('products.php') ?>">Koleksiyonu Keşfet</a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <div>
                <?php foreach ($cart['items'] as $item): ?>
                <div class="cart-line" data-key="<?= e($item['key']) ?>">
                    <a href="<?= url('product-detail.php?slug=' . urlencode($item['slug'])) ?>">
                        <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                    </a>
                    <div>
                        <a href="<?= url('product-detail.php?slug=' . urlencode($item['slug'])) ?>" class="cl-name"><?= e($item['name']) ?></a>
                        <div class="cl-meta">
                            <?php if ($item['frame_color']): ?>Renk: <?= e($item['frame_color']) ?><?php endif; ?>
                            <?php if ($item['frame_color'] && $item['glass_color']): ?> · <?php endif; ?>
                            <?php if ($item['glass_color']): ?>Cam: <?= e($item['glass_color']) ?><?php endif; ?>
                        </div>
                        <div class="qty qty-mini">
                            <button type="button" data-cart-qty="minus" aria-label="Azalt">−</button>
                            <input type="number" value="<?= (int)$item['qty'] ?>" min="1" max="99" aria-label="Adet" readonly>
                            <button type="button" data-cart-qty="plus" aria-label="Artır">+</button>
                        </div>
                    </div>
                    <div class="cl-right">
                        <span class="cl-price"><?= formatPrice($item['line_total']) ?></span>
                        <button type="button" class="line-remove" data-cart-remove>Kaldır</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <aside class="summary-card">
                <h3>Sipariş Özeti</h3>
                <div class="summary-row"><span>Ürün toplamı</span><span><?= formatPrice($cart['subtotal']) ?></span></div>
                <div class="summary-row"><span>Kargo</span><span class="muted">Ödeme adımında</span></div>
                <div class="summary-row total"><span>Toplam</span><span><?= formatPrice($cart['subtotal']) ?></span></div>
                <a class="btn btn-solid" href="<?= url('checkout.php') ?>">Ödemeye Geç</a>
                <a class="link-line" href="<?= url('products.php') ?>" style="margin-top:18px; justify-content:center; display:flex;">Alışverişe devam et</a>
            </aside>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
