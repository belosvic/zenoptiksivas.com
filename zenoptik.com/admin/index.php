<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
// Veritabanından gerçek verileri çekiyoruz
try {
    // 1. Toplam Ürün Sayısı
    $urunSorgu = $db->query("SELECT COUNT(id) as toplam FROM products")->fetch();
    $toplamUrun = $urunSorgu->toplam;

    // 2. Kritik Stok (Stoğu 5 ve altında olan varyasyonlar)
    $stokSorgu = $db->query("SELECT COUNT(id) as kritik FROM product_variations WHERE stock_quantity <= 5")->fetch();
    $kritikStok = $stokSorgu->kritik;

    // 3. Toplam Marka
    $markaSorgu = $db->query("SELECT COUNT(id) as toplam FROM brands")->fetch();
    $toplamMarka = $markaSorgu->toplam;

    // 4. Toplam Kategori
    $kategoriSorgu = $db->query("SELECT COUNT(id) as toplam FROM categories")->fetch();
    $toplamKategori = $kategoriSorgu->toplam;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Veri çekme hatası: " . $e->getMessage() . "</div>";
}
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard</h2>
    </div>

    <!-- Gerçek İstatistik Kartları -->
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-3" style="border-left: 4px solid #3498db;">
                <div class="card-body">
                    <h6 class="text-muted">Toplam Ürün</h6>
                    <h3><?= $toplamUrun ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm mb-3" style="border-left: 4px solid #e74c3c;">
                <div class="card-body">
                    <h6 class="text-muted">Kritik Stok Uyarıları</h6>
                    <h3><?= $kritikStok ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm mb-3" style="border-left: 4px solid #9b59b6;">
                <div class="card-body">
                    <h6 class="text-muted">Aktif Markalar</h6>
                    <h3><?= $toplamMarka ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm mb-3" style="border-left: 4px solid #2ecc71;">
                <div class="card-body">
                    <h6 class="text-muted">Kategoriler</h6>
                    <h3><?= $toplamKategori ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Hızlı İşlemler</h5>
        </div>
        <div class="card-body">
            <a href="products.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Ürün Ekle</a>
            <a href="brands.php" class="btn btn-outline-secondary"><i class="fas fa-star"></i> Marka Yönetimi</a>
            <a href="categories.php" class="btn btn-outline-secondary"><i class="fas fa-tags"></i> Kategori Yönetimi</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>