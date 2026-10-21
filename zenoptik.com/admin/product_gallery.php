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

$upload_dir = '../uploads/products/';

// 1. ÇOKLU GÖRSEL YÜKLEME (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_images'])) {
    
    // Yüklenen dosya var mı kontrolü
    if (!empty(array_filter($_FILES['gallery_images']['name']))) {
        
        $uploaded_count = 0;
        $error_count = 0;
        
        // Dosyaları döngüye al (Çoklu yükleme yapıldığı için)
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['gallery_images']['name'][$key];
            $file_error = $_FILES['gallery_images']['error'][$key];
            
            if ($file_error == 0) {
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $new_name = uniqid('gallery_') . '_' . rand(1000, 9999) . '.' . $ext;
                    
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        // Veritabanına ekle
                        $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$product_id, $new_name]);
                        $uploaded_count++;
                    }
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($uploaded_count > 0) {
            $successMsg = "$uploaded_count adet görsel başarıyla yüklendi.";
        }
        if ($error_count > 0) {
            $errorMsg = "$error_count adet dosya uygun formatta (JPG, PNG, WEBP) olmadığı için yüklenemedi.";
        }
    } else {
        $errorMsg = "Lütfen yüklenecek görselleri seçin.";
    }
}

// 2. GÖRSEL SİLME (GET)
if (isset($_GET['delete'])) {
    $img_id = intval($_GET['delete']);
    try {
        // Önce dosyanın adını bulalım ki sunucudan da silebilelim
        $imgStmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $imgStmt->execute([$img_id]);
        $imgData = $imgStmt->fetch();
        
        if ($imgData && file_exists($upload_dir . $imgData->image_path)) {
            unlink($upload_dir . $imgData->image_path); // Klasörden sil
        }

        // Veritabanından sil
        $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$img_id]);
        
        header("Location: product_gallery.php?product_id=$product_id&msg=deleted");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $successMsg = "Görsel başarıyla silindi.";
}

// ÜRÜNE AİT GÖRSELLERİ ÇEK
$stmtImages = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id DESC");
$stmtImages->execute([$product_id]);
$images = $stmtImages->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Galeri Yönetimi</h2>
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
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $errorMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sol Taraf: Çoklu Görsel Yükleme Formu -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-upload me-1"></i> Yeni Görseller Yükle</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Fotoğrafları Seçin</label>
                            <!-- multiple özelliği sayesinde aynı anda birden fazla fotoğraf seçilebilir -->
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/jpeg, image/png, image/webp" multiple required>
                            <small class="text-muted mt-2 d-block">Ctrl (veya Cmd) tuşuna basılı tutarak birden fazla fotoğraf seçebilirsiniz.</small>
                        </div>
                        <button type="submit" name="upload_images" class="btn btn-primary w-100">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Görselleri Yükle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: Galeri Görüntüleme -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Mevcut Galeri Görselleri</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach($images as $img): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm position-relative">
                                <!-- Resmi karesel ve düzgün göstermek için object-fit kullandık -->
                                <img src="../uploads/products/<?= $img->image_path ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Ürün Görseli">
                                <div class="card-body p-2 text-center">
                                    <a href="?product_id=<?= $product_id ?>&delete=<?= $img->id ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?');">
                                        <i class="fas fa-trash me-1"></i> Sil
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($images)): ?>
                        <div class="col-12 text-center text-muted p-5">
                            <i class="fas fa-images fs-1 mb-3 text-light"></i>
                            <p>Bu ürüne henüz galeri görseli eklenmemiş.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>