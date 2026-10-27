<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$slug = trim($_GET['slug'] ?? '');

// ---- Ürün ----
$stmt = $db->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name,
           ps.ekartman, ps.kopru, ps.sap_uzunlugu, ps.frame_shape, ps.frame_material,
           ps.glass_feature, ps.suitable_face_shapes
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_specs ps ON ps.product_id = p.id
    WHERE p.slug = ? AND p.status = 'active'
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Ürün bulunamadı';
    require __DIR__ . '/includes/header.php';
    echo '<section class="page-head"><div class="container-wide"><h1 class="display">Ürün bulunamadı</h1><p class="mt-2"><a class="link-line" href="' . url('products.php') . '">Koleksiyona dön</a></p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product->name;
$pageDesc  = mb_substr(trim((string)$product->description), 0, 150, 'UTF-8');

// ---- Galeri ----
$galleryStmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC");
$galleryStmt->execute([$product->id]);
$gallery = array_column($galleryStmt->fetchAll(), 'image_path');
array_unshift($gallery, $product->main_image); // ana görsel her zaman ilk
$gallery = array_values(array_filter(array_unique($gallery)));

// ---- Varyasyonlar ----
$varStmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY id ASC");
$varStmt->execute([$product->id]);
$variations = $varStmt->fetchAll();

// ---- Benzer ürünler ----
$related = [];
if ($product->category_id) {
    $stmtR = $db->prepare("
        SELECT p.*, b.name AS brand_name FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.status = 'active' AND p.id != ? AND p.category_id = ?
        ORDER BY p.created_at DESC LIMIT 3
    ");
    $stmtR->execute([$product->id, $product->category_id]);
    $related = $stmtR->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<section style="padding-top:var(--nav-h);">
    <div class="container-wide detail-layout" style="padding-top:clamp(30px,5vw,60px);">

        <!-- Galeri -->
        <div class="detail-gallery">
            <div class="dg-main" id="dgMain">
                <img id="dgImage" src="<?= url('uploads/products/' . e($product->main_image ?: '')) ?>"
                     onerror="this.src='<?= url('assets/img/frame-placeholder.svg') ?>'"
                     alt="<?= e($product->name) ?>">
            </div>
            <?php if (count($gallery) > 1): ?>
            <div class="dg-thumbs">
                <?php foreach ($gallery as $i => $img): ?>
                <button class="<?= $i === 0 ? 'on' : '' ?>" data-thumb="<?= url('uploads/products/' . e($img)) ?>"
                        aria-label="Görsel <?= $i + 1 ?>">
                    <img src="<?= url('uploads/products/' . e($img)) ?>" alt="" loading="lazy">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bilgi -->
        <div class="detail-info">
            <div class="crumbs" style="font-size:11px; letter-spacing:.22em; text-transform:uppercase; color:var(--ink-mute); margin-bottom:14px;">
                <a href="<?= url('products.php') ?>">Koleksiyon</a>
                <?php if ($product->category_name): ?> / <a href="<?= url('products.php?cat=' . urlencode($product->category_name)) ?>"><?= e($product->category_name) ?></a><?php endif; ?>
            </div>

            <?php if ($product->brand_name): ?><div class="pc-brand"><?= e($product->brand_name) ?></div><?php endif; ?>
            <h1><?= e($product->name) ?></h1>

            <div class="detail-price">
                <?php if (hasDiscount($product)): ?>
                    <del><?= formatPrice($product->base_price) ?></del>
                    <span style="color:var(--gold-deep);"><?= formatPrice($product->discount_price) ?></span>
                    <span class="save">%<?= round((1 - $product->discount_price / $product->base_price) * 100) ?> indirim</span>
                <?php else: ?>
                    <span><?= formatPrice($product->base_price) ?></span>
                <?php endif; ?>
            </div>

            <?php if (trim((string)$product->description)): ?>
            <p class="detail-desc"><?= e($product->description) ?></p>
            <?php endif; ?>

            <?php if ($variations): ?>
            <div>
                <div class="opt-label">
                    <span>Çerçeve Rengi</span>
                    <span id="selVarName"></span>
                </div>
                <div class="variant-row" id="variantRow">
                    <?php foreach ($variations as $v): $hex = colorHex($v->frame_color); ?>
                    <button type="button"
                            class="variant-chip <?= (int)$v->stock_quantity <= 0 ? 'out' : ((int)$v->stock_quantity <= 3 ? 'low' : '') ?>"
                            data-vid="<?= (int)$v->id ?>"
                            data-swatch="<?= e($v->frame_color) ?>"
                            data-name="<?= e($v->frame_color) ?>"
                            data-stock="<?= (int)$v->stock_quantity ?>"
                            data-img="<?= $v->variation_image ? url('uploads/products/' . e($v->variation_image)) : '' ?>">
                        <span class="swatch" style="background:<?= $hex ?: 'var(--ink-mute)' ?>;"></span>
                        <?= e($v->frame_color) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <span id="stockNote" style="font-size:12px; color:var(--ink-mute); display:block; min-height:18px;"></span>
            </div>
            <?php else: ?>
            <input type="hidden" id="selectedVariation" value="">
            <?php endif; ?>

            <div class="qty-row">
                <div class="qty">
                    <button type="button" data-qty="minus" aria-label="Azalt">−</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="99" inputmode="numeric" aria-label="Adet">
                    <button type="button" data-qty="plus" aria-label="Artır">+</button>
                </div>
                <button class="btn btn-solid" id="addBtn" data-pid="<?= (int)$product->id ?>">Sepete Ekle</button>
            </div>
            <span class="add-feedback" id="addFeedback" role="status"></span>

            <!-- Spec tablosu -->
            <?php
            $specs = [
                'Çerçeve Formu' => $product->frame_shape,
                'Çerçeve Materyali' => $product->frame_material,
                'Cam Özelliği' => $product->glass_feature,
                'Uygun Yüz Tipleri' => $product->suitable_face_shapes,
                'Ekartman' => $product->ekartman ? $product->ekartman . ' mm' : null,
                'Köprü' => $product->kopru ? $product->kopru . ' mm' : null,
                'Sap Uzunluğu' => $product->sap_uzunlugu ? $product->sap_uzunlugu . ' mm' : null,
                'Cinsiyet' => $product->gender,
            ];
            $specs = array_filter($specs, fn($v) => $v !== null && trim((string)$v) !== '');
            ?>
            <?php if ($specs): ?>
            <table class="spec-table" aria-label="Ürün özellikleri">
                <?php foreach ($specs as $k => $v): ?>
                <tr><td><?= e($k) ?></td><td><?= e($v) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <div class="mt-4" style="border-top:1px solid var(--line); padding-top:24px; font-size:13px; color:var(--ink-mute);">
                Ürün soruları için:
                <a class="link-line" href="<?= $S->whatsapp_phone ? 'https://wa.me/9' . e(preg_replace('/\D/', '', $S->whatsapp_phone)) : '#' ?>" target="_blank" rel="noopener" style="color:var(--gold-deep);">WhatsApp</a>
                · <a class="link-line" href="mailto:<?= e($S->contact_email) ?>" style="color:var(--gold-deep);">E-posta</a>
            </div>
        </div>
    </div>
</section>

<!-- Benzer ürünler -->
<?php if ($related): ?>
<section class="section section-soft">
    <div class="container-wide">
        <span class="eyebrow reveal">Seçki</span>
        <h2 class="display reveal" style="font-size:clamp(28px,4vw,54px); margin:20px 0 44px;">Benzer modeller</h2>
        <div class="product-grid">
            <?php $variationsByProduct = []; foreach ($related as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
(function () {
    // Galeri
    const main = document.getElementById('dgImage');
    document.querySelectorAll('[data-thumb]').forEach(btn => {
        btn.addEventListener('click', () => {
            main.src = btn.dataset.thumb;
            document.querySelectorAll('[data-thumb]').forEach(b => b.classList.remove('on'));
            btn.classList.add('on');
        });
    });

    // Varyasyon seçimi
    let selectedVariation = null;
    const row = document.getElementById('variantRow');
    const selName = document.getElementById('selVarName');
    const stockNote = document.getElementById('stockNote');

    function select(chip) {
        row.querySelectorAll('.variant-chip').forEach(c => c.classList.remove('on'));
        chip.classList.add('on');
        selectedVariation = parseInt(chip.dataset.vid, 10);
        if (selName) selName.textContent = chip.dataset.name;
        const st = parseInt(chip.dataset.stock, 10);
        if (stockNote) {
            if (st <= 0) stockNote.textContent = 'Bu renk tükendi';
            else if (st <= 3) stockNote.textContent = 'Son ' + st + ' ürün';
            else stockNote.textContent = '';
        }
        if (chip.dataset.img) main.src = chip.dataset.img;
    }
    if (row) {
        row.querySelectorAll('.variant-chip').forEach(chip => {
            chip.addEventListener('click', () => select(chip));
            // İlk stoklu varyasyonu otomatik seç
        });
        const first = row.querySelector('.variant-chip:not(.out)');
        if (first) select(first);
    }

    // Adet
    const qtyInput = document.getElementById('qtyInput');
    document.querySelectorAll('[data-qty]').forEach(btn => {
        btn.addEventListener('click', () => {
            let q = parseInt(qtyInput.value, 10) + (btn.dataset.qty === 'plus' ? 1 : -1);
            qtyInput.value = Math.max(1, Math.min(99, q || 1));
        });
    });

    // Sepete ekle
    const addBtn = document.getElementById('addBtn');
    const fb = document.getElementById('addFeedback');
    addBtn.addEventListener('click', async () => {
        addBtn.disabled = true;
        const data = await addToCart(<?= (int)$product->id ?>, selectedVariation, Math.max(1, parseInt(qtyInput.value, 10) || 1));
        addBtn.disabled = false;
        if (data.ok) {
            fb.textContent = '✓ Sepete eklendi';
            setTimeout(() => fb.textContent = '', 2500);
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
