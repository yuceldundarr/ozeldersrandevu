-- Randevular tablosuna yeni alanlar ekleme
ALTER TABLE appointments
ADD COLUMN status_history TEXT DEFAULT NULL COMMENT 'Durum değişikliği geçmişi JSON formatında',
ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Son güncelleme tarihi',
ADD COLUMN updated_by INT DEFAULT NULL COMMENT 'Güncelleyen admin ID',
ADD COLUMN notes TEXT DEFAULT NULL COMMENT 'Randevu notları';

-- Status alanı için varsayılan değeri güncelleme
ALTER TABLE appointments
MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending' NOT NULL;
