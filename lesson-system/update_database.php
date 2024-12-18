<?php
require_once 'config/database.php';

try {
    // Yeni sütunları ekle
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS expertise VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS education TEXT DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS experience TEXT DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS about TEXT DEFAULT NULL"
    ];

    foreach ($alterQueries as $query) {
        $pdo->exec($query);
    }

    echo "Veritabanı başarıyla güncellendi!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
