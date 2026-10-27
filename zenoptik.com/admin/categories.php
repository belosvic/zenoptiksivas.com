<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
function seoUrl($string) {
    $string = mb_strtolower(trim($string), 'UTF-8');
    $string = str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'o', 's', 'u'], $string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// MESAJ YAKALAMA (Düzenleme sonrası yönlendirmeden gelen)
if(isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $successMsg = "Kategori başarıyla güncellendi!";
}

// 1. KATEGORİ EKLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = seoUrl($name);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;
    $status = $_POST['status'];

    try {
        $stmt = $db->prepare("INSERT INTO categories (parent_id, name, slug, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$parent_id, $name, $slug, $status]);
        $successMsg = "Kategori başarıyla eklendi!";
    } catch (PDOException $e) {
        $errorMsg = "Hata oluştu: " . $e->getMessage();
    }
}

// 2. KATEGORİ GÜNCELLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $slug = seoUrl($name);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;
    $status = $_POST['status'];

    try {
        $stmt = $db->prepare("UPDATE categories SET parent_id = ?, name = ?, slug = ?, status = ? WHERE id = ?");
        $stmt->execute([$parent_id, $name, $slug, $status, $id]);
        header("Location: categories.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}

// 3. KATEGORİ SİLME İŞLEMİ (GET)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: categories.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

// 4. DÜZENLENECEK KATEGORİYİ GETİRME (GET)
$editData = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

// KATEGORİLERİ LİSTELEME İŞLEMİ
$query = $db->query("
    SELECT c1.*, c2.name as parent_name 
    FROM categories c1 
    LEFT JOIN categories c2 ON c1.parent_id = c2.id 
    ORDER BY c1.parent_id ASC, c1.id DESC
");
$categories = $query->fetchAll();

// ÜST KATEGORİ SEÇİMİ İÇİN (Sadece ana kategoriler ve kendisi hariç)
$parentCategories = $db->query("SELECT id, name FROM categories WHERE parent_id IS NULL")->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kategori Yönetimi</h2>
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
                        <?= $editData ? '<i class="fas fa-edit me-1"></i> Kategoriyi Düzenle' : 'Yeni Kategori Ekle' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="categories.php">
                        
                        <?php if($editData): ?>
                            <!-- Düzenleme modunda ID'yi gizli olarak gönderiyoruz -->
                            <input type="hidden" name="category_id" value="<?= $editData->id ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Kategori Adı <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Örn: Güneş Gözlüğü" 
                                   value="<?= $editData ? htmlspecialchars($editData->name) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Üst Kategori</label>
                            <select name="parent_id" class="form-select">
                                <option value="">(Ana Kategori Olsun)</option>
                                <?php foreach($parentCategories as $pCat): ?>
                                    <!-- Bir kategoriyi kendi altına eklemeyi engelliyoruz -->
                                    <?php if($editData && $editData->id == $pCat->id) continue; ?>
                                    
                                    <option value="<?= $pCat->id ?>" <?= ($editData && $editData->parent_id == $pCat->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pCat->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($editData && $editData->status == 'active') ? 'selected' : '' ?>>Aktif (Sitede Görünsün)</option>
                                <option value="passive" <?= ($editData && $editData->status == 'passive') ? 'selected' : '' ?>>Pasif (Gizle)</option>
                            </select>
                        </div>
                        
                        <?php if($editData): ?>
                            <button type="submit" name="update_category" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-sync me-2"></i>Güncelle
                            </button>
                            <a href="categories.php" class="btn btn-outline-secondary w-100">İptal</a>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">
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
                    <h6 class="mb-0">Kayıtlı Kategoriler</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Kategori Adı</th>
                                    <th>Durum</th>
                                    <th class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $category): ?>
                                <tr <?= ($editData && $editData->id == $category->id) ? 'class="table-warning"' : '' ?>>
                                    <td class="ps-3"><?= $category->id ?></td>
                                    <td>
                                        <?php if($category->parent_name): ?>
                                            <span class="text-muted small"><?= htmlspecialchars($category->parent_name) ?> &rsaquo; </span>
                                            <strong><?= htmlspecialchars($category->name) ?></strong>
                                        <?php else: ?>
                                            <strong class="text-primary"><?= htmlspecialchars($category->name) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($category->status == 'active'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <!-- Düzenle Butonu Eklendi -->
                                        <a href="?edit=<?= $category->id ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <a href="?delete=<?= $category->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">
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