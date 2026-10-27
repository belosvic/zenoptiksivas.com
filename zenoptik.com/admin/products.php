<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
// SİLME İŞLEMİ
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        // Veritabanı şemasında ON DELETE CASCADE olduğu için ürün silinince, 
        // ona bağlı product_specs (özellikler) ve varyasyonlar da otomatik silinir.
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: products.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

// MESAJ YAKALAMA
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'added') $successMsg = "Ürün başarıyla eklendi!";
    if($_GET['msg'] == 'updated') $successMsg = "Ürün başarıyla güncellendi!";
    if($_GET['msg'] == 'deleted') $successMsg = "Ürün silindi!";
}

// ÜRÜNLERİ GETİRME (Kategori ve Marka isimleriyle birleştirerek)
$query = $db->query("
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    ORDER BY p.id DESC
");
$products = $query->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Ürün Yönetimi</h2>
        <a href="product_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
        </a>
    </div>

    <?php if(isset($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Ürün Adı</th>
                            <th>Marka</th>
                            <th>Kategori</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th class="text-end pe-3">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td class="ps-3"><?= $product->id ?></td>
                            <td><strong><?= htmlspecialchars($product->name) ?></strong></td>
                            <td><?= $product->brand_name ? htmlspecialchars($product->brand_name) : '-' ?></td>
                            <td><?= $product->category_name ? htmlspecialchars($product->category_name) : '-' ?></td>
                            <td>
                                <?php if($product->discount_price > 0): ?>
                                    <del class="text-muted small"><?= $product->base_price ?> ₺</del><br>
                                    <strong class="text-success"><?= $product->discount_price ?> ₺</strong>
                                <?php else: ?>
                                    <strong><?= $product->base_price ?> ₺</strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $product->status == 'active' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>' ?>
                            </td>
                            <td class="text-end pe-3">
                                <!-- Galeri Butonu (YENİ) -->
                                <a href="product_gallery.php?product_id=<?= $product->id ?>" class="btn btn-sm btn-outline-info me-1" title="Çoklu Görsel Galerisi">
                                    <i class="fas fa-images"></i>
                                </a>
                                <!-- Varyasyon (Renk/Stok) Butonu -->
                                <a href="product_variations.php?product_id=<?= $product->id ?>" class="btn btn-sm btn-outline-success me-1" title="Renk ve Stok Varyasyonları">
                                    <i class="fas fa-palette"></i>
                                </a>
                                <!-- Düzenle Butonu -->
                                <a href="product_form.php?id=<?= $product->id ?>" class="btn btn-sm btn-outline-primary me-1" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!-- Sil Butonu -->
                                <a href="?delete=<?= $product->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">
                                <i class="fas fa-glasses fs-3 mb-2 d-block"></i>
                                Henüz ürün eklenmemiş.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>