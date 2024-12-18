<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

try {
    // Mevcut bilgileri getir
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            td.hourly_rate,
            td.expertise,
            td.education,
            td.experience,
            td.about_me
        FROM users u 
        LEFT JOIN teacher_details td ON u.id = td.teacher_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Form gönderildiğinde
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $hourly_rate = floatval($_POST['hourly_rate']);
        $expertise = sanitize($_POST['expertise']);
        $education = sanitize($_POST['education']);
        $experience = sanitize($_POST['experience']);
        $about_me = sanitize($_POST['about_me']);

        // Profil fotoğrafı yükleme
        $profile_image = $user['profile_image']; // Varsayılan olarak mevcut resmi kullan
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = 'uploads/users/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Eski resmi sil
                    if (!empty($user['profile_image']) && file_exists('uploads/users/' . $user['profile_image'])) {
                        unlink('uploads/users/' . $user['profile_image']);
                    }
                    $profile_image = $new_filename;
                }
            }
        }

        // Kullanıcı bilgilerini güncelle
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, phone = ?, profile_image = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $phone, $profile_image, $_SESSION['user_id']]);

        // Öğretmen detaylarını güncelle
        $stmt = $pdo->prepare("
            UPDATE teacher_details 
            SET hourly_rate = ?, expertise = ?, education = ?, experience = ?, about_me = ?
            WHERE teacher_id = ?
        ");
        $stmt->execute([$hourly_rate, $expertise, $education, $experience, $about_me, $_SESSION['user_id']]);

        $_SESSION['user_name'] = $name; // Session'daki ismi güncelle
        $success = "Profiliniz başarıyla güncellendi.";
        
        // Güncel bilgileri yeniden yükle
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                td.hourly_rate,
                td.expertise,
                td.education,
                td.experience,
                td.about_me
            FROM users u 
            LEFT JOIN teacher_details td ON u.id = td.teacher_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = "Bir hata oluştu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Düzenle - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Profil Düzenle</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Profil Fotoğrafı</label>
                                <input type="file" class="form-control" name="profile_image" accept="image/*">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <div class="mt-2">
                                        <img src="uploads/users/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                             alt="Mevcut profil fotoğrafı" 
                                             class="rounded-circle" 
                                             style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Saatlik Ücret (TL)</label>
                                <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate" 
                                       value="<?php echo htmlspecialchars($user['hourly_rate']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="about_me" class="form-label">Hakkımda</label>
                                <textarea class="form-control" id="about_me" name="about_me" rows="4"><?php echo htmlspecialchars($user['about_me']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="expertise" class="form-label">Uzmanlık Alanları</label>
                                <textarea class="form-control" id="expertise" name="expertise" rows="4"><?php echo htmlspecialchars($user['expertise']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="education" class="form-label">Eğitim Bilgileri</label>
                                <textarea class="form-control" id="education" name="education" rows="4"><?php echo htmlspecialchars($user['education']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="experience" class="form-label">Deneyim</label>
                                <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo htmlspecialchars($user['experience']); ?></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
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
