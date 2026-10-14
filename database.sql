CREATE DATABASE IF NOT EXISTS suubow_documents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE suubow_documents;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(180) NOT NULL,
  mobile VARCHAR(40) NOT NULL,
  address VARCHAR(255) NOT NULL,
  identity_number VARCHAR(100) NOT NULL,
  document_type VARCHAR(100) NOT NULL,
  ref_no VARCHAR(100) NOT NULL UNIQUE,
  pdf_filename VARCHAR(255) NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mobile (mobile),
  INDEX idx_identity (identity_number),
  INDEX idx_type_date (document_type, created_at),
  CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS employees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(180) NOT NULL,
  mobile VARCHAR(40) NOT NULL,
  address VARCHAR(255) NOT NULL,
  education ENUM('Jaamacad','Dugsi sare','Dugsi dhexe','Dugsi hoose','Waxbarasho kale') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  employee_role ENUM('Qoraa','Sawir Qaade','Document keydiye') NOT NULL DEFAULT 'Qoraa',
  photo VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  check_in DATETIME NULL,
  check_out DATETIME NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attendance_user_id (user_id),
  UNIQUE KEY unique_employee_day (employee_id, work_date),
  INDEX idx_work_date (work_date),
  CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- Default admin: username admin / password ChangeMe123!
-- Generated with PHP password_hash('ChangeMe123!', PASSWORD_DEFAULT)
INSERT IGNORE INTO users (username, password_hash, full_name, role)
VALUES ('admin', '$2y$12$wMZ0Wr2ZtyvcY6oIWRw/wOWyEyfYOIkmtZxJGoNiekZQY1RGibjOG', 'Dr. Mohamed Yusuf Ali (Caddow)', 'admin');
