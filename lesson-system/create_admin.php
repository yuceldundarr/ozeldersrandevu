<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$admin_password = 'admin123'; // Bu şifreyi kullanacağız
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Önce eski admin hesabını temizle
$stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
$stmt->execute(['admin@example.com']);

// Yeni admin hesabı oluştur
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)");
if($stmt->execute(['Admin', 'admin@example.com', $hashed_password, '5555555555', 'admin'])) {
    echo "Admin hesabı başarıyla oluşturuldu!<br>";
    echo "Email: admin@example.com<br>";
    echo "Şifre: admin123";
} else {
    echo "Hata oluştu!";
}
