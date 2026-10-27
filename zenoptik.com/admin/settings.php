<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
$upload_dir = '../uploads/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// FORMU GÖNDERME (GÜNCELLEME) İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    
    $site_title = trim($_POST['site_title']);
    $site_description = trim($_POST['site_description']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $contact_phone_2 = trim($_POST['contact_phone_2']); // 2. Telefon eklendi
    $whatsapp_phone = trim($_POST['whatsapp_phone']);
    $address = trim($_POST['address']);
    $instagram_url = trim($_POST['instagram_url']);
    $facebook_url = trim($_POST['facebook_url']);
    $footer_text = trim($_POST['footer_text']);
    
    $current_logo = $_POST['current_logo'];
    $site_logo = $current_logo;

    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        
        if (in_array($ext, $allowed)) {
            $new_name = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $new_name)) {
                $site_logo = $new_name;
                
                if($current_logo && file_exists($upload_dir . $current_logo)) {
                    unlink($upload_dir . $current_logo);
                }
            }
        } else {
            $errorMsg = "Logo için sadece JPG, PNG, WEBP ve SVG formatları desteklenir.";
        }
    }

    if(!isset($errorMsg)) {
        try {
            // SQL sorgusuna contact_phone_2 eklendi
            $stmt = $db->prepare("UPDATE settings SET site_title=?, site_description=?, site_logo=?, contact_email=?, contact_phone=?, contact_phone_2=?, whatsapp_phone=?, address=?, instagram_url=?, facebook_url=?, footer_text=? WHERE id=1");
            $stmt->execute([$site_title, $site_description, $site_logo, $contact_email, $contact_phone, $contact_phone_2, $whatsapp_phone, $address, $instagram_url, $facebook_url, $footer_text]);
            
            $successMsg = "Site ayarları başarıyla güncellendi!";
        } catch (PDOException $e) {
            $errorMsg = "Güncelleme hatası: " . $e->getMessage();
        }
    }
}

// AYARLARI VERİTABANINDAN ÇEK
$stmt = $db->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Site Ayarları</h2>
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

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-9">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#general"><i class="fas fa-globe me-1"></i>Genel & SEO</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#contact"><i class="fas fa-address-book me-1"></i>İletişim & Sosyal Medya</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#logo"><i class="fas fa-image me-1"></i>Logo & Tasarım</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content">
                            
                            <!-- 1. SEKME: GENEL & SEO -->
                            <div class="tab-pane fade show active" id="general">
                                <div class="mb-3">
                                    <label class="form-label">Site Başlığı (Title)</label>
                                    <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($settings->site_title ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Site Açıklaması (Description)</label>
                                    <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings->site_description ?? '') ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alt Kısım (Footer) Yazısı</label>
                                    <textarea name="footer_text" class="form-control" rows="2"><?= htmlspecialchars($settings->footer_text ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- 2. SEKME: İLETİŞİM & SOSYAL MEDYA -->
                            <div class="tab-pane fade" id="contact">
                                <div class="mb-3">
                                    <label class="form-label">E-Posta Adresi</label>
                                    <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings->contact_email ?? '') ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefon Numarası 1</label>
                                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings->contact_phone ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefon Numarası 2</label>
                                        <input type="text" name="contact_phone_2" class="form-control" value="<?= htmlspecialchars($settings->contact_phone_2 ?? '') ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-success fw-bold"><i class="fab fa-whatsapp me-1"></i> WhatsApp Numarası</label>
                                    <input type="text" name="whatsapp_phone" class="form-control border-success" placeholder="Örn: 905551234567" value="<?= htmlspecialchars($settings->whatsapp_phone ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Açık Adres</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($settings->address ?? '') ?></textarea>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fab fa-instagram text-danger me-1"></i> Instagram Linki</label>
                                        <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($settings->instagram_url ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fab fa-facebook text-primary me-1"></i> Facebook Linki</label>
                                        <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($settings->facebook_url ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. SEKME: LOGO -->
                            <div class="tab-pane fade" id="logo">
                                <input type="hidden" name="current_logo" value="<?= htmlspecialchars($settings->site_logo ?? '') ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Mevcut Logo</label>
                                    <div class="p-3 border rounded bg-light text-center" style="max-width: 300px;">
                                        <?php if(!empty($settings->site_logo)): ?>
                                            <img src="../uploads/<?= htmlspecialchars($settings->site_logo) ?>" class="img-fluid" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="text-muted">Henüz logo yüklenmedi.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Yeni Logo Yükle</label>
                                    <input type="file" name="site_logo" class="form-control" accept="image/jpeg, image/png, image/webp, image/svg+xml">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">İşlemler</div>
                    <div class="card-body">
                        <button type="submit" name="update_settings" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Ayarları Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>