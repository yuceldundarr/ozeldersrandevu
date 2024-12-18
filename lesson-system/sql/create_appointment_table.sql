-- Randevular tablosu
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    duration INT NOT NULL DEFAULT 60, -- Dakika cinsinden
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Örnek randevular
INSERT INTO appointments (student_id, teacher_id, subject, date, time, duration, status) 
VALUES 
    (1, 2, 'Matematik Dersi', CURDATE(), '10:00:00', 60, 'pending'),
    (1, 2, 'Matematik Dersi', CURDATE(), '14:00:00', 60, 'confirmed'),
    (1, 2, 'Matematik Dersi', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 90, 'completed'),
    (1, 2, 'Matematik Dersi', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:00:00', 60, 'cancelled');
