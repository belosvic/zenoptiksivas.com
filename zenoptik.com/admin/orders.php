<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
// SİPARİŞ DURUMU GÜNCELLEME (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $tracking_code = trim($_POST['tracking_code']);
    
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ?, tracking_code = ? WHERE id = ?");
        $stmt->execute([$status, $tracking_code, $order_id]);
        $successMsg = "Sipariş durumu başarıyla güncellendi.";
    } catch (PDOException $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}

// SİPARİŞ DETAYINI GÖRÜNTÜLEME (GET id varsa)
$viewOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $viewOrder = $stmt->fetch();
    
    if($viewOrder) {
        $stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order_id]);
        $orderItems = $stmtItems->fetchAll();
    }
}

// TÜM SİPARİŞLERİ LİSTELE
$orders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

// Durumları Türkçeleştirmek için yardımcı dizi
$statusTranslations = [
    'pending' => ['text' => 'Yeni Sipariş', 'badge' => 'bg-danger'],
    'processing' => ['text' => 'Hazırlanıyor', 'badge' => 'bg-warning text-dark'],
    'shipped' => ['text' => 'Kargoya Verildi', 'badge' => 'bg-info text-dark'],
    'delivered' => ['text' => 'Teslim Edildi', 'badge' => 'bg-success'],
    'cancelled' => ['text' => 'İptal Edildi', 'badge' => 'bg-secondary']
];
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Sipariş Yönetimi</h2>
        <?php if($viewOrder): ?>
            <a href="orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Tüm Siparişlere Dön</a>
        <?php endif; ?>
    </div>

    <?php if(isset($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($viewOrder): ?>
        <!-- SİPARİŞ DETAY EKRANI -->
        <div class="row">
            <!-- Sol: Sipariş İçeriği ve Müşteri -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Sipariş Detayı (#<?= htmlspecialchars($viewOrder->order_number) ?>)</h6>
                        <span class="text-muted"><?= date('d.m.Y H:i', strtotime($viewOrder->created_at)) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2">Müşteri Bilgileri</h6>
                                <strong><?= htmlspecialchars($viewOrder->customer_name) ?></strong><br>
                                <i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($viewOrder->customer_email) ?><br>
                                <i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($viewOrder->customer_phone) ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2">Teslimat Adresi</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($viewOrder->shipping_address)) ?></p>
                            </div>
                        </div>

                        <h6 class="text-muted border-bottom pb-2">Satın Alınan Ürünler</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ürün Adı & Özellik</th>
                                        <th class="text-center">Adet</th>
                                        <th class="text-end">Birim Fiyat</th>
                                        <th class="text-end">Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item->product_name) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($item->variation_details) ?></small>
                                        </td>
                                        <td class="text-center"><?= $item->quantity ?></td>
                                        <td class="text-end"><?= $item->unit_price ?> ₺</td>
                                        <td class="text-end"><strong><?= $item->quantity * $item->unit_price ?> ₺</strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end text-muted">Genel Toplam:</td>
                                        <td class="text-end fs-5 text-success"><strong><?= $viewOrder->total_amount ?> ₺</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ: Durum Güncelleme Formu -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Sipariş Durumu</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="orders.php?id=<?= $viewOrder->id ?>">
                            <input type="hidden" name="order_id" value="<?= $viewOrder->id ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Durum</label>
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $viewOrder->status == 'pending' ? 'selected' : '' ?>>Yeni Sipariş (Bekliyor)</option>
                                    <option value="processing" <?= $viewOrder->status == 'processing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                                    <option value="shipped" <?= $viewOrder->status == 'shipped' ? 'selected' : '' ?>>Kargoya Verildi</option>
                                    <option value="delivered" <?= $viewOrder->status == 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                                    <option value="cancelled" <?= $viewOrder->status == 'cancelled' ? 'selected' : '' ?>>İptal Edildi</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Kargo Takip Numarası</label>
                                <input type="text" name="tracking_code" class="form-control" placeholder="Müşteriye gösterilecek" value="<?= htmlspecialchars($viewOrder->tracking_code ?? '') ?>">
                            </div>
                            
                            <button type="submit" name="update_order" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Durumu Güncelle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- SİPARİŞ LİSTESİ -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th class="text-end pe-3">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td class="ps-3"><strong>#<?= htmlspecialchars($order->order_number) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($order->customer_name) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($order->customer_phone) ?></small>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($order->created_at)) ?></td>
                                <td><strong><?= $order->total_amount ?> ₺</strong></td>
                                <td>
                                    <?php 
                                        $st = $statusTranslations[$order->status];
                                        echo "<span class='badge {$st['badge']}'>{$st['text']}</span>";
                                    ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="?id=<?= $order->id ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-search me-1"></i> İncele
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">
                                    <i class="fas fa-box-open fs-3 mb-2 d-block"></i>
                                    Henüz sipariş bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>