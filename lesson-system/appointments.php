<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

// Giriş kontrolü
checkLogin();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Öğretmen için randevuları duruma göre grupla
if ($user_type == 'teacher') {
    // Bekleyen randevular
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as student_name, u.email as student_email, u.phone as student_phone 
        FROM appointments a 
        JOIN users u ON a.student_id = u.id 
        WHERE a.teacher_id = ? AND a.status = 'pending'
        ORDER BY a.date ASC, a.time ASC
    ");
    $stmt->execute([$user_id]);
    $pending_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Onaylanan randevular
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as student_name, u.email as student_email, u.phone as student_phone 
        FROM appointments a 
        JOIN users u ON a.student_id = u.id 
        WHERE a.teacher_id = ? AND a.status = 'confirmed'
        ORDER BY a.date ASC, a.time ASC
    ");
    $stmt->execute([$user_id]);
    $confirmed_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tamamlanan randevular
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as student_name, u.email as student_email, u.phone as student_phone 
        FROM appointments a 
        JOIN users u ON a.student_id = u.id 
        WHERE a.teacher_id = ? AND a.status = 'completed'
        ORDER BY a.date DESC, a.time DESC
    ");
    $stmt->execute([$user_id]);
    $completed_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Öğrenci randevuları
    $appointments = getAppointments($pdo, $user_id, $user_type);
}

// Randevu durumunu güncelle
if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['confirmed', 'cancelled', 'completed'])) {
        if (updateAppointmentStatus($pdo, $appointment_id, $action)) {
            header("Location: appointments.php?success=1");
            exit();
        }
    }
}

// Durum açıklamaları
$status_labels = [
    'pending' => '<span class="badge bg-warning">Beklemede</span>',
    'confirmed' => '<span class="badge bg-success">Onaylandı</span>',
    'cancelled' => '<span class="badge bg-danger">İptal Edildi</span>',
    'completed' => '<span class="badge bg-info">Tamamlandı</span>'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevularım - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .appointment-card {
            transition: transform 0.2s;
        }
        .appointment-card:hover {
            transform: translateY(-5px);
        }
        .nav-pills .nav-link {
            color: #333;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Özel Ders Randevu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">Randevularım</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profilim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Randevu durumu başarıyla güncellendi.</div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="card-title mb-0">Randevularım</h2>
                    <div class="d-flex align-items-center">
                        <?php if ($user_type == 'student'): ?>
                            <!-- Bildirim Bileşeni -->
                            <?php include 'includes/notification-component.php'; ?>
                            <a href="create-appointment.php" class="btn btn-primary ms-3">Yeni Randevu</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user_type == 'teacher'): ?>
                    <!-- Öğretmen Görünümü -->
                    <ul class="nav nav-pills mb-4" id="appointmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pending" type="button">
                                Bekleyen (<?php echo count($pending_appointments); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#confirmed" type="button">
                                Onaylanan (<?php echo count($confirmed_appointments); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#completed" type="button">
                                Tamamlanan (<?php echo count($completed_appointments); ?>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Bekleyen Randevular -->
                        <div class="tab-pane fade show active" id="pending">
                            <?php if (empty($pending_appointments)): ?>
                                <div class="alert alert-info">Bekleyen randevunuz bulunmamaktadır.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($pending_appointments as $appointment): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card appointment-card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($appointment['student_name']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Ders:</strong> <?php echo htmlspecialchars($appointment['subject']); ?><br>
                                                        <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($appointment['date'])); ?><br>
                                                        <strong>Saat:</strong> <?php echo date('H:i', strtotime($appointment['time'])); ?><br>
                                                        <strong>Süre:</strong> <?php echo $appointment['duration']; ?> dakika<br>
                                                        <strong>Email:</strong> <?php echo htmlspecialchars($appointment['student_email']); ?><br>
                                                        <strong>Telefon:</strong> <?php echo htmlspecialchars($appointment['student_phone']); ?>
                                                    </p>
                                                    <div class="d-flex gap-2">
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="action" value="confirmed">
                                                            <button type="submit" class="btn btn-success">Onayla</button>
                                                        </form>
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="action" value="cancelled">
                                                            <button type="submit" class="btn btn-danger">İptal Et</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Onaylanan Randevular -->
                        <div class="tab-pane fade" id="confirmed">
                            <?php if (empty($confirmed_appointments)): ?>
                                <div class="alert alert-info">Onaylanan randevunuz bulunmamaktadır.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($confirmed_appointments as $appointment): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card appointment-card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($appointment['student_name']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Ders:</strong> <?php echo htmlspecialchars($appointment['subject']); ?><br>
                                                        <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($appointment['date'])); ?><br>
                                                        <strong>Saat:</strong> <?php echo date('H:i', strtotime($appointment['time'])); ?><br>
                                                        <strong>Süre:</strong> <?php echo $appointment['duration']; ?> dakika<br>
                                                        <strong>Email:</strong> <?php echo htmlspecialchars($appointment['student_email']); ?><br>
                                                        <strong>Telefon:</strong> <?php echo htmlspecialchars($appointment['student_phone']); ?>
                                                    </p>
                                                    <div class="d-flex gap-2">
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="action" value="completed">
                                                            <button type="submit" class="btn btn-success">Tamamlandı</button>
                                                        </form>
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="action" value="cancelled">
                                                            <button type="submit" class="btn btn-danger">İptal Et</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tamamlanan Randevular -->
                        <div class="tab-pane fade" id="completed">
                            <?php if (empty($completed_appointments)): ?>
                                <div class="alert alert-info">Tamamlanan randevunuz bulunmamaktadır.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($completed_appointments as $appointment): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card appointment-card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($appointment['student_name']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Ders:</strong> <?php echo htmlspecialchars($appointment['subject']); ?><br>
                                                        <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($appointment['date'])); ?><br>
                                                        <strong>Saat:</strong> <?php echo date('H:i', strtotime($appointment['time'])); ?><br>
                                                        <strong>Süre:</strong> <?php echo $appointment['duration']; ?> dakika<br>
                                                        <strong>Email:</strong> <?php echo htmlspecialchars($appointment['student_email']); ?><br>
                                                        <strong>Telefon:</strong> <?php echo htmlspecialchars($appointment['student_phone']); ?>
                                                    </p>
                                                    <span class="badge bg-success">Tamamlandı</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Öğrenci Görünümü -->
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info">Henüz randevunuz bulunmamaktadır.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th>Öğretmen</th>
                                        <th>Konu</th>
                                        <th>Süre</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($appointment['date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($appointment['time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['subject']); ?></td>
                                            <td><?php echo $appointment['duration']; ?> dakika</td>
                                            <td><?php echo $status_labels[$appointment['status']]; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($user_type == 'student'): ?>
    <style>
    /* Bildirim dropdown'ı için özel stiller */
    .dropdown-menu {
        min-width: 320px;
        max-width: 320px;
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    .notification-item {
        transition: background-color 0.2s;
    }
    </style>
    <?php endif; ?>
</body>
</html>
