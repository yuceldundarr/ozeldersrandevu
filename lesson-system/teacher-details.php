<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Öğretmen ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: teachers.php");
    exit();
}

$teacher_id = (int)$_GET['id'];

try {
    // Öğretmen bilgilerini getir
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
        WHERE u.id = ? AND u.user_type = 'teacher'
    ");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        header("Location: teachers.php");
        exit();
    }

    // Öğretmenin derslerini getir
    $stmt = $pdo->prepare("
        SELECT * FROM teacher_subjects 
        WHERE teacher_id = ? 
        ORDER BY subject_name
    ");
    $stmt->execute([$teacher_id]);
    $subjects = $stmt->fetchAll();

    // Öğretmenin müsaitlik durumunu getir
    $stmt = $pdo->prepare("
        SELECT * FROM teacher_availability 
        WHERE teacher_id = ? 
        ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
    ");
    $stmt->execute([$teacher_id]);
    $availability = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Günleri Türkçeye çevir
$days_tr = [
    'monday' => 'Pazartesi',
    'tuesday' => 'Salı',
    'wednesday' => 'Çarşamba',
    'thursday' => 'Perşembe',
    'friday' => 'Cuma',
    'saturday' => 'Cumartesi',
    'sunday' => 'Pazar'
];

// Seviye açıklamaları
$level_tr = [
    'beginner' => 'Başlangıç',
    'intermediate' => 'Orta',
    'advanced' => 'İleri',
    'all' => 'Tüm Seviyeler'
];

function getDayName($day) {
    global $days_tr;
    return $days_tr[$day];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($teacher['name']); ?> - Öğretmen Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            object-fit: cover;
            background-color: #fff;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Sol Taraf - Profil Bilgileri -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <?php if (!empty($teacher['profile_image'])): ?>
                            <img src="uploads/users/<?php echo htmlspecialchars($teacher['profile_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($teacher['name']); ?>" 
                                 class="rounded-circle profile-img mb-3">
                        <?php else: ?>
                            <div class="bg-secondary text-white rounded-circle profile-img d-flex align-items-center justify-content-center mx-auto mb-3">
                                <i class="bi bi-person-fill" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>

                        <h1 class="h3 mb-3"><?php echo htmlspecialchars($teacher['name']); ?></h1>
                        
                        <div class="d-flex flex-column gap-2 text-start mb-4">
                            <?php if (!empty($teacher['email'])): ?>
                                <div>
                                    <i class="bi bi-envelope-fill me-2"></i>
                                    <?php echo htmlspecialchars($teacher['email']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($teacher['phone'])): ?>
                                <div>
                                    <i class="bi bi-telephone-fill me-2"></i>
                                    <?php echo htmlspecialchars($teacher['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($teacher['hourly_rate'])): ?>
                                <div>
                                    <i class="bi bi-currency-dollar me-2"></i>
                                    <?php echo number_format($teacher['hourly_rate'], 2); ?> TL/Saat
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                            <a href="create-appointment.php?teacher_id=<?php echo $teacher['id']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="bi bi-calendar-plus me-2"></i>Randevu Al
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Öğretmen Hakkında -->
                <?php if (!empty($teacher['about_me']) || !empty($teacher['expertise']) || !empty($teacher['education']) || !empty($teacher['experience'])): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Öğretmen Hakkında</h2>
                        
                        <?php if (!empty($teacher['about_me'])): ?>
                            <div class="mb-4">
                                <h3 class="h5 mb-3">
                                    <i class="bi bi-person-lines-fill me-2"></i>Hakkında
                                </h3>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($teacher['about_me'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($teacher['expertise'])): ?>
                            <div class="mb-4">
                                <h3 class="h5 mb-3">
                                    <i class="bi bi-book-fill me-2"></i>Uzmanlık Alanları
                                </h3>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($teacher['expertise'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($teacher['education'])): ?>
                            <div class="mb-4">
                                <h3 class="h5 mb-3">
                                    <i class="bi bi-mortarboard-fill me-2"></i>Eğitim Bilgileri
                                </h3>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($teacher['education'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($teacher['experience'])): ?>
                            <div class="mb-4">
                                <h3 class="h5 mb-3">
                                    <i class="bi bi-briefcase-fill me-2"></i>Deneyim
                                </h3>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($teacher['experience'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sağ Taraf - Detaylar -->
            <div class="col-lg-8">
                <!-- Dersler -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Verdiği Dersler</h2>
                        <div class="row g-3">
                            <?php foreach ($subjects as $subject): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-book me-2"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                                                <?php if (!empty($subject['level']) && $subject['level'] !== 'all'): ?>
                                                    <div class="small text-muted">
                                                        <?php echo htmlspecialchars($subject['level']); ?> Seviye
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Müsaitlik -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Müsaitlik Durumu</h2>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Gün</th>
                                        <th>Saat Aralığı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($availability as $slot): ?>
                                        <tr>
                                            <td><?php echo getDayName($slot['day_of_week']); ?></td>
                                            <td><?php echo htmlspecialchars($slot['start_time']); ?> - <?php echo htmlspecialchars($slot['end_time']); ?></td>
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
