<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['product_id']);
$productStmt = $db->prepare("SELECT name FROM products WHERE id = ?");
$productStmt->execute([$product_id]);
$product = $productStmt->fetch();

if (!$product) {
    echo "<div class='alert alert-danger m-4'>Ürün bulunamadı!</div>";
    include 'includes/footer.php';
    exit;
}

if(isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $successMsg = "Varyasyon başarıyla güncellendi!";
}

// GÖRSEL YÜKLEME DİZİNİ KONTROLÜ
$upload_dir = '../uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. VARYASYON EKLEME (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_variation'])) {
    $sku = trim($_POST['sku']);
    $frame_color = trim($_POST['frame_color']);
    $glass_color = trim($_POST['glass_color']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $variation_image = NULL;

    // Görsel Yüklendiyse
    if (isset($_FILES['variation_image']) && $_FILES['variation_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['variation_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('optik_') . '.' . $ext;
            if (move_uploaded_file($_FILES['variation_image']['tmp_name'], $upload_dir . $new_name)) {
                $variation_image = $new_name;
            }
        } else {
            $errorMsg = "Sadece JPG, PNG ve WEBP formatları yüklenebilir.";
        }
    }

    if(!isset($errorMsg)) {
        try {
            $stmt = $db->prepare("INSERT INTO product_variations (product_id, sku, frame_color, glass_color, stock_quantity, variation_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $sku, $frame_color, $glass_color, $stock_quantity, $variation_image]);
            $successMsg = "Renk varyasyonu ve görseli başarıyla eklendi!";
        } catch (PDOException $e) {
            $errorMsg = "Hata (SKU daha önce kullanılmış olabilir): " . $e->getMessage();
        }
    }
}

// 2. VARYASYON GÜNCELLEME (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_variation'])) {
    $var_id = $_POST['variation_id'];
    $sku = trim($_POST['sku']);
    $frame_color = trim($_POST['frame_color']);
    $glass_color = trim($_POST['glass_color']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $current_image = $_POST['current_image'];
    $variation_image = $current_image;

    // Yeni Görsel Yüklendiyse
    if (isset($_FILES['variation_image']) && $_FILES['variation_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['variation_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('optik_') . '.' . $ext;
            if (move_uploaded_file($_FILES['variation_image']['tmp_name'], $upload_dir . $new_name)) {
                $variation_image = $new_name;
                
                // Eski görseli klasörden sil
                if($current_image && file_exists($upload_dir . $current_image)) {
                    unlink($upload_dir . $current_image);
                }
            }
        }
    }

    try {
        $stmt = $db->prepare("UPDATE product_variations SET sku=?, frame_color=?, glass_color=?, stock_quantity=?, variation_image=? WHERE id=?");
        $stmt->execute([$sku, $frame_color, $glass_color, $stock_quantity, $variation_image, $var_id]);
        header("Location: product_variations.php?product_id=$product_id&msg=updated");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}

// 3. VARYASYON SİLME (GET)
if (isset($_GET['delete'])) {
    $var_id = intval($_GET['delete']);
    try {
        // Önce görseli bulup sunucudan silelim (yer kaplamasın)
        $imgStmt = $db->prepare("SELECT variation_image FROM product_variations WHERE id = ?");
        $imgStmt->execute([$var_id]);
        $imgData = $imgStmt->fetch();
        
        if($imgData && $imgData->variation_image && file_exists($upload_dir . $imgData->variation_image)) {
            unlink($upload_dir . $imgData->variation_image);
        }

        $stmt = $db->prepare("DELETE FROM product_variations WHERE id = ?");
        $stmt->execute([$var_id]);
        header("Location: product_variations.php?product_id=$product_id");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

// DÜZENLENECEK VARYASYONU GETİRME (GET)
$editData = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM product_variations WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

$stmtVars = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY id DESC");
$stmtVars->execute([$product_id]);
$variations = $stmtVars->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Varyasyon Yönetimi</h2>
            <p class="text-muted mb-0">Ürün: <strong><?= htmlspecialchars($product->name) ?></strong></p>
        </div>
        <a href="products.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Ürünlere Dön</a>
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
        <!-- Sol Taraf: Dinamik Form -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header <?= $editData ? 'bg-warning text-dark' : 'bg-white' ?>">
                    <h6 class="mb-0">
                        <?= $editData ? '<i class="fas fa-edit me-1"></i> Varyasyonu Düzenle' : 'Yeni Renk Ekle' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Görsel yükleyebilmek için enctype eklendi -->
                    <form method="POST" action="product_variations.php?product_id=<?= $product_id ?>" enctype="multipart/form-data">
                        
                        <?php if($editData): ?>
                            <input type="hidden" name="variation_id" value="<?= $editData->id ?>">
                            <input type="hidden" name="current_image" value="<?= $editData->variation_image ?>">
                        <?php endif; ?>

                        <!-- Görsel Önizleme (Düzenleme modunda) -->
                        <?php if($editData && $editData->variation_image): ?>
                            <div class="mb-3 text-center">
                                <img src="../uploads/products/<?= $editData->variation_image ?>" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Ürün Görseli</label>
                            <input type="file" name="variation_image" class="form-control" accept="image/jpeg, image/png, image/webp">
                            <small class="text-muted">Bu renk kombinasyonuna ait fotoğraf.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SKU (Barkod) <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" required placeholder="Örn: RB3025-001/51" 
                                   value="<?= $editData ? htmlspecialchars($editData->sku) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Çerçeve Rengi <span class="text-danger">*</span></label>
                            <input type="text" name="frame_color" class="form-control" required placeholder="Örn: Siyah" 
                                   value="<?= $editData ? htmlspecialchars($editData->frame_color) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cam Rengi</label>
                            <input type="text" name="glass_color" class="form-control" placeholder="Örn: Füme" 
                                   value="<?= $editData ? htmlspecialchars($editData->glass_color) : '' ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Stok Miktarı <span class="text-danger">*</span></label>
                            <input type="number" name="stock_quantity" class="form-control" required min="0"
                                   value="<?= $editData ? $editData->stock_quantity : '0' ?>">
                        </div>
                        
                        <?php if($editData): ?>
                            <button type="submit" name="update_variation" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-sync me-2"></i>Güncelle
                            </button>
                            <a href="product_variations.php?product_id=<?= $product_id ?>" class="btn btn-outline-secondary w-100">İptal</a>
                        <?php else: ?>
                            <button type="submit" name="add_variation" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: Bu Ürünün Varyasyonları -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Ekli Varyasyonlar</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Görsel</th>
                                    <th>SKU</th>
                                    <th>Renkler (Çerçeve/Cam)</th>
                                    <th>Stok</th>
                                    <th class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($variations as $var): ?>
                                <tr>
                                    <td class="ps-3">
                                        <?php if($var->variation_image): ?>
                                            <img src="../uploads/products/<?= $var->variation_image ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                                <i class="fas fa-camera"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($var->sku) ?></small></td>
                                    <td>
                                        <strong><?= htmlspecialchars($var->frame_color) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($var->glass_color) ?></small>
                                    </td>
                                    <td>
                                        <?php if($var->stock_quantity <= 5 && $var->stock_quantity > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $var->stock_quantity ?> (Kritik)</span>
                                        <?php elseif($var->stock_quantity == 0): ?>
                                            <span class="badge bg-danger">Tükendi</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $var->stock_quantity ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="?product_id=<?= $product_id ?>&edit=<?= $var->id ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?product_id=<?= $product_id ?>&delete=<?= $var->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu varyasyonu silmek istediğinize emin misiniz?');">
                                            <i class="fas fa-trash"></i>
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