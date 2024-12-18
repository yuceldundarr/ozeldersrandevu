<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Önce teacher_details tablosunda kayıt var mı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM teacher_details WHERE teacher_id = ?");
    $stmt->execute([1]); // ID'si 1 olan öğretmen için
    $teacher = $stmt->fetch();

    if ($teacher) {
        // Kayıt varsa güncelle
        $sql = "UPDATE teacher_details SET 
                expertise = ?,
                education = ?,
                experience = ?,
                about_me = ?
                WHERE teacher_id = ?";
    } else {
        // Kayıt yoksa ekle
        $sql = "INSERT INTO teacher_details 
                (teacher_id, expertise, education, experience, about_me) 
                VALUES (?, ?, ?, ?, ?)";
    }

    $params = [
        "Matematik, Fizik ve Kimya alanlarında uzmanlaşmış bir eğitimciyim. Özellikle lise ve üniversite düzeyinde öğrencilere ders vermekteyim.",
        "- İstanbul Teknik Üniversitesi, Fizik Mühendisliği (2015-2019)\n- Boğaziçi Üniversitesi, Eğitim Bilimleri Yüksek Lisans (2019-2021)",
        "- Özel Ders Öğretmenliği (2019-Günümüz)\n- ABC Dershanesi Fizik Öğretmeni (2020-2022)\n- XYZ Koleji Matematik Öğretmeni (2022-Günümüz)",
        "Merhaba! 5 yıldır özel ders ve kurumsal eğitmenlik yapıyorum. Öğrencilerimin başarısı için elimden gelenin en iyisini yapmaya çalışıyorum. Her öğrencinin öğrenme stilinin farklı olduğuna inanıyor ve derslerimi buna göre şekillendiriyorum."
    ];

    if (!$teacher) {
        array_unshift($params, 1); // Yeni kayıt için teacher_id'yi başa ekle
    } else {
        array_push($params, 1); // Güncelleme için teacher_id'yi sona ekle
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo "Öğretmen bilgileri başarıyla eklendi/güncellendi!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
