-- Profil ve banner resim alanlarını ekle
ALTER TABLE users
ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL,
ADD COLUMN banner_image VARCHAR(255) DEFAULT NULL;

-- Öğretmen profil alanlarını ekle
ALTER TABLE users
ADD COLUMN expertise VARCHAR(255) DEFAULT NULL,
ADD COLUMN education TEXT DEFAULT NULL,
ADD COLUMN experience TEXT DEFAULT NULL,
ADD COLUMN about TEXT DEFAULT NULL;
