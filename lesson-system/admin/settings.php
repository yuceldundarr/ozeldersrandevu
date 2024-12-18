<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Admin erişimi için sabit tanımla
define('ADMIN_ACCESS', true);

$success = '';
$error = '';

// Upload dizini oluştur
$upload_dir = '../uploads/settings/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Ayarları kaydet
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach($_POST as $key => $value) {
            if(strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // "setting_" prefix'ini kaldır
                
                // Ayarın tipini kontrol et
                $stmt = $pdo->prepare("SELECT setting_type FROM settings WHERE setting_key = ?");
                $stmt->execute([$setting_key]);
                $setting = $stmt->fetch();
                
                if($setting) {
                    // Boolean değerler için özel işlem
                    if($setting['setting_type'] === 'boolean') {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    }
                    
                    // Güncelleme
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $setting_key]);
                }
            }
        }
        
        // Dosya yüklemeleri
        foreach($_FILES as $key => $file) {
            if(strpos($key, 'setting_') === 0 && $file['error'] === 0) {
                $setting_key = substr($key, 8);
                
                // Dosya türünü kontrol et
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'ico'];
                $filename = $file['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    $new_filename = $setting_key . '_' . time() . '.' . $ext;
                    
                    // Eski dosyayı sil
                    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                    $stmt->execute([$setting_key]);
                    $old_file = $stmt->fetchColumn();
                    
                    if($old_file && file_exists($upload_dir . $old_file)) {
                        unlink($upload_dir . $old_file);
                    }
                    
                    // Yeni dosyayı yükle
                    if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                        $stmt->execute([$new_filename, $setting_key]);
                    }
                }
            }
        }
        
        $pdo->commit();
        $success = "Ayarlar başarıyla güncellendi.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Ayarlar güncellenirken bir hata oluştu: " . $e->getMessage();
    }
}

// Ayarları gruplandırarak getir
$stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_group, id");
$all_settings = $stmt->fetchAll();

$settings = [];
foreach($all_settings as $setting) {
    $settings[$setting['setting_group']][] = $setting;
}

// Ayar grubu başlıkları
$group_titles = [
    'general' => 'Genel Ayarlar',
    'appearance' => 'Görünüm Ayarları',
    'appointment' => 'Randevu Ayarları',
    'email' => 'Email Ayarları'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ayarları - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .admin-content {
            padding: 20px;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            margin-top: 10px;
        }
        .nav-pills .nav-link {
            color: rgba(255,255,255,.8);
        }
        .nav-pills .nav-link:hover {
            color: #fff;
        }
        .nav-pills .nav-link.active {
            background-color: rgba(255,255,255,.1);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar p-0">
                <div class="d-flex flex-column flex-shrink-0 p-3 text-white">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <span class="fs-4">Admin Panel</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link text-white">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="nav-link text-white">
                                <i class="bi bi-people me-2"></i>
                                Kullanıcılar
                            </a>
                        </li>
                        <li>
                            <a href="appointments.php" class="nav-link text-white">
                                <i class="bi bi-calendar-check me-2"></i>
                                Randevular
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="nav-link active">
                                <i class="bi bi-gear me-2"></i>
                                Ayarlar
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="../logout.php">Çıkış Yap</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Site Ayarları</h2>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
                                <?php $first = true; foreach($settings as $group => $group_settings): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                                            id="<?php echo $group; ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo $group; ?>" 
                                            type="button" role="tab">
                                        <?php echo $group_titles[$group]; ?>
                                    </button>
                                </li>
                                <?php $first = false; endforeach; ?>
                            </ul>

                            <div class="tab-content" id="settingsTabsContent">
                                <?php $first = true; foreach($settings as $group => $group_settings): ?>
                                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                                     id="<?php echo $group; ?>" role="tabpanel">
                                    
                                    <?php foreach($group_settings as $setting): ?>
                                        <div class="mb-3">
                                            <label for="setting_<?php echo $setting['setting_key']; ?>" 
                                                   class="form-label">
                                                <?php echo htmlspecialchars($setting['setting_label']); ?>
                                            </label>

                                            <?php if($setting['setting_type'] === 'textarea'): ?>
                                                <textarea class="form-control" 
                                                        id="setting_<?php echo $setting['setting_key']; ?>" 
                                                        name="setting_<?php echo $setting['setting_key']; ?>" 
                                                        rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>

                                            <?php elseif($setting['setting_type'] === 'boolean'): ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="setting_<?php echo $setting['setting_key']; ?>" 
                                                           name="setting_<?php echo $setting['setting_key']; ?>"
                                                           <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                </div>

                                            <?php elseif($setting['setting_type'] === 'image'): ?>
                                                <input type="file" class="form-control" 
                                                       id="setting_<?php echo $setting['setting_key']; ?>" 
                                                       name="setting_<?php echo $setting['setting_key']; ?>" 
                                                       accept="image/*">
                                                <?php if($setting['setting_value']): ?>
                                                    <img src="<?php echo '../uploads/settings/' . htmlspecialchars($setting['setting_value']); ?>" 
                                                         class="preview-image" alt="<?php echo htmlspecialchars($setting['setting_label']); ?>">
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <input type="<?php echo $setting['setting_type']; ?>" 
                                                       class="form-control" 
                                                       id="setting_<?php echo $setting['setting_key']; ?>" 
                                                       name="setting_<?php echo $setting['setting_key']; ?>" 
                                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                                <?php $first = false; endforeach; ?>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
