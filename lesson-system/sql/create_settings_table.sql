CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) NOT NULL,
    setting_label VARCHAR(255) NOT NULL,
    setting_type ENUM('text', 'textarea', 'number', 'email', 'color', 'image', 'boolean') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan ayarları ekle
INSERT INTO settings (setting_key, setting_value, setting_group, setting_label, setting_type) VALUES
-- Site Genel Ayarları
('site_title', 'Özel Ders Sistemi', 'general', 'Site Başlığı', 'text'),
('site_description', 'Online özel ders randevu sistemi', 'general', 'Site Açıklaması', 'textarea'),
('site_email', 'info@example.com', 'general', 'Site Email Adresi', 'email'),
('site_phone', '+90 555 555 5555', 'general', 'Site Telefon', 'text'),
('site_address', 'İstanbul, Türkiye', 'general', 'Site Adresi', 'textarea'),

-- Görünüm Ayarları
('primary_color', '#0d6efd', 'appearance', 'Ana Renk', 'color'),
('secondary_color', '#6c757d', 'appearance', 'İkincil Renk', 'color'),
('logo_image', NULL, 'appearance', 'Site Logo', 'image'),
('favicon', NULL, 'appearance', 'Favicon', 'image'),

-- Randevu Ayarları
('min_appointment_duration', '30', 'appointment', 'Minimum Randevu Süresi (dk)', 'number'),
('max_appointment_duration', '180', 'appointment', 'Maksimum Randevu Süresi (dk)', 'number'),
('appointment_interval', '30', 'appointment', 'Randevu Aralığı (dk)', 'number'),
('allow_same_day_booking', '1', 'appointment', 'Aynı Gün Randevuya İzin Ver', 'boolean'),
('max_future_booking_days', '30', 'appointment', 'Maksimum İleri Tarihli Randevu Günü', 'number'),

-- Email Ayarları
('smtp_host', 'smtp.example.com', 'email', 'SMTP Sunucu', 'text'),
('smtp_port', '587', 'email', 'SMTP Port', 'number'),
('smtp_username', '', 'email', 'SMTP Kullanıcı Adı', 'text'),
('smtp_password', '', 'email', 'SMTP Şifre', 'text'),
('smtp_encryption', 'tls', 'email', 'SMTP Güvenlik', 'text');
