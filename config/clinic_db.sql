DROP DATABASE IF EXISTS clinic_db;
CREATE DATABASE clinic_db;
USE clinic_db;

-- 使用者帳號表
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(20) NOT NULL UNIQUE,
  id_number VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'doctor', 'patient') NOT NULL,
  name VARCHAR(50),
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 病患資料表（FK ON DELETE CASCADE）
CREATE TABLE patients (
  patient_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  id_number VARCHAR(20),
  phone VARCHAR(20),
  gender ENUM('male', 'female', 'other'),
  birthdate DATE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 科別資料表
CREATE TABLE departments (
  department_id INT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (department_id, name) VALUES
(101, '眼科'),
(102, '耳鼻喉科'),
(103, '小兒科'),
(104, '皮膚科'),
(105, '骨科');

-- 醫師資料表（user_id 為 INT 並設 ON DELETE CASCADE）
CREATE TABLE doctors (
  doctor_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NOT NULL,
  profile TEXT,
  is_active TEXT,
  photo_url TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(department_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 管理員資料表（user_id 為 INT 並設 ON DELETE CASCADE）
CREATE TABLE admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 預約資料表
CREATE TABLE appointments (
  appointment_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  doctor_id INT,
  appointment_date DATE,
  time_slot VARCHAR(50),
  service_type ENUM('consultation', 'checkup', 'follow_up', 'emergency'),
  status ENUM('scheduled', 'checked_in', 'cancelled', 'completed', 'no-show'),
  checkin_time DATETIME,
  substitute_doctor_id INT,
  modified_by_admin_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  FOREIGN KEY (substitute_doctor_id) REFERENCES doctors(doctor_id),
  FOREIGN KEY (modified_by_admin_id) REFERENCES admins(admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 意見回饋表
CREATE TABLE feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  rating INT,
  comment TEXT,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--醫師排班
CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    note TEXT,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--通知病患
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT,
  patient_id INT,
  type ENUM('email', 'sms', 'line') NOT NULL,
  message TEXT,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;