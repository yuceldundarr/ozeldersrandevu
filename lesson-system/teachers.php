<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Filtreleme parametrelerini al
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $subject = isset($_GET['subject']) ? $_GET['subject'] : '';
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000;

    // Ana SQL sorgusu
    $sql = "SELECT DISTINCT 
                u.id, 
                u.name, 
                u.email,
                u.phone,
                u.profile_image,
                td.hourly_rate
            FROM users u 
            LEFT JOIN teacher_details td ON u.id = td.teacher_id 
            WHERE u.user_type = 'teacher'";

    // Filtreleme koşulları
    $params = array();
    
    if (!empty($search)) {
        $sql .= " AND u.name LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($subject)) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM teacher_subjects ts2 
            WHERE ts2.teacher_id = u.id 
            AND ts2.subject_name LIKE ?
        )";
        $params[] = "%$subject%";
    }
    
    if ($min_price > 0) {
        $sql .= " AND td.hourly_rate >= ?";
        $params[] = $min_price;
    }
    
    if ($max_price < 1000000) {
        $sql .= " AND td.hourly_rate <= ?";
        $params[] = $max_price;
    }

    // Sorguyu hazırla ve çalıştır
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll();

    // Her öğretmen için dersleri al
    foreach ($teachers as &$teacher) {
        $stmt = $pdo->prepare("
            SELECT subject_name, level 
            FROM teacher_subjects 
            WHERE teacher_id = ?
            ORDER BY subject_name
        ");
        $stmt->execute([$teacher['id']]);
        $teacher['subjects'] = $stmt->fetchAll();
    }

    // Benzersiz dersleri al
    $stmt = $pdo->query("
        SELECT DISTINCT subject_name 
        FROM teacher_subjects 
        ORDER BY subject_name
    ");
    $all_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmenlerimiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .card {
            transition: transform 0.2s;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: -60px auto 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .card-img-top {
            height: 100px;
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
        }
        .subject-badge {
            font-size: 0.85rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            background-color: #e9ecef;
            color: #495057;
        }
        .price-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255,255,255,0.9);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: #0d6efd;
        }
        .contact-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <h2 class="mb-3 mb-md-0">Öğretmenlerimiz</h2>
            
            <!-- Filtreleme Butonu -->
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-funnel-fill me-1"></i> Filtrele
            </button>
        </div>

        <!-- Filtreleme Formu -->
        <div class="collapse mb-4" id="filterCollapse">
            <div class="card card-body">
                <form method="GET" class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="search" class="form-label">İsim Ara</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="subject" class="form-label">Ders</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="min_price" class="form-label">Min. Ücret</label>
                        <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="max_price" class="form-label">Max. Ücret</label>
                        <input type="number" class="form-control" id="max_price" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                        <a href="teachers.php" class="btn btn-secondary">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($teachers as $teacher): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php if (!empty($teacher['profile_image'])): ?>
                                    <img src="uploads/users/<?php echo htmlspecialchars($teacher['profile_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($teacher['name']); ?>" 
                                         class="profile-img">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle profile-img d-flex align-items-center justify-content-center mx-auto">
                                        <i class="bi bi-person-fill" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <h5 class="card-title"><?php echo htmlspecialchars($teacher['name']); ?></h5>
                            </div>
                            
                            <div class="d-flex flex-column gap-2">
                                <p class="card-text mb-1">
                                    <i class="bi bi-envelope me-2"></i>
                                    <?php echo htmlspecialchars($teacher['email']); ?>
                                </p>
                                <?php if (!empty($teacher['phone'])): ?>
                                    <p class="card-text mb-1">
                                        <i class="bi bi-telephone me-2"></i>
                                        <?php echo htmlspecialchars($teacher['phone']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="card-text mb-1">
                                    <i class="bi bi-currency-dollar me-2"></i>
                                    <?php echo number_format($teacher['hourly_rate'], 2); ?> TL/Saat
                                </p>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-center">
                            <a href="teacher-details.php?id=<?php echo $teacher['id']; ?>" 
                               class="btn btn-primary">Detayları Gör</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
