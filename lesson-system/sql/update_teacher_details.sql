-- teacher_details tablosuna yeni sütunlar ekleme
ALTER TABLE teacher_details
ADD COLUMN expertise TEXT NULL,
ADD COLUMN education TEXT NULL,
ADD COLUMN experience TEXT NULL,
ADD COLUMN about_me TEXT NULL;
