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

$editData = null;
$editSpecs = null;
$isEdit = false;
$upload_dir = '../uploads/products/';

// KLASÖR KONTROLÜ
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// DÜZENLEME MODU KONTROLÜ
if (isset($_GET['id'])) {
    $isEdit = true;
    $id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
    
    $stmtSpecs = $db->prepare("SELECT * FROM product_specs WHERE product_id = ?");
    $stmtSpecs->execute([$id]);
    $editSpecs = $stmtSpecs->fetch();
}

// FORM GÖNDERİLDİĞİNDE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    
    $name = trim($_POST['name']);
    $slug = seoUrl($name);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : NULL;
    $gender = $_POST['gender'];
    $base_price = $_POST['base_price'];
    $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : NULL;
    $description = $_POST['description'];
    $status = $_POST['status'];
    
    // Mevcut görseli tut
    $main_image = $isEdit ? $editData->main_image : NULL;

    // YENİ GÖRSEL YÜKLENDİYSE
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('main_') . '.' . $ext;
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $new_name)) {
                $main_image = $new_name;
                
                // Güncelleme yapılıyorsa ve eski görsel varsa sunucudan sil
                if($isEdit && $editData->main_image && file_exists($upload_dir . $editData->main_image)) {
                    unlink($upload_dir . $editData->main_image);
                }
            }
        } else {
            $errorMsg = "Sadece JPG, PNG ve WEBP formatları yüklenebilir.";
        }
    }

    // Optik Spesifikasyonlar
    $ekartman = !empty($_POST['ekartman']) ? $_POST['ekartman'] : NULL;
    $kopru = !empty($_POST['kopru']) ? $_POST['kopru'] : NULL;
    $sap_uzunlugu = !empty($_POST['sap_uzunlugu']) ? $_POST['sap_uzunlugu'] : NULL;
    $frame_shape = $_POST['frame_shape'];
    $frame_material = $_POST['frame_material'];
    $glass_feature = $_POST['glass_feature'];
    $suitable_face_shapes = $_POST['suitable_face_shapes'];

    if(!isset($errorMsg)) {
        try {
            $db->beginTransaction();

            if ($isEdit) {
                // GÜNCELLEME
                $stmt = $db->prepare("UPDATE products SET category_id=?, brand_id=?, name=?, slug=?, main_image=?, description=?, gender=?, base_price=?, discount_price=?, status=? WHERE id=?");
                $stmt->execute([$category_id, $brand_id, $name, $slug, $main_image, $description, $gender, $base_price, $discount_price, $status, $id]);
                
                if ($editSpecs) {
                    $stmtSpecs = $db->prepare("UPDATE product_specs SET ekartman=?, kopru=?, sap_uzunlugu=?, frame_shape=?, frame_material=?, glass_feature=?, suitable_face_shapes=? WHERE product_id=?");
                    $stmtSpecs->execute([$ekartman, $kopru, $sap_uzunlugu, $frame_shape, $frame_material, $glass_feature, $suitable_face_shapes, $id]);
                } else {
                    $stmtSpecs = $db->prepare("INSERT INTO product_specs (product_id, ekartman, kopru, sap_uzunlugu, frame_shape, frame_material, glass_feature, suitable_face_shapes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtSpecs->execute([$id, $ekartman, $kopru, $sap_uzunlugu, $frame_shape, $frame_material, $glass_feature, $suitable_face_shapes]);
                }
                
                $db->commit();
                header("Location: products.php?msg=updated");
                exit;
                
            } else {
                // YENİ EKLEME
                $stmt = $db->prepare("INSERT INTO products (category_id, brand_id, name, slug, main_image, description, gender, base_price, discount_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $brand_id, $name, $slug, $main_image, $description, $gender, $base_price, $discount_price, $status]);
                
                $new_product_id = $db->lastInsertId();
                
                $stmtSpecs = $db->prepare("INSERT INTO product_specs (product_id, ekartman, kopru, sap_uzunlugu, frame_shape, frame_material, glass_feature, suitable_face_shapes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtSpecs->execute([$new_product_id, $ekartman, $kopru, $sap_uzunlugu, $frame_shape, $frame_material, $glass_feature, $suitable_face_shapes]);
                
                $db->commit();
                header("Location: products.php?msg=added");
                exit;
            }

        } catch (PDOException $e) {
            $db->rollBack();
            $errorMsg = "Veritabanı Hatası: " . $e->getMessage();
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $isEdit ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle' ?></h2>
        <a href="products.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Listeye Dön</a>
    </div>

    <?php if(isset($errorMsg)): ?>
        <div class="alert alert-danger"><?= $errorMsg ?></div>
    <?php endif; ?>

    <!-- Dosya yükleme (enctype) eklendi -->
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <ul class="nav nav-tabs mb-3" id="productTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#general"><i class="fas fa-info-circle me-1"></i>Genel Bilgiler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#specs"><i class="fas fa-ruler-combined me-1"></i>Optik Özellikler</a>
                    </li>
                </ul>

                <div class="tab-content bg-white p-4 border border-top-0 rounded-bottom shadow-sm">
                    
                    <!-- TAB 1: GENEL BİLGİLER -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="mb-3">
                            <label class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= $editData ? htmlspecialchars($editData->name) : '' ?>" placeholder="Örn: Ray-Ban Aviator RB3025">
                        </div>

                        <!-- ANA GÖRSEL YÜKLEME ALANI EKLENDİ -->
                        <div class="mb-4 bg-light p-3 border rounded">
                            <label class="form-label fw-bold">Ana Ürün Kapak Görseli</label>
                            <?php if($editData && $editData->main_image): ?>
                                <div class="mb-2">
                                    <img src="../uploads/products/<?= $editData->main_image ?>" class="img-thumbnail" style="height: 100px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="main_image" class="form-control" accept="image/jpeg, image/png, image/webp">
                            <small class="text-muted">Kategori ve anasayfa listelemesinde kullanılacak kapak fotoğrafı.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Normal Fiyat (₺) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="base_price" class="form-control" required 
                                       value="<?= $editData ? $editData->base_price : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İndirimli Fiyat (₺)</label>
                                <input type="number" step="0.01" name="discount_price" class="form-control" 
                                       value="<?= $editData ? $editData->discount_price : '' ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ürün Açıklaması</label>
                            <textarea name="description" class="form-control" rows="4"><?= $editData ? htmlspecialchars($editData->description) : '' ?></textarea>
                        </div>
                    </div>

                    <!-- TAB 2: OPTİK ÖZELLİKLER -->
                    <div class="tab-pane fade" id="specs">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ekartman (mm)</label>
                                <input type="number" name="ekartman" class="form-control" placeholder="Örn: 52"
                                       value="<?= $editSpecs ? $editSpecs->ekartman : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Köprü (mm)</label>
                                <input type="number" name="kopru" class="form-control" placeholder="Örn: 18"
                                       value="<?= $editSpecs ? $editSpecs->kopru : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sap Uzunluğu (mm)</label>
                                <input type="number" name="sap_uzunlugu" class="form-control" placeholder="Örn: 140"
                                       value="<?= $editSpecs ? $editSpecs->sap_uzunlugu : '' ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Çerçeve Formu</label>
                                <input type="text" name="frame_shape" class="form-control" placeholder="Örn: Damla, Yuvarlak, Kedi Gözü"
                                       value="<?= $editSpecs ? htmlspecialchars($editSpecs->frame_shape) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Çerçeve Materyali</label>
                                <input type="text" name="frame_material" class="form-control" placeholder="Örn: Asetat, Titanyum, Metal"
                                       value="<?= $editSpecs ? htmlspecialchars($editSpecs->frame_material) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cam Özelliği</label>
                                <input type="text" name="glass_feature" class="form-control" placeholder="Örn: Polarize, UV400, Degrade"
                                       value="<?= $editSpecs ? htmlspecialchars($editSpecs->glass_feature) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Uygun Yüz Tipleri</label>
                                <input type="text" name="suitable_face_shapes" class="form-control" placeholder="Örn: Oval, Kare"
                                       value="<?= $editSpecs ? htmlspecialchars($editSpecs->suitable_face_shapes) : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ Taraf: Ayarlar -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">Yayın Ayarları</div>
                    <div class="card-body">
                        <button type="submit" name="save_product" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-save me-2"></i><?= $isEdit ? 'Değişiklikleri Kaydet' : 'Ürünü Kaydet' ?>
                        </button>
                        
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select mb-3">
                            <option value="active" <?= ($editData && $editData->status == 'active') ? 'selected' : '' ?>>Aktif (Yayında)</option>
                            <option value="passive" <?= ($editData && $editData->status == 'passive') ? 'selected' : '' ?>>Pasif (Gizli)</option>
                        </select>

                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select mb-3">
                            <option value="">-- Kategori Seç --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= ($editData && $editData->category_id == $cat->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label">Marka</label>
                        <select name="brand_id" class="form-select mb-3">
                            <option value="">-- Marka Seç --</option>
                            <?php foreach($brands as $brand): ?>
                                <option value="<?= $brand->id ?>" <?= ($editData && $editData->brand_id == $brand->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label class="form-label">Cinsiyet</label>
                        <select name="gender" class="form-select">
                            <option value="Unisex" <?= ($editData && $editData->gender == 'Unisex') ? 'selected' : '' ?>>Unisex</option>
                            <option value="Erkek" <?= ($editData && $editData->gender == 'Erkek') ? 'selected' : '' ?>>Erkek</option>
                            <option value="Kadın" <?= ($editData && $editData->gender == 'Kadın') ? 'selected' : '' ?>>Kadın</option>
                            <option value="Çocuk" <?= ($editData && $editData->gender == 'Çocuk') ? 'selected' : '' ?>>Çocuk</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>