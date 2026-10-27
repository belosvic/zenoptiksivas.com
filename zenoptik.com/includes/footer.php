<?php require_once __DIR__ . '/site-init.php'; $S = getSettings($db); ?>

<footer class="footer" id="iletisim">
    <div class="container-wide">
        <div class="footer-grid">
            <div>
                <a href="<?= url('index.php') ?>" class="brand">
                    <span class="brand-mark"><span></span></span>
                    Zen Optik
                </a>
                <p style="font-size:14px; max-width:320px; opacity:.7;">
                    <?= e($S->footer_text ?: 'Doğru çerçeve, doğru lens, doğru bakış. Zen Optik gözlük deneyimi.') ?>
                </p>
                <?php if ($S->instagram_url): ?>
                <a class="link-line" href="<?= e($S->instagram_url) ?>" target="_blank" rel="noopener" style="margin-top:22px; color:var(--gold);">Instagram</a>
                <?php endif; ?>
            </div>
            <div>
                <h5>Alışveriş</h5>
                <ul>
                    <li><a href="<?= url('products.php') ?>">Tüm Koleksiyon</a></li>
                    <?php
                    $navCats = $db->query("SELECT id, name, slug FROM categories WHERE parent_id IS NULL ORDER BY id LIMIT 3")->fetchAll();
                    foreach ($navCats as $c): ?>
                    <li><a href="<?= url('products.php?cat=' . e($c->slug)) ?>"><?= e($c->name) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= url('cart.php') ?>">Sepetim</a></li>
                </ul>
            </div>
            <div>
                <h5>Hizmet</h5>
                <ul>
                    <li><a href="<?= url('index.php#hizmet') ?>">Kargo &amp; İade</a></li>
                    <li><a href="<?= url('index.php#hizmet') ?>">Sıkça Sorulanlar</a></li>
                    <li><a href="<?= url('index.php#hizmet') ?>">Ücretsiz Ölçü</a></li>
                    <li><a href="<?= e($S->whatsapp_phone ? 'https://wa.me/9' . preg_replace('/\D/', '', $S->whatsapp_phone) : '#') ?>" target="_blank" rel="noopener">WhatsApp Destek</a></li>
                </ul>
            </div>
            <div>
                <h5>İletişim</h5>
                <ul>
                    <?php if ($S->address): ?><li style="opacity:.75;"><?= e($S->address) ?></li><?php endif; ?>
                    <?php if ($S->contact_phone): ?><li><a href="tel:<?= e(preg_replace('/\s/', '', $S->contact_phone)) ?>"><?= e($S->contact_phone) ?></a></li><?php endif; ?>
                    <?php if ($S->contact_email): ?><li><a href="mailto:<?= e($S->contact_email) ?>"><?= e($S->contact_email) ?></a></li><?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> Zen Optik. Tüm hakları saklıdır.</span>
            <span>Sivas, Türkiye</span>
        </div>
    </div>
</footer>

<script src="<?= url('assets/js/site.js') ?>" defer></script>
</body>
</html>
