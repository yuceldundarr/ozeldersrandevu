<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Ara - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-4">Öğretmen Ara</h3>
                        <form method="GET" action="" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="subject" class="form-select">
                                        <option value="">Ders Seçin</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
                                        while ($subject = $stmt->fetch()) {
                                            $selected = ($_GET['subject'] ?? '') == $subject['id'] ? 'selected' : '';
                                            echo "<option value='{$subject['id']}' {$selected}>{$subject['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="name" class="form-control" placeholder="Öğretmen Adı" 
                                           value="<?php echo $_GET['name'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Ara</button>
                                </div>
                            </div>
                        </form>

                        <?php
                        if ($_SERVER['REQUEST_METHOD'] == 'GET' && (!empty($_GET['subject']) || !empty($_GET['name']))) {
                            $where = [];
                            $params = [];

                            if (!empty($_GET['subject'])) {
                                $where[] = "ts.subject_id = ?";
                                $params[] = $_GET['subject'];
                            }

                            if (!empty($_GET['name'])) {
                                $where[] = "u.name LIKE ?";
                                $params[] = "%{$_GET['name']}%";
                            }

                            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                            $sql = "SELECT DISTINCT u.*, ts.hourly_rate, s.name as subject_name 
                                    FROM users u 
                                    JOIN teacher_subjects ts ON u.id = ts.teacher_id 
                                    JOIN subjects s ON ts.subject_id = s.id 
                                    $whereClause AND u.user_type = 'teacher'";

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            $teachers = $stmt->fetchAll();

                            if (count($teachers) > 0) {
                                foreach ($teachers as $teacher) {
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($teacher['name']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Ders:</strong> <?php echo htmlspecialchars($teacher['subject_name']); ?><br>
                                                        <strong>Saatlik Ücret:</strong> <?php echo number_format($teacher['hourly_rate'], 2); ?> TL
                                                    </p>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <a href="book-appointment.php?teacher_id=<?php echo $teacher['id']; ?>" 
                                                       class="btn btn-primary">Randevu Al</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="alert alert-info">Aramanıza uygun öğretmen bulunamadı.</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
