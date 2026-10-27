<?php
// Kullanım: $p = ürün satırı (products + joins ile)
$useSet = null;
if (!empty($variationsByProduct)) $useSet = $variationsByProduct[$p->id] ?? null;
?>
<a class="product-card reveal" href="<?= url('product-detail.php?slug=' . urlencode($p->slug)) ?>">
    <div class="pc-media">
        <?php if (hasDiscount($p)): ?><span class="pc-flag">İndirim</span><?php endif; ?>
        <?php if ($p->main_image): ?>
            <img src="<?= url('uploads/products/' . e($p->main_image)) ?>" alt="<?= e($p->name) ?>" loading="lazy">
        <?php else: ?>
            <img class="pc-ph" src="<?= url('assets/img/frame-placeholder.svg') ?>" alt="" loading="lazy">
        <?php endif; ?>
        <span class="pc-quick">Ürünü İncele</span>
    </div>
    <div class="pc-info">
        <div>
            <?php if (!empty($p->brand_name)): ?><div class="pc-brand"><?= e($p->brand_name) ?></div><?php endif; ?>
            <div class="pc-name"><?= e($p->name) ?></div>
            <?php if ($useSet && count($useSet) > 1): ?>
                <div class="pc-colors" style="display:flex; gap:5px; margin-top:8px; align-items:center;">
                    <?php foreach (array_slice($useSet, 0, 4) as $v): $hex = colorHex($v->frame_color); ?>
                        <span style="width:11px;height:11px;border-radius:50%;background:<?= $hex ?: 'var(--ink-mute)' ?>;border:1px solid var(--line);" title="<?= e($v->frame_color) ?>"></span>
                    <?php endforeach; ?>
                    <span class="muted" style="font-size:11px;"><?= count($useSet) ?> renk</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="pc-price">
            <?php if (hasDiscount($p)): ?>
                <del><?= formatPrice($p->base_price) ?></del>
            <?php endif; ?>
            <?= formatPrice(displayPrice($p)) ?>
        </div>
    </div>
</a>
