<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
// SEO URL Fonksiyonu
function seoUrl($string) {
    $string = mb_strtolower(trim($string), 'UTF-8');
    $string = str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'o', 's', 'u'], $string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// MESAJ YAKALAMA (Düzenleme sonrası)
if(isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $successMsg = "Marka başarıyla güncellendi!";
}

// 1. MARKA EKLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    $slug = seoUrl($name);
    $status = $_POST['status'];

    try {
        $stmt = $db->prepare("INSERT INTO brands (name, slug, status) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $status]);
        $successMsg = "Marka başarıyla eklendi!";
    } catch (PDOException $e) {
        $errorMsg = "Hata oluştu: " . $e->getMessage();
    }
}

// 2. MARKA GÜNCELLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_brand'])) {
    $id = $_POST['brand_id'];
    $name = trim($_POST['name']);
    $slug = seoUrl($name);
    $status = $_POST['status'];

    try {
        $stmt = $db->prepare("UPDATE brands SET name = ?, slug = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $status, $id]);
        header("Location: brands.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}

// 3. MARKA SİLME İŞLEMİ (GET)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: brands.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

// 4. DÜZENLENECEK MARKAYI GETİRME (GET)
$editData = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

// MARKALARI LİSTELEME İŞLEMİ
$brands = $db->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Marka Yönetimi</h2>
    </div>

    <?php if(isset($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($errorMsg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $errorMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sol Taraf: Dinamik Form (Ekle / Düzenle) -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header <?= $editData ? 'bg-warning text-dark' : 'bg-white' ?>">
                    <h6 class="mb-0">
                        <?= $editData ? '<i class="fas fa-edit me-1"></i> Markayı Düzenle' : 'Yeni Marka Ekle' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="brands.php">
                        
                        <?php if($editData): ?>
                            <!-- Düzenleme modunda ID'yi gizli gönderiyoruz -->
                            <input type="hidden" name="brand_id" value="<?= $editData->id ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Marka Adı <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Örn: Ray-Ban" 
                                   value="<?= $editData ? htmlspecialchars($editData->name) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($editData && $editData->status == 'active') ? 'selected' : '' ?>>Aktif (Sitede Görünsün)</option>
                                <option value="passive" <?= ($editData && $editData->status == 'passive') ? 'selected' : '' ?>>Pasif (Gizle)</option>
                            </select>
                        </div>
                        
                        <?php if($editData): ?>
                            <button type="submit" name="update_brand" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-sync me-2"></i>Güncelle
                            </button>
                            <a href="brands.php" class="btn btn-outline-secondary w-100">İptal</a>
                        <?php else: ?>
                            <button type="submit" name="add_brand" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: Liste Tablosu -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Kayıtlı Markalar</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Marka Adı</th>
                                    <th>Durum</th>
                                    <th class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($brands as $brand): ?>
                                <tr <?= ($editData && $editData->id == $brand->id) ? 'class="table-warning"' : '' ?>>
                                    <td class="ps-3"><?= $brand->id ?></td>
                                    <td><strong><?= htmlspecialchars($brand->name) ?></strong></td>
                                    <td>
                                        <?php if($brand->status == 'active'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <!-- Düzenle Butonu -->
                                        <a href="?edit=<?= $brand->id ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <a href="?delete=<?= $brand->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= $brand->name ?> markasını silmek istediğinize emin misiniz?');">
                                            <i class="fas fa-trash"></i> Sil
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>