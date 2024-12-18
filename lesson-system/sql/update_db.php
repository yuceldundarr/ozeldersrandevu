<?php
require_once __DIR__ . '/../config/database.php';

try {
    // teacher_details tablosuna yeni sütunlar ekleme
    $sql = "
        ALTER TABLE teacher_details
        ADD COLUMN IF NOT EXISTS expertise TEXT NULL,
        ADD COLUMN IF NOT EXISTS education TEXT NULL,
        ADD COLUMN IF NOT EXISTS experience TEXT NULL,
        ADD COLUMN IF NOT EXISTS about_me TEXT NULL
    ";
    
    $pdo->exec($sql);
    echo "Veritabanı başarıyla güncellendi!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
