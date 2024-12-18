<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    
    // Ödeme işlemi simülasyonu
    $sql = "UPDATE appointments SET payment_status = 'paid' WHERE id = ? AND student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$appointment_id, $_SESSION['user_id']]);
    
    // Bildirim oluştur
    $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], 'Ödemeniz başarıyla alındı.', 'payment']);
    
    header("Location: appointments.php");
    exit();
}

$appointment_id = $_GET['id'] ?? null;
if (!$appointment_id) {
    header("Location: appointments.php");
    exit();
}

// Randevu bilgilerini al
$sql = "SELECT a.*, u.name as teacher_name, s.name as subject_name, ts.hourly_rate 
        FROM appointments a 
        JOIN users u ON a.teacher_id = u.id 
        JOIN teacher_subjects ts ON a.teacher_id = ts.teacher_id 
        JOIN subjects s ON ts.subject_id = s.id 
        WHERE a.id = ? AND a.student_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ödeme Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Randevu Detayları</h6>
                            <p>
                                <strong>Öğretmen:</strong> <?php echo htmlspecialchars($appointment['teacher_name']); ?><br>
                                <strong>Ders:</strong> <?php echo htmlspecialchars($appointment['subject_name']); ?><br>
                                <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($appointment['date'])); ?><br>
                                <strong>Saat:</strong> <?php echo date('H:i', strtotime($appointment['time'])); ?><br>
                                <strong>Süre:</strong> <?php echo $appointment['duration']; ?> dakika<br>
                                <strong>Ücret:</strong> <?php echo number_format($appointment['hourly_rate'] * ($appointment['duration'] / 60), 2); ?> TL
                            </p>
                        </div>

                        <form method="POST" id="payment-form">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Kart Numarası</label>
                                <input type="text" class="form-control" required maxlength="16" pattern="\d{16}">
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Son Kullanma Tarihi</label>
                                    <input type="text" class="form-control" placeholder="AA/YY" required pattern="\d{2}/\d{2}">
                                </div>
                                <div class="col">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" required maxlength="3" pattern="\d{3}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kart Sahibi</label>
                                <input type="text" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo number_format($appointment['hourly_rate'] * ($appointment['duration'] / 60), 2); ?> TL Öde
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kart numarası formatı
        document.querySelector('input[pattern="\\d{16}"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });

        // Son kullanma tarihi formatı
        document.querySelector('input[placeholder="AA/YY"]').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substr(0,2) + '/' + value.substr(2);
            }
            this.value = value;
        });

        // CVV formatı
        document.querySelector('input[pattern="\\d{3}"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
