<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
checkLogin();

// Öğretmen kontrolü
if ($_SESSION['user_type'] === 'teacher') {
    header('Location: appointments.php');
    exit('Öğretmenler randevu oluşturamaz.');
}

$error = '';
$success = '';

// Öğretmenleri getir
$stmt = $pdo->query("SELECT id, name, user_type FROM users WHERE user_type = 'teacher'");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_id = sanitize($_POST['teacher_id']);
    $subject = sanitize($_POST['subject']);
    $date = sanitize($_POST['date']);
    $time = sanitize($_POST['time']);
    $duration = sanitize($_POST['duration']);
    $student_id = $_SESSION['user_id'];

    // Validasyon
    if (empty($teacher_id) || empty($subject) || empty($date) || empty($time) || empty($duration)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        // Randevu çakışması kontrolü
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
                              WHERE teacher_id = ? AND date = ? AND time = ? 
                              AND status != 'cancelled'");
        $stmt->execute([$teacher_id, $date, $time]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Bu zaman dilimi için seçilen öğretmen müsait değil.";
        } else {
            // Randevu oluştur
            if (createAppointment($pdo, $teacher_id, $student_id, $subject, $date, $time, $duration)) {
                $success = "Randevu başarıyla oluşturuldu! 3 saniye sonra yönlendirileceksiniz...";
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "appointments.php";
                    }, 3000);
                </script>';
            } else {
                $error = "Randevu oluşturulurken bir hata oluştu.";
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
    <title>Yeni Randevu Oluştur - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <a class="nav-link" href="appointments.php">Randevularım</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Yeni Randevu Oluştur</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="teacher_id" class="form-label">Öğretmen</label>
                                <select class="form-select" id="teacher_id" name="teacher_id" required>
                                    <option value="">Öğretmen Seçin</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>">
                                            <?php echo htmlspecialchars($teacher['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Ders</label>
                                <select class="form-select" id="subject" name="subject" required disabled>
                                    <option value="">Önce öğretmen seçin</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Tarih</label>
                                <input type="text" class="form-control" id="date" name="date" required readonly>
                            </div>

                            <div class="mb-3">
                                <label for="time" class="form-label">Saat</label>
                                <select class="form-select" id="time" name="time" required disabled>
                                    <option value="">Önce tarih seçin</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="duration" class="form-label">Süre (Dakika)</label>
                                <select class="form-select" id="duration" name="duration" required>
                                    <option value="30">30 dakika</option>
                                    <option value="45">45 dakika</option>
                                    <option value="60" selected>60 dakika</option>
                                    <option value="90">90 dakika</option>
                                    <option value="120">120 dakika</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Randevu Oluştur</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <script>
        // Öğretmen seçildiğinde dersleri getir
        document.getElementById('teacher_id').addEventListener('change', function() {
            const teacherId = this.value;
            const subjectSelect = document.getElementById('subject');
            const dateInput = document.getElementById('date');
            const timeSelect = document.getElementById('time');
            
            if (teacherId) {
                // Dersleri getir
                fetch(`get_teacher_subjects.php?teacher_id=${teacherId}`)
                    .then(response => response.json())
                    .then(data => {
                        subjectSelect.innerHTML = '<option value="">Ders Seçin</option>';
                        data.forEach(subject => {
                            subjectSelect.innerHTML += `<option value="${subject.subject_name}">${subject.subject_name} (${subject.level})</option>`;
                        });
                        subjectSelect.disabled = false;
                    });

                // Flatpickr'ı güncelle
                initializeFlatpickr(teacherId);
            } else {
                subjectSelect.innerHTML = '<option value="">Önce öğretmen seçin</option>';
                subjectSelect.disabled = true;
                dateInput.value = '';
                timeSelect.innerHTML = '<option value="">Önce tarih seçin</option>';
                timeSelect.disabled = true;
            }
        });

        // Tarih seçildiğinde müsait saatleri getir
        function getAvailableHours(teacherId, date) {
            const timeSelect = document.getElementById('time');
            
            fetch(`get_available_hours.php?teacher_id=${teacherId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '<option value="">Saat Seçin</option>';
                    data.forEach(time => {
                        timeSelect.innerHTML += `<option value="${time}">${time}</option>`;
                    });
                    timeSelect.disabled = false;
                });
        }

        // Flatpickr ayarları
        function initializeFlatpickr(teacherId) {
            const dateInput = document.getElementById('date');
            
            // Öğretmenin müsait günlerini getir
            fetch(`get_available_days.php?teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    flatpickr(dateInput, {
                        minDate: "today",
                        dateFormat: "Y-m-d",
                        locale: "tr",
                        enable: data.available_days,
                        onChange: function(selectedDates, dateStr) {
                            if (selectedDates.length > 0) {
                                getAvailableHours(teacherId, dateStr);
                            }
                        }
                    });
                });
        }
    </script>
</body>
</html>
