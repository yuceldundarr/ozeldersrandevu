<?php
require_once '../config/database.php';

function get_setting($key, $default = null) {
    global $pdo;
    
    static $settings = null;
    
    // Ayarları bir kere yükle ve cache'le
    if($settings === null) {
        $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM settings");
        $settings = [];
        while($row = $stmt->fetch()) {
            // Boolean değerleri dönüştür
            if($row['setting_type'] === 'boolean') {
                $settings[$row['setting_key']] = $row['setting_value'] == '1';
            } else {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

function update_setting($key, $value) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    return $stmt->execute([$value, $key]);
}
?>
