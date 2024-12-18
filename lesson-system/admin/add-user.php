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

// Form gönderildiğinde
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $user_type = sanitize($_POST['user_type']);
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Zorunlu alanları kontrol et
    if(empty($name) || empty($email) || empty($password) || empty($user_type)) {
        $error = "Lütfen tüm zorunlu alanları doldurun.";
    } else {
        try {
            // Email kontrolü
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if($stmt->rowCount() > 0) {
                throw new Exception("Bu email adresi zaten kullanılıyor.");
            }
            
            // Şifreyi hashle
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Kullanıcıyı ekle
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, user_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if($stmt->execute([$name, $email, $phone, $hashed_password, $user_type, $status])) {
                $user_id = $pdo->lastInsertId();
                
                // Eğer öğretmen ise öğretmen detaylarını da ekle
                if($user_type === 'teacher') {
                    $stmt = $pdo->prepare("INSERT INTO teacher_details (teacher_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                }
                
                $success = "Kullanıcı başarıyla eklendi!";
                
                // Formu temizle
                $_POST = array();
            } else {
                throw new Exception("Kullanıcı eklenirken bir hata oluştu.");
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kullanıcı Ekle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .form-card {
            max-width: 800px;
            margin: 0 auto;
        }
        .password-toggle {
            cursor: pointer;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Yeni Kullanıcı Ekle</h2>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Geri Dön
                    </a>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="card form-card">
                    <div class="card-body">
                        <form method="POST" action="" id="addUserForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label required-field">Ad Soyad</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label required-field">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label required-field">Şifre</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="user_type" class="form-label required-field">Kullanıcı Tipi</label>
                                    <select class="form-select" id="user_type" name="user_type" required 
                                            onchange="toggleTeacherFields()">
                                        <option value="">Seçiniz...</option>
                                        <option value="student">Öğrenci</option>
                                        <option value="teacher">Öğretmen</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Durum</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                        <label class="form-check-label" for="status">Aktif</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Öğretmen alanları -->
                            <div id="teacherFields" style="display: none;">
                                <hr>
                                <h5 class="mb-3">Öğretmen Bilgileri</h5>
                                
                                <div class="mb-3">
                                    <label for="expertise" class="form-label">Uzmanlık Alanları</label>
                                    <textarea class="form-control" id="expertise" name="expertise" rows="2"
                                            placeholder="Örnek: Matematik, Fizik, Kimya"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="education" class="form-label">Eğitim Bilgileri</label>
                                    <textarea class="form-control" id="education" name="education" rows="2"
                                            placeholder="Örnek: Boğaziçi Üniversitesi - Matematik Bölümü (2015-2019)"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="experience" class="form-label">Deneyim</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="2"
                                            placeholder="Örnek: 5 yıl özel ders deneyimi"></textarea>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Kullanıcı Ekle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Şifre göster/gizle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Öğretmen alanlarını göster/gizle
        function toggleTeacherFields() {
            const userType = document.getElementById('user_type').value;
            const teacherFields = document.getElementById('teacherFields');
            
            if (userType === 'teacher') {
                teacherFields.style.display = 'block';
            } else {
                teacherFields.style.display = 'none';
            }
        }

        // Form doğrulama
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const userType = document.getElementById('user_type').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Şifre en az 6 karakter olmalıdır!');
                return;
            }
            
            if (!userType) {
                e.preventDefault();
                alert('Lütfen bir kullanıcı tipi seçin!');
                return;
            }
        });
    </script>
</body>
</html>
