<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$pageTitle = 'Ödeme';
$cart = cartItems($db);

if (empty($cart['items'])) {
    header('Location: ' . url('cart.php'));
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['phone']   = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');

    if (mb_strlen($old['name'], 'UTF-8') < 3) $errors['name'] = 'Ad soyad en az 3 karakter olmalı.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Geçerli bir e-posta girin.';
    if (strlen(preg_replace('/\D/', '', $old['phone'])) < 10) $errors['phone'] = 'Geçerli bir telefon girin.';
    if (mb_strlen($old['address'], 'UTF-8') < 10) $errors['address'] = 'Teslimat adresi çok kısa.';

    if (!$errors) {
        try {
            $db->beginTransaction();

            $orderNo = 'ZEN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $db->prepare("
                INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$orderNo, $old['name'], $old['email'], $old['phone'], $old['address'], $cart['subtotal']]);
            $orderId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, variation_id, product_name, variation_details, quantity, unit_price)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart['items'] as $item) {
                $details = trim(($item['frame_color'] ? 'Renk: ' . $item['frame_color'] . ' ' : '') .
                                ($item['glass_color'] ? 'Cam: ' . $item['glass_color'] : ''));
                $stmtItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['variation_id'],
                    $item['name'],
                    $details ?: null,
                    $item['qty'],
                    $item['unit_price'],
                ]);

                // Stok düş
                if ($item['variation_id']) {
                    $db->prepare("UPDATE product_variations SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?")
                       ->execute([$item['qty'], $item['variation_id']]);
                }
            }

            $db->commit();
            $_SESSION['cart'] = [];

            header('Location: ' . url('order-success.php?no=' . urlencode($orderNo)));
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors['general'] = 'Sipariş oluşturulamadı: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container-wide">
        <div class="crumbs"><a href="<?= url('cart.php') ?>">Sepet</a> / Ödeme</div>
        <h1 class="display">Ödeme</h1>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container-wide checkout-layout">

        <form method="post" novalidate>
            <?php if (!empty($errors['general'])): ?>
                <div style="border:1px solid #b03a2e; color:#b03a2e; padding:16px 20px; margin-bottom:24px; font-size:14px;"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="field full">
                    <label for="f-name">Ad Soyad *</label>
                    <input id="f-name" name="name" value="<?= e($old['name']) ?>" autocomplete="name">
                    <?php if (!empty($errors['name'])): ?><span class="err"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="f-email">E-posta *</label>
                    <input id="f-email" name="email" type="email" value="<?= e($old['email']) ?>" autocomplete="email">
                    <?php if (!empty($errors['email'])): ?><span class="err"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="f-phone">Telefon *</label>
                    <input id="f-phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" autocomplete="tel">
                    <?php if (!empty($errors['phone'])): ?><span class="err"><?= e($errors['phone']) ?></span><?php endif; ?>
                </div>
                <div class="field full">
                    <label for="f-address">Teslimat Adresi *</label>
                    <textarea id="f-address" name="address" rows="4"><?= e($old['address']) ?></textarea>
                    <?php if (!empty($errors['address'])): ?><span class="err"><?= e($errors['address']) ?></span><?php endif; ?>
                </div>
            </div>

            <button class="btn btn-solid" type="submit" style="margin-top:28px; width:100%;">Siparişi Onayla — <?= formatPrice($cart['subtotal']) ?></button>
            <p class="muted" style="font-size:12px; margin-top:14px;">
                Siparişiniz onaylandıktan sonra ekibimiz WhatsApp veya telefon ile sizinle iletişime geçer.
            </p>
        </form>

        <aside class="summary-card">
            <h3>Sipariş Özeti</h3>
            <?php foreach ($cart['items'] as $item): ?>
            <div class="checkout-mini-line">
                <span><?= e($item['name']) ?> × <?= (int)$item['qty'] ?></span>
                <span><?= formatPrice($item['line_total']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="summary-row total"><span>Toplam</span><span><?= formatPrice($cart['subtotal']) ?></span></div>
        </aside>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
