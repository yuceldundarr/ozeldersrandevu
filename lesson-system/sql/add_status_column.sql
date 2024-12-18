-- users tablosuna status sütunu ekleme
ALTER TABLE users
ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1;

-- Var olan kullanıcıların status değerini 1 (aktif) olarak güncelle
UPDATE users SET status = 1;
