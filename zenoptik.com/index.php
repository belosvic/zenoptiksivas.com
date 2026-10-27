<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$pageTitle = 'Anasayfa';

// Öne çıkan / yeni ürünler
$featured = $db->query("
    SELECT p.*, b.name AS brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.status = 'active'
    ORDER BY p.is_featured DESC, p.created_at DESC
    LIMIT 6
")->fetchAll();

// Marka şeridi
$brands = $db->query("SELECT name FROM brands WHERE status = 'active' ORDER BY id ASC LIMIT 14")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO: 3D SÖKÜLME SAHNESİ ============ -->
<div class="hero-scroll-zone">
    <section class="hero-stage" id="heroStage"
        data-fallback="<?= $featured && $featured[0]->main_image ? url('uploads/products/' . e($featured[0]->main_image)) : url('assets/img/frame-placeholder.svg') ?>">
        <canvas id="glCanvas" aria-label="3D gözlük modeli" role="img"></canvas>
        <div class="hero-grain"></div>

        <div class="hero-progress"><span></span></div>

        <div class="hero-copy container-wide">
            <div class="hero-top">
                <span class="eyebrow">Zen Optik — Sivas</span>
            </div>

            <div>
                <h1 class="hero-headline display">Vision,<br><span class="accent">Redefined.</span></h1>
                <p class="hero-sub" style="margin-top:22px;">
                    Premium çerçeve koleksiyonu. Aşağı kaydırın ve ürünün
                    mühendisliğini parça parça keşfedin.
                </p>
            </div>

            <div class="hero-bottom">
                <div class="hero-ctas">
                    <a class="btn btn-solid" href="<?= url('products.php') ?>">Koleksiyonu Keşfet</a>
                    <a class="btn" href="<?= url('#koleksiyon') ?>">Kategoriler</a>
                </div>
                <div class="scroll-cue"><span>Kaydırın</span><span class="line"></span></div>
            </div>
        </div>
    </section>
</div>

<script type="importmap">
{ "imports": {
    "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
}}
</script>
<script type="module" src="<?= url('assets/js/glasses-scene.js') ?>"></script>

<!-- ============ ŞERİT ============ -->
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
        <span>
            <?php foreach (array_slice($brands ?: ['Zen Optik'], 0, 7) as $b): ?>
            <?= e($b->name ?? $b) ?>
            <?php endforeach; ?>
        </span>
        <?php endfor; ?>
    </div>
</div>

<!-- ============ MÜHENDİSLİK & ZANAAT ============ -->
<section class="section section-dark" id="zanaat">
    <div class="container-wide">
        <span class="eyebrow reveal">Zanaat</span>
        <h2 class="display reveal" style="font-size:clamp(34px,5vw,72px); margin:26px 0 70px; max-width:900px;">
            Her parça bir <em class="gold" style="font-style:italic;">karar</em>
        </h2>

        <div class="craft-wrap">
            <div class="craft-visual reveal">
                <div class="cv-frame">
                    <?php if ($featured && $featured[0]->main_image): ?>
                        <img src="<?= url('uploads/products/' . e($featured[0]->main_image)) ?>" alt="Gözlük detay" loading="lazy">
                    <?php endif; ?>
                    <span class="cv-note">Ürün stüdyosu — detay çekimi</span>
                </div>
            </div>

            <div class="craft-copy">
                <?php
                $steps = [
                    ['01', 'Çerçeve', 'Asetat ve metal çerçeveler; şekil, ağırlık ve denge arasındaki ilişki üzerine kuruludur. Her model, uzun süreli konfor için denemelerle kalibre edilir.'],
                    ['02', 'Menteşe', 'Kaplama metal menteşeler, mikrometrik toleranslarla üretilir. Sap açılışı, yüzünüzü sıkmadan sabit kalacak şekilde ayarlanır.'],
                    ['03', 'Lens', 'UV korumalı ve netlik odaklı lens seçenekleri. Reçete damıldan degrade kaplamaya kadar ihtiyaca göre düzenlenir.'],
                    ['04', 'Akort &amp; Ölçü', 'Ekartman, köprü ve sap uzunluğu ölçüleriniz; mağazamızda ücretsiz yüz ölçümü ile doğru çerçeveye eşleştirilir.'],
                ];
                foreach ($steps as [$num, $title, $desc]): ?>
                <div class="craft-step reveal">
                    <div class="cs-num"><?= $num ?></div>
                    <div>
                        <h3><?= $title ?></h3>
                        <p><?= $desc ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ KOLEKSİYONLAR ============ -->
<section class="section" id="koleksiyon">
    <div class="container-wide">
        <div class="collections-head">
            <div>
                <span class="eyebrow reveal">Koleksiyonlar</span>
                <h2 class="display reveal" style="font-size:clamp(34px,5vw,72px); margin-top:20px;">Keşfe çık</h2>
            </div>
            <a class="link-line reveal" href="<?= url('products.php') ?>">Tümünü Gör</a>
        </div>

        <div class="collections-grid">
            <?php
            $collections = $db->query("SELECT id, name, slug FROM categories WHERE parent_id IS NULL ORDER BY id ASC")->fetchAll();
            $ccClasses = ['cc-1', 'cc-2', 'cc-3', 'cc-4'];
            $ccNums = ['01', '02', '03', '04'];
            $ccSubs = ['Güneş Gözlüğü' => 'Ikonik formlar, koruyucu lensler', 'Gözlük Zincirleri' => 'Kauçuk ve metal zincirler', 'Lensler' => 'Reçeteli ve renkli lens seçenekleri'];
            $ci = 0;
            foreach ($collections as $c): $cls = $ccClasses[$ci % 4]; $num = $ccNums[$ci % 4];
                $sub = $ccSubs[$c->name] ?? 'Koleksiyonu incele';
            ?>
            <a class="col-card <?= $cls ?> reveal" href="<?= url('products.php?cat=' . urlencode($c->slug)) ?>">
                <div class="cc-visual" style="background:<?= $ci % 2 ? 'var(--dark-2)' : 'var(--dark)' ?>;">
                    <span class="cc-num"><?= $num ?></span>
                    <span class="display" style="font-size:clamp(38px,6vw,86px); color:<?= $ci % 2 ? 'rgba(242,238,230,.16)' : 'rgba(242,238,230,.10)' ?>; transition: color .5s var(--ease);"
                          onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='<?= $ci % 2 ? 'rgba(242,238,230,.16)' : 'rgba(242,238,230,.10)' ?>'">
                        <?= mb_strtoupper(e(mb_substr($c->name, 0, 3, 'UTF-8')), 'UTF-8') ?>
                    </span>
                </div>
                <div class="cc-body">
                    <h3><?= e($c->name) ?></h3>
                    <p><?= e($sub) ?></p>
                </div>
            </a>
            <?php $ci++; endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ ÖNE ÇIKAN ÜRÜNLER ============ -->
<?php if ($featured): ?>
<section class="section section-soft">
    <div class="container-wide">
        <div class="featured-head">
            <div>
                <span class="eyebrow reveal">Seçki</span>
                <h2 class="display reveal" style="font-size:clamp(34px,5vw,72px); margin-top:20px;">Öne çıkanlar</h2>
            </div>
            <a class="link-line reveal" href="<?= url('products.php') ?>">Kataloğa git</a>
        </div>

        <div class="product-grid">
            <?php
            $varStmt = $db->prepare("SELECT id, frame_color FROM product_variations WHERE product_id = ? ORDER BY id ASC");
            foreach ($featured as $p) {
                $p->brand_name = $p->brand_name ?: null;
                $varStmt->execute([$p->id]);
                $variationsByProduct[$p->id] = $varStmt->fetchAll();
            }
            foreach ($featured as $p) include __DIR__ . '/includes/product-card.php';
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ MARKA HİKAYESİ ============ -->
<section class="section" id="hikaye">
    <div class="container-wide story-wrap">
        <div class="story-copy">
            <span class="eyebrow reveal">Hikaye</span>
            <h2 class="display reveal" style="font-size:clamp(32px,4.4vw,64px); margin-top:22px;">Zen Optik</h2>
            <p class="story-quote reveal">
                “Gözlük, yüzünüzün <em>mimarisi</em>dir. Biz o mimariyi
                sizinle birlikte kuruyoruz.”
            </p>
            <p class="reveal muted" style="max-width:480px;">
                Sivas merkezli mağazamızda, dünya markalarının orijinal
                koleksiyonlarını; doğru ölçü, doğru lens ve kişisel danışmanlıkla
                birleştiriyoruz. Ürün seçiminde baskı değil, uyum esastır.
            </p>
            <div class="story-stats reveal">
                <?php $brandCount = (int)$db->query("SELECT COUNT(id) c FROM brands")->fetch()->c; ?>
                <div class="ss"><b><?= $brandCount ?>+</b><span>Global Marka</span></div>
                <div class="ss"><b>Ücretsiz</b><span>Yüz Ölçümü</span></div>
                <div class="ss"><b>Orijinal</b><span>Ürün Garantisi</span></div>
            </div>
        </div>
        <div class="story-media reveal">
            <div class="sm-frame">
                <?php if ($featured && $featured[0]->main_image): ?>
                    <img src="<?= url('uploads/products/' . e($featured[0]->main_image)) ?>" alt="Zen Optik ürün" loading="lazy">
                <?php else: ?>
                    <div class="cv-frame" style="background:var(--bg-soft); aspect-ratio:4/3;"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ GÜVEN & HİZMET ============ -->
<section class="section section-dark" id="hizmet">
    <div class="container-wide">
        <span class="eyebrow reveal">Hizmet</span>
        <h2 class="display reveal" style="font-size:clamp(30px,4.4vw,60px); margin:24px 0 56px;">Yanınızdayız</h2>

        <div class="trust-grid reveal">
            <div class="trust-cell">
                <div class="tc-icon">◈</div>
                <h4>Orijinal Ürün</h4>
                <p>Tüm ürünler yetkili distribütörlerden tedarik edilir, kutu ve sertifikasıyla gönderilir.</p>
            </div>
            <div class="trust-cell">
                <div class="tc-icon">◈</div>
                <h4>Kargo &amp; İade</h4>
                <p>Siparişleriniz özenle paketlenir. İade koşulları için bizimle iletişime geçebilirsiniz.</p>
            </div>
            <div class="trust-cell">
                <div class="tc-icon">◈</div>
                <h4>Ücretsiz Danışmanlık</h4>
                <p>Çerçeve seçimi ve lens önerisi için mağazamıza bekleriz; yüz ölçümü ücretsizdir.</p>
            </div>
            <div class="trust-cell">
                <div class="tc-icon">◈</div>
                <h4>WhatsApp Destek</h4>
                <p>
                    <?php if ($S->whatsapp_phone): ?>
                        <a href="https://wa.me/9<?= e(preg_replace('/\D/', '', $S->whatsapp_phone)) ?>" target="_blank" rel="noopener" style="color:var(--gold);"><?= e($S->whatsapp_phone) ?></a>
                    <?php else: ?>Bize yazın<?php endif; ?>
                    — sorularınızı en kısa sürede yanıtlıyoruz.
                </p>
            </div>
        </div>

        <div class="flex-between mt-4 reveal" style="color:rgba(242,238,230,.55); font-size:13px;">
            <span><?= e($S->address) ?></span>
            <a class="link-line" style="color:var(--gold);" href="mailto:<?= e($S->contact_email) ?>"><?= e($S->contact_email) ?></a>
        </div>
    </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta-band">
    <div class="container">
        <span class="eyebrow reveal">Koleksiyon</span>
        <h2 class="reveal" style="margin-top:26px;">Bakışını<br><em>tanımla.</em></h2>
        <a class="btn btn-gold reveal" href="<?= url('products.php') ?>">Alışverişe Başla</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
