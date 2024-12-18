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

// Kullanıcı silme işlemi
if(isset($_POST['delete_user'])) {
    $user_id = sanitize($_POST['user_id']);
    
    // Kullanıcının randevularını sil
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE student_id = ? OR teacher_id = ?");
    $stmt->execute([$user_id, $user_id]);
    
    // Kullanıcıyı sil
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if($stmt->execute([$user_id])) {
        $success = "Kullanıcı başarıyla silindi.";
    } else {
        $error = "Kullanıcı silinirken bir hata oluştu.";
    }
}

// Kullanıcıları getir
$stmt = $pdo->query("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM appointments WHERE student_id = u.id OR teacher_id = u.id) as total_appointments
    FROM users u 
    WHERE user_type != 'admin'
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
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
                    <h2>Kullanıcı Yönetimi</h2>
                    <a href="add-user.php" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Yeni Kullanıcı
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
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>İsim</th>
                                        <th>Telefon</th>
                                        <th>Tip</th>
                                        <th>Randevu Sayısı</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if($user['profile_image']): ?>
                                                    <img src="<?php echo '../uploads/users/' . htmlspecialchars($user['profile_image']); ?>" 
                                                         class="rounded-circle me-2" width="40" height="40" 
                                                         style="object-fit: cover;" alt="Profil Fotoğrafı">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="bi bi-person-fill text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($user['email']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['user_type'] === 'teacher' ? 'primary' : 'success'; ?>">
                                                <?php echo $user['user_type'] === 'teacher' ? 'Öğretmen' : 'Öğrenci'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['total_appointments']; ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
