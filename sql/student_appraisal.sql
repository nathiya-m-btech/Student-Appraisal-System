CREATE DATABASE IF NOT EXISTS student_appraisal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_appraisal;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','faculty','admin') NOT NULL,
  email VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(200),
  roll VARCHAR(50),
  batch VARCHAR(20),
  department VARCHAR(100),
  cgpa DECIMAL(3,2),
  coding_score INT DEFAULT 0,
  mentor_name VARCHAR(150),
  family_members INT DEFAULT 0,
  dob DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE certificates (
  cert_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  extracted_text TEXT,
  level ENUM('intra','college','state','national','international','other') DEFAULT 'other',
  prize VARCHAR(100),
  event_name VARCHAR(200),
  cert_date DATE NULL,
  verified TINYINT(1) DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

CREATE TABLE marks (
  mark_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  category VARCHAR(100),
  raw_score DECIMAL(8,3),
  computed_marks DECIMAL(8,3),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

CREATE TABLE rankings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  topsis_score DECIMAL(8,5),
  rf_predicted_label VARCHAR(50),
  final_score DECIMAL(8,5),
  rank_pos INT,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
