<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['teacher_id']) || !isset($_GET['date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher ID and date are required']);
    exit;
}

$teacher_id = sanitize($_GET['teacher_id']);
$date = sanitize($_GET['date']);

try {
    // Seçilen günün adını bul (monday, tuesday, etc.)
    $day_name = strtolower(date('l', strtotime($date)));
    
    // Öğretmenin o gün için müsait saatlerini getir
    $stmt = $pdo->prepare("SELECT start_time, end_time 
                          FROM teacher_availability 
                          WHERE teacher_id = ? AND day_of_week = ?");
    $stmt->execute([$teacher_id, $day_name]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mevcut randevuları getir
    $stmt = $pdo->prepare("SELECT time, duration 
                          FROM appointments 
                          WHERE teacher_id = ? AND date = ? AND status != 'cancelled'");
    $stmt->execute([$teacher_id, $date]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Müsait saatleri hesapla
    $available_times = [];
    foreach ($availability as $slot) {
        $start = new DateTime($slot['start_time']);
        $end = new DateTime($slot['end_time']);
        
        // 30'ar dakikalık slotlar oluştur
        $interval = new DateInterval('PT30M');
        $current = clone $start;
        
        while ($current < $end) {
            $time_str = $current->format('H:i');
            $is_available = true;
            
            // Randevu çakışması kontrolü
            foreach ($appointments as $appointment) {
                $appt_start = new DateTime($appointment['time']);
                $appt_end = clone $appt_start;
                $appt_end->add(new DateInterval('PT' . $appointment['duration'] . 'M'));
                
                if ($current >= $appt_start && $current < $appt_end) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $available_times[] = $time_str;
            }
            
            $current->add($interval);
        }
    }
    
    echo json_encode($available_times);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
