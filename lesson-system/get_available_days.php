<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['teacher_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher ID is required']);
    exit;
}

$teacher_id = sanitize($_GET['teacher_id']);

try {
    // Öğretmenin müsait günlerini getir
    $stmt = $pdo->prepare("SELECT DISTINCT day_of_week FROM teacher_availability WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $available_days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Günleri tarih formatına çevir
    $dates = [];
    $today = new DateTime();
    $end = (new DateTime())->modify('+2 months'); // 2 ay sonrasına kadar
    
    while ($today <= $end) {
        $day_name = strtolower($today->format('l')); // Günün adını al (monday, tuesday, etc.)
        if (in_array($day_name, $available_days)) {
            $dates[] = $today->format('Y-m-d');
        }
        $today->modify('+1 day');
    }
    
    echo json_encode(['available_days' => $dates]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
