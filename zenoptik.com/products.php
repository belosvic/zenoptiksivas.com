<?php require_once __DIR__ . '/includes/site-init.php';

$S = getSettings($db);
$pageTitle = 'Koleksiyon';

// ---- Filtreler ----
$catSlug  = trim($_GET['cat'] ?? '');
$brandId  = (int)($_GET['brand'] ?? 0);
$gender   = trim($_GET['gender'] ?? '');
$sort     = $_GET['sort'] ?? 'new';
$q        = trim($_GET['q'] ?? '');

$cats = $db->query("SELECT id, name, slug, parent_id FROM categories ORDER BY parent_id IS NOT NULL, name ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();

// Kategori filtresi: slug eşleşen kategori + alt kategorileri
$catIds = [];
$activeCat = null;
if ($catSlug !== '') {
    foreach ($cats as $c) {
        if ($c->slug === $catSlug) {
            $activeCat = $c;
            $catIds[] = (int)$c->id;
            foreach ($cats as $c2) {
                if ((int)$c2->parent_id === (int)$c->id) $catIds[] = (int)$c2->id;
            }
            break;
        }
    }
}

// ---- Sorgu kurma ----
$where = ["p.status = 'active'"];
$params = [];

if ($catIds) {
    $where[] = "p.category_id IN (" . implode(',', $catIds) . ")";
}
if ($brandId) {
    $where[] = "p.brand_id = ?";
    $params[] = $brandId;
}
if ($gender !== '') {
    $where[] = "p.gender = ?";
    $params[] = $gender;
}
if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}

$sortSql = match ($sort) {
    'price-asc'  => "COALESCE(NULLIF(p.discount_price,0), p.base_price) ASC",
    'price-desc' => "COALESCE(NULLIF(p.discount_price,0), p.base_price) DESC",
    'name'       => "p.name ASC",
    default      => "p.created_at DESC",
};

$sql = "
    SELECT p.*, b.name AS brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $sortSql
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Varyasyon renk sayıları (tek sorguda)
$variationsByProduct = [];
if ($products) {
    $varStmt = $db->prepare("SELECT id, frame_color FROM product_variations WHERE product_id = ? ORDER BY id ASC");
    foreach ($products as $p) {
        $varStmt->execute([$p->id]);
        $variationsByProduct[$p->id] = $varStmt->fetchAll();
    }
}

function buildUrl($overrides) {
    $params = array_filter(array_merge($_GET, $overrides), fn($v) => $v !== '' && $v !== null);
    return url('products.php?' . http_build_query($params));
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container-wide">
        <div class="crumbs"><a href="<?= url('index.php') ?>">Anasayfa</a> / Koleksiyon</div>
        <h1 class="display"><?= $activeCat ? e($activeCat->name) : 'Koleksiyon' ?></h1>
        <p><?= e($S->site_title) ?> kataloğu — orijinal ürünler, seçki düzenlemesiyle sunulur.</p>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container-wide catalog-layout">

        <aside class="filters" aria-label="Ürün filtreleri">
            <form action="<?= url('products.php') ?>" method="get">
                <div class="filter-group">
                    <h4>Arama</h4>
                    <div class="field" style="margin:0;">
                        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Ürün ara…" style="padding:11px 14px;">
                        <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
                    </div>
                </div>
            </form>

            <div class="filter-group">
                <h4>Kategori</h4>
                <a class="link-line" href="<?= buildUrl(['cat' => '', 'page' => null]) ?>" style="font-size:13px; margin-bottom:10px; <?= $catSlug === '' ? 'color:var(--gold-deep);' : '' ?>">Tümü</a>
                <?php foreach ($cats as $c): ?>
                    <label class="radio <?= $catSlug === $c->slug ? 'on' : '' ?>">
                        <input type="radio" name="cat" value="<?= e($c->slug) ?>" <?= $catSlug === $c->slug ? 'checked' : '' ?>
                               style="position:absolute; opacity:0; pointer-events:none;"
                               onchange="window.location='<?= buildUrl(['cat' => $c->slug]) ?>'">
                        <span style="<?= $c->parent_id ? 'padding-left:14px; opacity:.75;' : '' ?>"><?= e($c->name) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h4>Cinsiyet</h4>
                <?php foreach (['' => 'Tümü', 'Unisex' => 'Unisex', 'Erkek' => 'Erkek', 'Kadın' => 'Kadın', 'Çocuk' => 'Çocuk'] as $g => $label): ?>
                    <label class="radio <?= $gender === $g ? 'on' : '' ?>">
                        <input type="radio" name="gender" value="<?= e($g) ?>" <?= $gender === $g ? 'checked' : '' ?>
                               style="position:absolute; opacity:0; pointer-events:none;"
                               onchange="window.location='<?= buildUrl(['gender' => $g]) ?>'">
                        <span><?= e($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h4>Marka</h4>
                <label class="radio <?= $brandId === 0 ? 'on' : '' ?>">
                    <span onclick="window.location='<?= buildUrl(['brand' => '']) ?>'">Tümü</span>
                </label>
                <?php foreach ($brands as $b): ?>
                    <label class="radio <?= $brandId === (int)$b->id ? 'on' : '' ?>">
                        <span onclick="window.location='<?= buildUrl(['brand' => (int)$b->id]) ?>'" style="font-size:13px;"><?= e($b->name) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </aside>

        <div>
            <div class="catalog-toolbar">
                <span class="catalog-count"><?= count($products) ?> ürün</span>
                <div class="chips">
                    <?php
                    $sorts = ['new' => 'En Yeni', 'price-asc' => 'Fiyat ↑', 'price-desc' => 'Fiyat ↓', 'name' => 'A–Z'];
                    foreach ($sorts as $k => $label): ?>
                        <a class="chip <?= $sort === $k ? 'on' : '' ?>" href="<?= buildUrl(['sort' => $k]) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($products): ?>
                <div class="product-grid">
                    <?php foreach ($products as $p) include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php else: ?>
                <div class="center" style="padding:70px 0;">
                    <h2 class="display" style="font-size:clamp(26px,3.4vw,44px);">Sonuç bulunamadı</h2>
                    <p class="muted mt-2">Filtreleri temizleyip tekrar deneyin.</p>
                    <a class="btn mt-2" href="<?= url('products.php') ?>">Filtreleri Temizle</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
