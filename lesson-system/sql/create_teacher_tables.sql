-- Öğretmen detayları tablosu
CREATE TABLE IF NOT EXISTS teacher_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    bio TEXT,
    experience TEXT,
    education TEXT,
    hourly_rate DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Öğretmen dersleri tablosu
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    level ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Öğretmen müsaitlik tablosu
CREATE TABLE IF NOT EXISTS teacher_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Örnek öğretmen ekle
INSERT INTO users (name, email, password, user_type) 
VALUES ('Örnek Öğretmen', 'ogretmen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- Son eklenen öğretmenin ID'sini al
SET @teacher_id = LAST_INSERT_ID();

-- Öğretmen detaylarını ekle
INSERT INTO teacher_details (teacher_id, bio, experience, education, hourly_rate) 
VALUES (@teacher_id, 'Deneyimli matematik öğretmeni', '5 yıl deneyim', 'Matematik Öğretmenliği Bölümü', 150.00);

-- Öğretmenin derslerini ekle
INSERT INTO teacher_subjects (teacher_id, subject_name, description, level) 
VALUES (@teacher_id, 'Matematik', 'Lise ve üniversite matematik dersleri', 'all');

-- Öğretmenin müsaitlik durumunu ekle
INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) 
VALUES (@teacher_id, 'monday', '09:00:00', '17:00:00'),
       (@teacher_id, 'wednesday', '09:00:00', '17:00:00'),
       (@teacher_id, 'friday', '09:00:00', '17:00:00');
