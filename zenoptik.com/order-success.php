<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$pageTitle = 'Sipariş Onaylandı';
$no = trim($_GET['no'] ?? '');

$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
$stmt->execute([$no]);
$order = $stmt->fetch();

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container-wide">
        <div class="crumbs"><a href="<?= url('index.php') ?>">Anasayfa</a> / Sipariş</div>
        <h1 class="display">Teşekkürler</h1>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container-wide" style="max-width:640px;">
        <?php if ($order): ?>
            <div class="summary-card" style="position:static;">
                <h3>Siparişiniz Alındı</h3>
                <div class="summary-row"><span>Sipariş No</span><span style="font-weight:700;"><?= e($order->order_number) ?></span></div>
                <div class="summary-row"><span>Alıcı</span><span><?= e($order->customer_name) ?></span></div>
                <div class="summary-row"><span>Tutar</span><span style="font-weight:700;"><?= formatPrice($order->total_amount) ?></span></div>
                <div class="summary-row"><span>Durum</span><span><?= e($order->status === 'pending' ? 'Onay bekliyor' : e($order->status)) ?></span></div>
                <p class="muted" style="font-size:13px; margin-top:20px;">
                    Sipariş numaranızı saklayın. Ekibimiz en kısa sürede sizinle iletişime geçecek.
                    Sorularınız için WhatsApp: <?= e($S->whatsapp_phone) ?>
                </p>
            </div>
        <?php else: ?>
            <p class="muted">Sipariş bulunamadı.</p>
        <?php endif; ?>
        <a class="btn mt-4" href="<?= url('products.php') ?>">Alışverişe Devam Et</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
