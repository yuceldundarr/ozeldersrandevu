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

// İstatistikleri al
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_appointments' => 0,
    'pending_appointments' => 0
];

// Öğrenci sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student'");
$stats['total_students'] = $stmt->fetchColumn();

// Öğretmen sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'teacher'");
$stats['total_teachers'] = $stmt->fetchColumn();

// Toplam randevu sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$stats['total_appointments'] = $stmt->fetchColumn();

// Bekleyen randevu sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $stmt->fetchColumn();

// Durum bilgileri
$status_info = [
    'pending' => ['text' => 'Bekliyor', 'class' => 'warning', 'icon' => 'bi-hourglass'],
    'confirmed' => ['text' => 'Onaylandı', 'class' => 'success', 'icon' => 'bi-check-circle'],
    'cancelled' => ['text' => 'İptal Edildi', 'class' => 'danger', 'icon' => 'bi-x-circle'],
    'completed' => ['text' => 'Tamamlandı', 'class' => 'info', 'icon' => 'bi-trophy']
];

// Varsayılan durum bilgisi
$default_status_info = ['text' => 'Bekliyor', 'class' => 'warning', 'icon' => 'bi-hourglass'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
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
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                    <h2>Dashboard</h2>
                    <a href="/lesson-system/index.php" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Siteyi Görüntüle
                    </a>
                </div>
                
                <div class="row">
                    <!-- Öğrenci Sayısı -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Öğrenciler</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_students']; ?></h2>
                                    </div>
                                    <i class="bi bi-mortarboard fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Öğretmen Sayısı -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Öğretmenler</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_teachers']; ?></h2>
                                    </div>
                                    <i class="bi bi-person-workspace fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toplam Randevu -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Toplam Randevu</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_appointments']; ?></h2>
                                    </div>
                                    <i class="bi bi-calendar2-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bekleyen Randevu -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Bekleyen Randevu</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['pending_appointments']; ?></h2>
                                    </div>
                                    <i class="bi bi-hourglass-split fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Randevular -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Son Randevular</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Öğrenci</th>
                                                <th>Öğretmen</th>
                                                <th>Konu</th>
                                                <th>Tarih</th>
                                                <th>Saat</th>
                                                <th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $pdo->query("
                                                SELECT 
                                                    a.*,
                                                    COALESCE(s.name, 'Silinmiş Kullanıcı') as student_name,
                                                    COALESCE(t.name, 'Silinmiş Kullanıcı') as teacher_name
                                                FROM appointments a
                                                LEFT JOIN users s ON a.student_id = s.id
                                                LEFT JOIN users t ON a.teacher_id = t.id
                                                ORDER BY a.created_at DESC
                                                LIMIT 5
                                            ");
                                            while($row = $stmt->fetch()): 
                                                $status = $row['status'] ?? 'pending';
                                                $info = $status_info[$status] ?? $default_status_info;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($row['time'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $info['class']; ?>">
                                                        <i class="bi <?php echo $info['icon']; ?> me-1"></i>
                                                        <?php echo $info['text']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <?php if($stmt->rowCount() == 0): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Henüz randevu bulunmuyor.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
