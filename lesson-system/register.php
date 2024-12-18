<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    $user_type = sanitize($_POST['user_type']);
    $phone = sanitize($_POST['phone']);

    // Validasyon
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type) || empty($phone)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif ($password !== $confirm_password) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir email adresi girin.";
    } elseif (!preg_match("/^[0-9]{10,11}$/", $phone)) {
        $error = "Geçerli bir telefon numarası girin (10-11 rakam).";
    } else {
        // Email kontrolü
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Bu email adresi zaten kullanılıyor.";
        } else {
            // Kullanıcıyı kaydet
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $phone, $user_type])) {
                $success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            } else {
                $error = "Kayıt sırasında bir hata oluştu.";
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
    <title>Kayıt Ol - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/lesson-system/assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="card-body p-4">
                <div class="auth-header">
                    <h2>Kayıt Ol</h2>
                    <p class="text-muted">Yeni bir hesap oluşturun</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad Soyad</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" 
                                   class="form-control <?php echo $error && empty($_POST['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Adresi</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" 
                                   class="form-control <?php echo $error && (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) ? 'is-invalid' : ''; ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefon Numarası</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input type="tel" 
                                   class="form-control <?php echo $error && (!isset($_POST['phone']) || !preg_match("/^[0-9]{10,11}$/", $_POST['phone'])) ? 'is-invalid' : ''; ?>" 
                                   id="phone" 
                                   name="phone" 
                                   pattern="[0-9]{10,11}"
                                   placeholder="5XX XXX XX XX"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="user_type" class="form-label">Hesap Türü</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <select class="form-select <?php echo $error && empty($_POST['user_type']) ? 'is-invalid' : ''; ?>" 
                                    id="user_type" 
                                    name="user_type" 
                                    required>
                                <option value="">Seçiniz</option>
                                <option value="student" <?php echo isset($_POST['user_type']) && $_POST['user_type'] == 'student' ? 'selected' : ''; ?>>Öğrenci</option>
                                <option value="teacher" <?php echo isset($_POST['user_type']) && $_POST['user_type'] == 'teacher' ? 'selected' : ''; ?>>Öğretmen</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control <?php echo $error && empty($_POST['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" 
                                   class="form-control <?php echo $error && (empty($_POST['confirm_password']) || $_POST['password'] !== $_POST['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>
                            Kayıt Ol
                        </button>
                    </div>
                </form>

                <div class="auth-links">
                    <p class="mb-0">
                        Zaten hesabınız var mı? 
                        <a href="login.php">Giriş Yap</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
