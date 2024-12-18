<?php
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function createUser($pdo, $name, $email, $password, $user_type) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$name, $email, $hashed_password, $user_type]);
}

function createAppointment($pdo, $teacher_id, $student_id, $subject, $date, $time, $duration) {
    $sql = "INSERT INTO appointments (teacher_id, student_id, subject, date, time, duration, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$teacher_id, $student_id, $subject, $date, $time, $duration]);
}

function getAppointments($pdo, $user_id, $user_type) {
    $column = ($user_type == 'teacher') ? 'teacher_id' : 'student_id';
    
    $sql = "SELECT a.*, u1.name as teacher_name, u2.name as student_name 
            FROM appointments a 
            JOIN users u1 ON a.teacher_id = u1.id 
            JOIN users u2 ON a.student_id = u2.id 
            WHERE a.$column = ?
            ORDER BY a.date DESC, a.time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateAppointmentStatus($pdo, $appointment_id, $status) {
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$status, $appointment_id]);
}
?>
