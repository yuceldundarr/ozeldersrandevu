<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Upload dizini oluştur
$upload_dir = '../uploads/users/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ID kontrolü
if(!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = sanitize($_GET['id']);

// Kullanıcı bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    header('Location: users.php');
    exit();
}

// Form gönderildiğinde
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $user_type = sanitize($_POST['user_type']);
    
    // Validasyon
    if(empty($name) || empty($email) || empty($phone) || empty($user_type)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        // Email kontrolü (mevcut kullanıcı hariç)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if($stmt->fetch()) {
            $error = "Bu email adresi zaten kullanılıyor.";
        } else {
            // Profil fotoğrafı yükleme
            $profile_image = $user['profile_image'];
            if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                    if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                        // Eski dosyayı sil
                        if($profile_image && file_exists($upload_dir . $profile_image)) {
                            unlink($upload_dir . $profile_image);
                        }
                        $profile_image = $new_filename;
                    }
                }
            }

            // Banner fotoğrafı yükleme
            $banner_image = $user['banner_image'];
            if(isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['banner_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    $new_filename = 'banner_' . $user_id . '_' . time() . '.' . $ext;
                    if(move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $new_filename)) {
                        // Eski dosyayı sil
                        if($banner_image && file_exists($upload_dir . $banner_image)) {
                            unlink($upload_dir . $banner_image);
                        }
                        $banner_image = $new_filename;
                    }
                }
            }

            // Şifre değiştirilecek mi?
            $password_sql = '';
            $params = [$name, $email, $phone, $user_type, $profile_image, $banner_image, $user_id];
            
            if(!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_sql = ", password = ?";
                array_splice($params, -1, 0, [$password]);
            }
            
            // Güncelleme
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone = ?, user_type = ?, 
                    profile_image = ?, banner_image = ?" . $password_sql . "
                WHERE id = ?
            ");
            
            if($stmt->execute($params)) {
                $success = "Kullanıcı başarıyla güncellendi.";
            } else {
                $error = "Güncelleme sırasında bir hata oluştu.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Admin Panel</title>
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
            object-fit: cover;
            margin-top: 10px;
        }
        .banner-preview {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            margin-top: 10px;
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
                            <a href="users.php" class="nav-link active text-white">
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
                            <a href="settings.php" class="nav-link text-white">
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
                    <h2>Kullanıcı Düzenle</h2>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Geri Dön
                    </a>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">İsim</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="user_type" class="form-label">Kullanıcı Tipi</label>
                                        <select class="form-select" id="user_type" name="user_type" required>
                                            <option value="student" <?php echo $user['user_type'] === 'student' ? 'selected' : ''; ?>>
                                                Öğrenci
                                            </option>
                                            <option value="teacher" <?php echo $user['user_type'] === 'teacher' ? 'selected' : ''; ?>>
                                                Öğretmen
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Yeni Şifre (Boş bırakılabilir)</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="profile_image" class="form-label">Profil Fotoğrafı</label>
                                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                        <?php if($user['profile_image']): ?>
                                            <img src="<?php echo '../uploads/users/' . htmlspecialchars($user['profile_image']); ?>" 
                                                 class="preview-image" alt="Profil Fotoğrafı">
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label for="banner_image" class="form-label">Banner Fotoğrafı</label>
                                        <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                                        <?php if($user['banner_image']): ?>
                                            <img src="<?php echo '../uploads/users/' . htmlspecialchars($user['banner_image']); ?>" 
                                                 class="banner-preview" alt="Banner Fotoğrafı">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Güncelle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
