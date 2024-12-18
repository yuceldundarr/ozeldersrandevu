<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Kullanıcı bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Eğer öğretmense, öğretmen detaylarını getir
$teacher_details = null;
$subjects = [];
$availability = [];
if ($user['user_type'] === 'teacher') {
    $stmt = $pdo->prepare("SELECT * FROM teacher_details WHERE teacher_id = ?");
    $stmt->execute([$user_id]);
    $teacher_details = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM teacher_subjects WHERE teacher_id = ?");
    $stmt->execute([$user_id]);
    $subjects = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM teacher_availability WHERE teacher_id = ?");
    $stmt->execute([$user_id]);
    $availability = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $new_password = trim($_POST['new_password']);

            // Profil fotoğrafı yükleme
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_photo']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Sadece JPG, PNG ve GIF formatında resimler yükleyebilirsiniz.');
                }

                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['profile_photo']['size'] > $max_size) {
                    throw new Exception('Dosya boyutu çok büyük. Maximum 5MB yükleyebilirsiniz.');
                }

                $upload_dir = 'uploads/profile_photos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('profile_') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                    // Eski profil fotoğrafını sil
                    if ($user['profile_photo']) {
                        $old_photo = $upload_dir . $user['profile_photo'];
                        if (file_exists($old_photo)) {
                            unlink($old_photo);
                        }
                    }
                    
                    // Veritabanını güncelle
                    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $stmt->execute([$file_name, $user_id]);
                }
            }

            // Banner fotoğrafı yükleme (sadece öğretmenler için)
            if ($user['user_type'] === 'teacher' && isset($_FILES['banner_photo']) && $_FILES['banner_photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['banner_photo']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Sadece JPG, PNG ve GIF formatında resimler yükleyebilirsiniz.');
                }

                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['banner_photo']['size'] > $max_size) {
                    throw new Exception('Dosya boyutu çok büyük. Maximum 5MB yükleyebilirsiniz.');
                }

                $upload_dir = 'uploads/banner_photos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['banner_photo']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('banner_') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['banner_photo']['tmp_name'], $target_path)) {
                    // Eski banner fotoğrafını sil
                    if ($teacher_details && $teacher_details['banner_photo']) {
                        $old_photo = $upload_dir . $teacher_details['banner_photo'];
                        if (file_exists($old_photo)) {
                            unlink($old_photo);
                        }
                    }
                    
                    // Veritabanını güncelle
                    $stmt = $pdo->prepare("UPDATE teacher_details SET banner_photo = ? WHERE teacher_id = ?");
                    $stmt->execute([$file_name, $user_id]);
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);

            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }

            // Öğretmen detaylarını güncelle
            if ($user['user_type'] === 'teacher') {
                $bio = trim($_POST['bio']);
                $experience = trim($_POST['experience']);
                $education = trim($_POST['education']);
                $hourly_rate = floatval($_POST['hourly_rate']);

                if ($teacher_details) {
                    $stmt = $pdo->prepare("UPDATE teacher_details SET bio = ?, experience = ?, education = ?, hourly_rate = ? WHERE teacher_id = ?");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO teacher_details (bio, experience, education, hourly_rate, teacher_id) VALUES (?, ?, ?, ?, ?)");
                }
                $stmt->execute([$bio, $experience, $education, $hourly_rate, $user_id]);

                // Dersleri güncelle
                if (isset($_POST['subjects'])) {
                    // Önce mevcut dersleri sil
                    $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
                    $stmt->execute([$user_id]);

                    // Yeni dersleri ekle
                    foreach ($_POST['subjects'] as $subject) {
                        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_name, description, level) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $user_id,
                            $subject['name'],
                            $subject['description'],
                            $subject['level']
                        ]);
                    }
                }

                // Müsaitlik durumunu güncelle
                if (isset($_POST['availability'])) {
                    // Önce mevcut müsaitlik durumunu sil
                    $stmt = $pdo->prepare("DELETE FROM teacher_availability WHERE teacher_id = ?");
                    $stmt->execute([$user_id]);

                    // Yeni müsaitlik durumunu ekle
                    foreach ($_POST['availability'] as $slot) {
                        $stmt = $pdo->prepare("INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $user_id,
                            $slot['day'],
                            $slot['start_time'],
                            $slot['end_time']
                        ]);
                    }
                }
            }

            $success_message = "Profil başarıyla güncellendi!";
            
            // Sayfayı yenile
            header("Location: profile.php?success=1");
            exit();
        }
    } catch (Exception $e) {
        $error_message = "Hata oluştu: " . $e->getMessage();
    }
}

// Başarı mesajını URL'den al
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Profil başarıyla güncellendi!";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .subject-form {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .availability-slot {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Özel Ders Randevu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="teachers.php">Öğretmenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Randevularım</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profilim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Profil Bilgileri</h3>
                        <form method="POST" action="" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Profil Fotoğrafı</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo">
                            </div>

                            <?php if ($user['user_type'] === 'teacher'): ?>
                                <hr class="my-4">
                                <h4 class="mb-4">Öğretmen Bilgileri</h4>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Hakkımda</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($teacher_details['bio'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="experience" class="form-label">Deneyim</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="3"><?php echo htmlspecialchars($teacher_details['experience'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="education" class="form-label">Eğitim</label>
                                    <textarea class="form-control" id="education" name="education" rows="3"><?php echo htmlspecialchars($teacher_details['education'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="hourly_rate" class="form-label">Saatlik Ücret (₺)</label>
                                    <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo htmlspecialchars($teacher_details['hourly_rate'] ?? ''); ?>" step="0.01">
                                </div>

                                <div class="mb-3">
                                    <label for="banner_photo" class="form-label">Banner Fotoğrafı</label>
                                    <input type="file" class="form-control" id="banner_photo" name="banner_photo">
                                </div>

                                <hr class="my-4">
                                <h4 class="mb-4">Dersler</h4>
                                <div id="subjects-container">
                                    <?php foreach ($subjects as $index => $subject): ?>
                                        <div class="subject-form">
                                            <div class="mb-3">
                                                <label class="form-label">Ders Adı</label>
                                                <input type="text" class="form-control" name="subjects[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Açıklama</label>
                                                <textarea class="form-control" name="subjects[<?php echo $index; ?>][description]"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Seviye</label>
                                                <select class="form-control" name="subjects[<?php echo $index; ?>][level]">
                                                    <option value="beginner" <?php echo $subject['level'] === 'beginner' ? 'selected' : ''; ?>>Başlangıç</option>
                                                    <option value="intermediate" <?php echo $subject['level'] === 'intermediate' ? 'selected' : ''; ?>>Orta</option>
                                                    <option value="advanced" <?php echo $subject['level'] === 'advanced' ? 'selected' : ''; ?>>İleri</option>
                                                    <option value="all" <?php echo $subject['level'] === 'all' ? 'selected' : ''; ?>>Tüm Seviyeler</option>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm remove-subject">Dersi Sil</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary mb-4" id="add-subject">Yeni Ders Ekle</button>

                                <hr class="my-4">
                                <h4 class="mb-4">Müsaitlik Durumu</h4>
                                <div id="availability-container">
                                    <?php foreach ($availability as $index => $slot): ?>
                                        <div class="availability-slot">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">Gün</label>
                                                    <select class="form-control" name="availability[<?php echo $index; ?>][day]">
                                                        <option value="monday" <?php echo $slot['day_of_week'] === 'monday' ? 'selected' : ''; ?>>Pazartesi</option>
                                                        <option value="tuesday" <?php echo $slot['day_of_week'] === 'tuesday' ? 'selected' : ''; ?>>Salı</option>
                                                        <option value="wednesday" <?php echo $slot['day_of_week'] === 'wednesday' ? 'selected' : ''; ?>>Çarşamba</option>
                                                        <option value="thursday" <?php echo $slot['day_of_week'] === 'thursday' ? 'selected' : ''; ?>>Perşembe</option>
                                                        <option value="friday" <?php echo $slot['day_of_week'] === 'friday' ? 'selected' : ''; ?>>Cuma</option>
                                                        <option value="saturday" <?php echo $slot['day_of_week'] === 'saturday' ? 'selected' : ''; ?>>Cumartesi</option>
                                                        <option value="sunday" <?php echo $slot['day_of_week'] === 'sunday' ? 'selected' : ''; ?>>Pazar</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Başlangıç Saati</label>
                                                    <input type="time" class="form-control" name="availability[<?php echo $index; ?>][start_time]" value="<?php echo $slot['start_time']; ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Bitiş Saati</label>
                                                    <input type="time" class="form-control" name="availability[<?php echo $index; ?>][end_time]" value="<?php echo $slot['end_time']; ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-danger btn-sm remove-availability" style="margin-top: 32px;">Sil</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary mb-4" id="add-availability">Yeni Zaman Dilimi Ekle</button>
                            <?php endif; ?>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Profili Güncelle</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Yeni ders ekleme
            $('#add-subject').click(function() {
                const index = $('#subjects-container .subject-form').length;
                const newSubject = `
                    <div class="subject-form">
                        <div class="mb-3">
                            <label class="form-label">Ders Adı</label>
                            <input type="text" class="form-control" name="subjects[${index}][name]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="subjects[${index}][description]"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seviye</label>
                            <select class="form-control" name="subjects[${index}][level]">
                                <option value="beginner">Başlangıç</option>
                                <option value="intermediate">Orta</option>
                                <option value="advanced">İleri</option>
                                <option value="all">Tüm Seviyeler</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm remove-subject">Dersi Sil</button>
                    </div>
                `;
                $('#subjects-container').append(newSubject);
            });

            // Ders silme
            $(document).on('click', '.remove-subject', function() {
                $(this).closest('.subject-form').remove();
            });

            // Yeni müsaitlik ekleme
            $('#add-availability').click(function() {
                const index = $('#availability-container .availability-slot').length;
                const newAvailability = `
                    <div class="availability-slot">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Gün</label>
                                <select class="form-control" name="availability[${index}][day]">
                                    <option value="monday">Pazartesi</option>
                                    <option value="tuesday">Salı</option>
                                    <option value="wednesday">Çarşamba</option>
                                    <option value="thursday">Perşembe</option>
                                    <option value="friday">Cuma</option>
                                    <option value="saturday">Cumartesi</option>
                                    <option value="sunday">Pazar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Başlangıç Saati</label>
                                <input type="time" class="form-control" name="availability[${index}][start_time]" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bitiş Saati</label>
                                <input type="time" class="form-control" name="availability[${index}][end_time]" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm remove-availability" style="margin-top: 32px;">Sil</button>
                            </div>
                        </div>
                    </div>
                `;
                $('#availability-container').append(newAvailability);
            });

            // Müsaitlik silme
            $(document).on('click', '.remove-availability', function() {
                $(this).closest('.availability-slot').remove();
            });
        });
    </script>
</body>
</html>
