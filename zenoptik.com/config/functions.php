<?php
// Zen Optik - Ön yüz yardımcı fonksiyonları

require_once __DIR__ . '/db.php';

// ---------- AYARLAR ----------
function getSettings($db) {
    static $settings = null;
    if ($settings === null) {
        $row = $db->query("SELECT * FROM settings WHERE id = 1")->fetch();
        $settings = $row ? $row : (object)[
            'site_title' => 'Zen Optik', 'site_description' => '', 'site_logo' => '',
            'contact_email' => '', 'contact_phone' => '', 'contact_phone_2' => '',
            'whatsapp_phone' => '', 'address' => '', 'instagram_url' => '',
            'facebook_url' => '', 'footer_text' => ''
        ];
    }
    return $settings;
}

// ---------- FİYAT ----------
function formatPrice($value) {
    return number_format((float)$value, 2, ',', '.') . ' ₺';
}
function displayPrice($product) {
    return $product->discount_price > 0 ? $product->discount_price : $product->base_price;
}
function hasDiscount($product) {
    return $product->discount_price > 0 && (float)$product->discount_price < (float)$product->base_price;
}

// ---------- GÖRSEL ----------
function productImage($image, $fallbackSvg = true) {
    $base = '/zenoptik.com';
    if ($image && file_exists(__DIR__ . '/../uploads/products/' . $image)) {
        return $base . '/uploads/products/' . $image;
    }
    return $fallbackSvg ? $base . '/assets/img/frame-placeholder.svg' : '';
}

// ---------- SEPET ----------
// $_SESSION['cart'] yapısı: [ key => ['product_id'=>int, 'variation_id'=>int|null, 'qty'=>int] ]
// key: "p{id}_v{variation_id veya 0}"
function cartItems($db) {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) return ['items' => [], 'count' => 0, 'subtotal' => 0.0];

    $items = [];
    $count = 0;
    $subtotal = 0.0;

    foreach ($cart as $key => $row) {
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.slug, p.main_image, p.base_price, p.discount_price,
                   v.id AS vid, v.frame_color, v.glass_color, v.stock_quantity, v.variation_image
            FROM products p
            LEFT JOIN product_variations v ON v.id = :vid AND v.product_id = p.id
            WHERE p.id = :pid AND p.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':pid' => $row['product_id'], ':vid' => $row['variation_id']]);
        $p = $stmt->fetch();
        if (!$p) { unset($_SESSION['cart'][$key]); continue; }

        $unit = displayPrice($p);
        $line = $unit * $row['qty'];
        $count += $row['qty'];
        $subtotal += $line;

        $items[] = [
            'key' => $key,
            'product_id' => (int)$p->id,
            'variation_id' => $p->vid ? (int)$p->vid : null,
            'name' => $p->name,
            'slug' => $p->slug,
            'image' => productImage($p->vid ? $p->variation_image : $p->main_image, false) ?: productImage($p->main_image),
            'frame_color' => $p->frame_color,
            'glass_color' => $p->glass_color,
            'qty' => (int)$row['qty'],
            'unit_price' => $unit,
            'line_total' => $line,
            'stock' => $p->vid ? (int)$p->stock_quantity : null,
        ];
    }
    return ['items' => $items, 'count' => $count, 'subtotal' => $subtotal];
}

function cartCount($db) {
    $cart = $_SESSION['cart'] ?? [];
    $n = 0;
    foreach ($cart as $row) $n += (int)$row['qty'];
    return $n;
}

function cartKey($productId, $variationId) {
    return 'p' . (int)$productId . '_v' . ($variationId ? (int)$variationId : 0);
}

// ---------- RENK ----------
function colorHex($name) {
    static $map = [
        'siyah' => '#1a1a1a', 'black' => '#1a1a1a', 'matte black' => '#151515', 'mat siyah' => '#151515',
        'kahve' => '#6b4a2b', 'brown' => '#6b4a2b', 'havana' => '#5a3a1e', 'tortoise' => '#7a5230',
        'gri' => '#8a8a8a', 'gray' => '#8a8a8a', 'grey' => '#8a8a8a',
        'beyaz' => '#efefef', 'white' => '#efefef',
        'gumus' => '#b8b8b8', 'gümüş' => '#b8b8b8', 'silver' => '#b8b8b8',
        'altin' => '#cfa858', 'gold' => '#cfa858',
        'mavi' => '#2d4b73', 'blue' => '#2d4b73', 'lacivert' => '#1c2a44', 'navy' => '#1c2a44',
        'yesil' => '#3a5c3a', 'yeşil' => '#3a5c3a', 'green' => '#3a5c3a', 'zeytin' => '#5d5a3a',
        'kirmizi' => '#a02c2c', 'kırmızı' => '#a02c2c', 'red' => '#a02c2c', 'bordo' => '#5e1f2a',
        'pembe' => '#c98a8a', 'pink' => '#c98a8a',
        'sari' => '#d9b23a', 'sarı' => '#d9b23a', 'yellow' => '#d9b23a',
        'mor' => '#5a4470', 'purple' => '#5a4470',
        'bej' => '#d6c9b2', 'beige' => '#d6c9b2', 'krem' => '#e3d8c4', 'cream' => '#e3d8c4',
        'seffaf' => 'rgba(180,180,190,.55)', 'şeffaf' => 'rgba(180,180,190,.55)', 'transparent' => 'rgba(180,180,190,.55)',
        'bronz' => '#8a6a42', 'bronze' => '#8a6a42', 'turuncu' => '#c07a35', 'orange' => '#c07a35',
    ];
    $key = mb_strtolower(trim((string)$name), 'UTF-8');
    foreach ($map as $k => $hex) {
        if (str_contains($key, $k)) return $hex;
    }
    return null;
}

// ---------- SLUG / URL ----------
function url($path = '') {
    return '/zenoptik.com/' . ltrim($path, '/');
}
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
