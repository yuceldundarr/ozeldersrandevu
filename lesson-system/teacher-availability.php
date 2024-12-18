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

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Önce mevcut müsaitlik durumlarını sil
        $stmt = $pdo->prepare("DELETE FROM teacher_availability WHERE teacher_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Yeni müsaitlik durumlarını ekle
        if (isset($_POST['availability']) && is_array($_POST['availability'])) {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_POST['availability'] as $day => $times) {
                if (!empty($times['start']) && !empty($times['end'])) {
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $day,
                        $times['start'],
                        $times['end']
                    ]);
                }
            }
        }
        
        $success = "Müsaitlik durumunuz başarıyla güncellendi.";
    } catch (PDOException $e) {
        $error = "Bir hata oluştu: " . $e->getMessage();
    }
}

// Mevcut müsaitlik durumunu getir
try {
    $stmt = $pdo->prepare("
        SELECT * FROM teacher_availability 
        WHERE teacher_id = ? 
        ORDER BY day_of_week, start_time
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $availabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Müsaitlik durumlarını gün bazında düzenle
    $availability_by_day = [];
    foreach ($availabilities as $availability) {
        $availability_by_day[$availability['day_of_week']] = [
            'start' => $availability['start_time'],
            'end' => $availability['end_time']
        ];
    }
} catch (PDOException $e) {
    $error = "Bir hata oluştu: " . $e->getMessage();
}

// Günleri tanımla
$days = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müsaitlik Durumu - Özel Ders Sistemi</title>
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
                        <h2 class="card-title mb-4">Müsaitlik Durumu</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php foreach ($days as $day_num => $day_name): ?>
                                <div class="mb-4">
                                    <label class="form-label fw-bold"><?php echo $day_name; ?></label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Başlangıç Saati</label>
                                            <input type="time" 
                                                   class="form-control" 
                                                   name="availability[<?php echo $day_num; ?>][start]"
                                                   value="<?php echo isset($availability_by_day[$day_num]) ? $availability_by_day[$day_num]['start'] : ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Bitiş Saati</label>
                                            <input type="time" 
                                                   class="form-control" 
                                                   name="availability[<?php echo $day_num; ?>][end]"
                                                   value="<?php echo isset($availability_by_day[$day_num]) ? $availability_by_day[$day_num]['end'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Kaydet
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
