-- ReadEase Reading Tool Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS `forda_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `forda_app`;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','student') DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reading materials table
CREATE TABLE IF NOT EXISTS reading_materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  session_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-6 (AIM sessions)',
  reading_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Reading 1,2,3... within a session',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Recordings table
CREATE TABLE IF NOT EXISTS recordings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  material_id INT NOT NULL,
  audio_path VARCHAR(255) NOT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  feedback TEXT DEFAULT NULL,
  feedback_at TIMESTAMP NULL,
  feedback_by INT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES reading_materials(id) ON DELETE CASCADE,
  FOREIGN KEY (feedback_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- Admin accounts (5 groups) + sample student account
-- ============================================================

-- Admin accounts (passwords: Admin@Group1 through Admin@Group5)
INSERT INTO users (name, email, password, role) VALUES
('Lorbelle Ganzan',  'lorbelleganzan@gmail.com',  '$2y$10$s/2EWiRI/w0zXZnheCyEoupe93WbyOiPPQC8c4MkeHExL7rXAN7Oi', 'admin'),
('Admin Group 2',    'admin.group2@gmail.com',    '$2y$10$79A637EUUUy3eBGRCYNvyu.qF4XA4RrLfBMdmPZwIAegjvVrSkSjC', 'admin'),
('Admin Group 3',    'admin.group3@gmail.com',    '$2y$10$p2so39ieMKGFx4.aLbxIBOENgm6fQXAcnolR17Cf2xHYl3Mf45qtS', 'admin'),
('Admin Group 4',    'admin.group4@gmail.com',    '$2y$10$Ox/mEVgS6RHEYSVkTiUVDOOG6cnnkeLscLVebzt9sNY6Wt7b1blaK', 'admin'),
('Admin Group 5',    'admin.group5@gmail.com',    '$2y$10$nss5wH9AqrG92gSVNV3OluDWCxMIOzuWKJqJZMXUIP/Ir847Ngc6K', 'admin');

-- Sample student account (password: student123)
INSERT INTO users (name, email, password, role) VALUES
('Jane Student', 'student@gmail.com', '$2y$10$wlUax898pG4mhi/SeUDaSeN5zsZWVfJo8zzE08AIYpuLzTsopG/5m', 'student');

-- Sample reading materials
INSERT INTO reading_materials (title, content, level, created_by) VALUES
('The Cat', 'The cat is on the mat. The cat is fat. The cat sat. The cat has a hat. The cat is happy. Look at the cat. The cat can run. The cat can jump. The cat is a good pet.', 'beginner', 1),
('The Dog', 'The dog can run. The dog can jump. The dog is big. The dog likes to play. The dog is my friend. I feed my dog every day. My dog is brown and white. My dog wags its tail when it is happy.', 'beginner', 1),
('The Sun', 'The sun is bright. The sun gives us light. The sun is hot. We need the sun to grow plants. The sun rises in the east. The sun sets in the west. The sun is a star. It is very far away from us.', 'beginner', 1),
('My Family', 'I have a family. My mom is kind. My dad is strong. My sister is smart. My brother is funny. I love my family very much. We eat dinner together every night. We help each other when we have problems. My family makes me happy.', 'intermediate', 1),
('The School', 'I go to school every day. I learn to read and write. My teacher is very nice. I have many friends at school. School is fun and important. We study math, science, and English. I like to read books in the library. Education helps us build a better future.', 'intermediate', 1),
('The Ocean', 'The ocean is vast and deep. It covers more than seventy percent of the Earth\'s surface. Many creatures live in the ocean, from tiny fish to enormous whales. The ocean provides food and oxygen for all living things. We must protect our oceans from pollution and climate change to preserve marine life for future generations.', 'advanced', 1),
('Technology Today', 'Technology has transformed the way we live and work. Smartphones, computers, and the internet have connected people across the globe. Artificial intelligence is now being used in medicine, education, and business. While technology brings many benefits, it also presents challenges such as privacy concerns and digital addiction. We must use technology responsibly and thoughtfully.', 'advanced', 1);
