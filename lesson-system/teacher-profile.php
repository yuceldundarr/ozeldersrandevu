<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
checkLogin();

// Sadece öğretmenler erişebilir
if ($_SESSION['user_type'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $expertise = trim($_POST['expertise']);
        $education = trim($_POST['education']);
        $experience = trim($_POST['experience']);
        $about = trim($_POST['about']);
        
        // Profil resmi yükleme
        $profile_image = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $upload_dir = 'uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $profile_image = $upload_dir . uniqid('profile_') . '.' . $file_extension;
                move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
            } else {
                $error_message = 'Profil resmi sadece JPG, JPEG, PNG veya GIF formatında olabilir.';
            }
        }
        
        // Banner resmi yükleme
        $banner_image = null;
        if (!empty($_FILES['banner_image']['name'])) {
            $upload_dir = 'uploads/banners/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $banner_image = $upload_dir . uniqid('banner_') . '.' . $file_extension;
                move_uploaded_file($_FILES['banner_image']['tmp_name'], $banner_image);
            } else {
                $error_message = 'Banner resmi sadece JPG, JPEG, PNG veya GIF formatında olabilir.';
            }
        }
        
        if (empty($error_message)) {
            try {
                // Mevcut resimleri al
                $stmt = $pdo->prepare("SELECT profile_image, banner_image FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_images = $stmt->fetch();
                
                // SQL sorgusunu hazırla
                $sql = "UPDATE users SET 
                        name = ?, 
                        email = ?, 
                        phone = ?, 
                        expertise = ?, 
                        education = ?, 
                        experience = ?, 
                        about = ?";
                
                $params = [$name, $email, $phone, $expertise, $education, $experience, $about];
                
                // Yeni profil resmi varsa ekle
                if ($profile_image) {
                    $sql .= ", profile_image = ?";
                    $params[] = $profile_image;
                    // Eski profil resmini sil
                    if ($current_images['profile_image'] && file_exists($current_images['profile_image'])) {
                        unlink($current_images['profile_image']);
                    }
                }
                
                // Yeni banner resmi varsa ekle
                if ($banner_image) {
                    $sql .= ", banner_image = ?";
                    $params[] = $banner_image;
                    // Eski banner resmini sil
                    if ($current_images['banner_image'] && file_exists($current_images['banner_image'])) {
                        unlink($current_images['banner_image']);
                    }
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $user_id;
                
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute($params)) {
                    $_SESSION['name'] = $name;
                    $success_message = 'Profil başarıyla güncellendi.';
                }
            } catch (PDOException $e) {
                $error_message = 'Profil güncellenirken bir hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

// Öğretmen bilgilerini getir
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE id = ? AND user_type = 'teacher'
");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Profili</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .banner-container {
            height: 200px;
            background-color: #f8f9fa;
            overflow: hidden;
            position: relative;
        }
        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-image-container {
            position: relative;
            margin-top: -75px;
            margin-left: 30px;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            background-color: #fff;
        }
        .image-upload-label {
            cursor: pointer;
            position: absolute;
            bottom: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .image-upload-label:hover {
            background: rgba(0,0,0,0.7);
        }
        .banner-upload-label {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .banner-upload-label:hover {
            background: rgba(0,0,0,0.7);
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4 mb-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Banner ve Profil Resmi -->
            <div class="card shadow-sm mb-4">
                <div class="banner-container">
                    <?php if ($teacher['banner_image']): ?>
                        <img src="<?php echo htmlspecialchars($teacher['banner_image']); ?>" alt="Banner" class="banner-image">
                    <?php else: ?>
                        <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted">
                            <i class="bi bi-image me-2"></i> Banner Resmi Yok
                        </div>
                    <?php endif; ?>
                    <label class="banner-upload-label">
                        <i class="bi bi-camera-fill me-2"></i>
                        Banner Güncelle
                        <input type="file" name="banner_image" class="d-none" accept="image/*">
                    </label>
                </div>
                
                <div class="profile-image-container">
                    <div class="position-relative d-inline-block">
                        <?php if ($teacher['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($teacher['profile_image']); ?>" alt="Profil" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image d-flex align-items-center justify-content-center bg-secondary text-white">
                                <i class="bi bi-person-fill" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        <label class="image-upload-label">
                            <i class="bi bi-camera-fill"></i>
                            <input type="file" name="profile_image" class="d-none" accept="image/*">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Profil Bilgileri -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Profil Bilgileri</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Uzmanlık Alanı</label>
                            <input type="text" name="expertise" class="form-control" value="<?php echo htmlspecialchars($teacher['expertise'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Eğitim</label>
                            <textarea name="education" class="form-control" rows="3"><?php echo htmlspecialchars($teacher['education'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Deneyim</label>
                            <textarea name="experience" class="form-control" rows="3"><?php echo htmlspecialchars($teacher['experience'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Hakkımda</label>
                            <textarea name="about" class="form-control" rows="4"><?php echo htmlspecialchars($teacher['about'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Resim yükleme önizleme
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                const isProfile = this.name === 'profile_image';
                
                reader.onload = function(e) {
                    if (isProfile) {
                        const container = document.querySelector('.profile-image-container .position-relative');
                        const oldImage = container.querySelector('.profile-image');
                        const newImage = document.createElement('img');
                        newImage.src = e.target.result;
                        newImage.className = 'profile-image';
                        newImage.alt = 'Profil';
                        container.replaceChild(newImage, oldImage);
                    } else {
                        const container = document.querySelector('.banner-container');
                        const oldImage = container.querySelector('.banner-image') || container.querySelector('.w-100.h-100');
                        const newImage = document.createElement('img');
                        newImage.src = e.target.result;
                        newImage.className = 'banner-image';
                        newImage.alt = 'Banner';
                        container.replaceChild(newImage, oldImage);
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    </script>
</body>
</html>
