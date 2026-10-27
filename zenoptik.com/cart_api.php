<?php
require_once __DIR__ . '/includes/site-init.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = $_POST; }

$action = $data['action'] ?? '';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

function respond($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Yalnızca POST']);

switch ($action) {
    case 'add': {
        $pid = (int)($data['product_id'] ?? 0);
        $vid = isset($data['variation_id']) && $data['variation_id'] ? (int)$data['variation_id'] : null;
        $qty = max(1, min(99, (int)($data['qty'] ?? 1)));

        // Ürün aktif mi?
        $stmt = $db->prepare("SELECT id, name FROM products WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();
        if (!$product) respond(['ok' => false, 'error' => 'Ürün bulunamadı']);

        // Varyasyon bu ürüne ait mi?
        if ($vid) {
            $stmt = $db->prepare("SELECT id, stock_quantity FROM product_variations WHERE id = ? AND product_id = ?");
            $stmt->execute([$vid, $pid]);
            $v = $stmt->fetch();
            if (!$v) respond(['ok' => false, 'error' => 'Geçersiz varyasyon']);
            if ((int)$v->stock_quantity <= 0) respond(['ok' => false, 'error' => 'Bu renk tükendi']);
        }

        $key = cartKey($pid, $vid);
        $current = (int)($_SESSION['cart'][$key]['qty'] ?? 0);
        $_SESSION['cart'][$key] = [
            'product_id' => $pid,
            'variation_id' => $vid,
            'qty' => min(99, $current + $qty),
        ];

        respond(['ok' => true, 'count' => cartCount($db)]);
    }

    case 'update': {
        $key = $data['key'] ?? '';
        if (!isset($_SESSION['cart'][$key])) respond(['ok' => false, 'error' => 'Satır bulunamadı']);
        $qty = max(0, min(99, (int)($data['qty'] ?? 0)));
        if ($qty === 0) unset($_SESSION['cart'][$key]);
        else $_SESSION['cart'][$key]['qty'] = $qty;
        respond(['ok' => true, 'count' => cartCount($db)]);
    }

    case 'remove': {
        $key = $data['key'] ?? '';
        unset($_SESSION['cart'][$key]);
        respond(['ok' => true, 'count' => cartCount($db)]);
    }

    case 'count': {
        respond(['ok' => true, 'count' => cartCount($db)]);
    }

    default:
        respond(['ok' => false, 'error' => 'Bilinmeyen işlem']);
}
