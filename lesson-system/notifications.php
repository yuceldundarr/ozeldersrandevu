<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
checkLogin();

// Bildirimleri okundu olarak işaretle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $sql = "UPDATE notifications SET read_status = TRUE WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    
    header("Location: notifications.php");
    exit();
}

// Bildirimleri getir
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimler - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Bildirimler</h5>
                        <?php if (count($notifications) > 0): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="mark_read" value="1">
                            <button type="submit" class="btn btn-sm btn-secondary">
                                Tümünü Okundu İşaretle
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item p-3 border-bottom <?php echo $notification['read_status'] ? '' : 'bg-light'; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php
                                            $icon = '';
                                            switch ($notification['type']) {
                                                case 'appointment':
                                                    $icon = 'bi-calendar-event';
                                                    break;
                                                case 'message':
                                                    $icon = 'bi-envelope';
                                                    break;
                                                case 'payment':
                                                    $icon = 'bi-credit-card';
                                                    break;
                                                default:
                                                    $icon = 'bi-bell';
                                            }
                                            ?>
                                            <i class="bi <?php echo $icon; ?> me-2"></i>
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center mb-0">Henüz bildiriminiz bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
