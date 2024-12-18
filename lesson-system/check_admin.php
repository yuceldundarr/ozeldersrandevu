<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Admin kullanıcısını kontrol et
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'admin'");
$stmt->execute(['admin@example.com']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Admin kullanıcısı bulundu:<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "İsim: " . $admin['name'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Kullanıcı Tipi: " . $admin['user_type'] . "<br>";
} else {
    echo "Admin kullanıcısı bulunamadı!";
}
