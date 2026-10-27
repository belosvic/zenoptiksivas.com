<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
// MÜŞTERİ DURUMUNU GÜNCELLEME (Aktif/Pasif - Banlama)
if (isset($_GET['status_id']) && isset($_GET['type'])) {
    $id = intval($_GET['status_id']);
    $type = $_GET['type'] == 'active' ? 'active' : 'passive';
    
    try {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$type, $id]);
        header("Location: customers.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Durum güncellenirken hata oluştu: " . $e->getMessage();
    }
}

// MÜŞTERİ SİLME
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: customers.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Silme hatası: " . $e->getMessage();
    }
}

// MESAJ YAKALAMA
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'updated') $successMsg = "Müşteri durumu güncellendi.";
    if($_GET['msg'] == 'deleted') $successMsg = "Müşteri sistemden silindi.";
}

// MÜŞTERİLERİ GETİR
$users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="content-area flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Müşteri Yönetimi</h2>
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

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">Kayıtlı Müşteriler</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Ad Soyad</th>
                            <th>İletişim</th>
                            <th>Kayıt Tarihi</th>
                            <th>Durum</th>
                            <th class="text-end pe-3">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="ps-3"><?= $user->id ?></td>
                            <td><strong><?= htmlspecialchars($user->full_name) ?></strong></td>
                            <td>
                                <div><i class="fas fa-envelope text-muted me-1"></i> <?= htmlspecialchars($user->email) ?></div>
                                <?php if($user->phone): ?>
                                <div><i class="fas fa-phone text-muted me-1"></i> <?= htmlspecialchars($user->phone) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($user->created_at)) ?></td>
                            <td>
                                <?php if($user->status == 'active'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pasif (Engelli)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if($user->status == 'active'): ?>
                                    <a href="?status_id=<?= $user->id ?>&type=passive" class="btn btn-sm btn-outline-warning me-1" title="Hesabı Engelle">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?status_id=<?= $user->id ?>&type=active" class="btn btn-sm btn-outline-success me-1" title="Engeli Kaldır">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?delete=<?= $user->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu müşteriyi tamamen silmek istediğinize emin misiniz?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted p-4">
                                <i class="fas fa-users fs-3 mb-2 d-block"></i>
                                Henüz siteye kayıtlı müşteri bulunmuyor.
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