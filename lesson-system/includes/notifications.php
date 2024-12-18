<?php
require_once 'functions.php';

/**
 * Kullanıcıya bildirim gönder
 * 
 * @param int $user_id Kullanıcı ID
 * @param string $title Bildirim başlığı
 * @param string $message Bildirim mesajı
 * @param string $type Bildirim tipi (info, success, warning, danger)
 * @param string|null $link İsteğe bağlı yönlendirme linki
 * @return bool
 */
function send_notification($user_id, $title, $message, $type = 'info', $link = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch (Exception $e) {
        error_log("Bildirim gönderme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcının okunmamış bildirimlerini getir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array
 */
function get_unread_notifications($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Bildirim getirme hatası: " . $e->getMessage());
        return [];
    }
}

/**
 * Bildirimi okundu olarak işaretle
 * 
 * @param int $notification_id Bildirim ID
 * @param int $user_id Kullanıcı ID (güvenlik için)
 * @return bool
 */
function mark_notification_as_read($notification_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notification_id, $user_id]);
    } catch (Exception $e) {
        error_log("Bildirim güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Tüm bildirimleri okundu olarak işaretle
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool
 */
function mark_all_notifications_as_read($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND is_read = 0
        ");
        
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Bildirim güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Randevu durumu değiştiğinde bildirim gönder
 * 
 * @param int $appointment_id Randevu ID
 * @param string $new_status Yeni durum
 * @param string $notes İsteğe bağlı notlar
 * @return bool
 */
function send_appointment_status_notification($appointment_id, $new_status, $notes = '') {
    global $pdo;
    
    try {
        // Randevu bilgilerini al
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                s.id as student_id,
                s.name as student_name,
                t.name as teacher_name
            FROM appointments a
            LEFT JOIN users s ON a.student_id = s.id
            LEFT JOIN users t ON a.teacher_id = t.id
            WHERE a.id = ?
        ");
        
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            return false;
        }
        
        // Durum mesajlarını hazırla
        $status_messages = [
            'pending' => 'beklemede',
            'confirmed' => 'onaylandı',
            'cancelled' => 'iptal edildi',
            'completed' => 'tamamlandı'
        ];
        
        $status_text = $status_messages[$new_status] ?? $new_status;
        
        // Bildirim başlığı ve mesajını oluştur
        $title = "Randevu Durumu Güncellendi";
        $message = sprintf(
            "%s ile %s tarihindeki randevunuz %s.",
            $appointment['teacher_name'],
            date('d.m.Y H:i', strtotime($appointment['date'] . ' ' . $appointment['time'])),
            $status_text
        );
        
        if ($notes) {
            $message .= "\n\nNot: " . $notes;
        }
        
        // Bildirim tipini belirle
        $type = 'info';
        switch ($new_status) {
            case 'confirmed':
                $type = 'success';
                break;
            case 'cancelled':
                $type = 'danger';
                break;
            case 'completed':
                $type = 'info';
                break;
        }
        
        // Öğrenciye bildirim gönder
        return send_notification(
            $appointment['student_id'],
            $title,
            $message,
            $type,
            "appointments.php?id=" . $appointment_id
        );
        
    } catch (Exception $e) {
        error_log("Randevu bildirim hatası: " . $e->getMessage());
        return false;
    }
}
