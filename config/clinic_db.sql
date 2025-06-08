DROP DATABASE IF EXISTS clinic_db;
CREATE DATABASE clinic_db;
USE clinic_db;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(20) NOT NULL UNIQUE,
  id_number VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'doctor', 'patient') NOT NULL,
  name VARCHAR(50),
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE patients (
  patient_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  id_number VARCHAR(20),
  phone VARCHAR(20),
  gender ENUM('male', 'female', 'other'),
  birthdate DATE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE departments (
  department_id INT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
INSERT INTO departments (department_id, name)
VALUES (101, '眼科'),
  (102, '耳鼻喉科'),
  (103, '小兒科'),
  (104, '皮膚科'),
  (105, '骨科');
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE appointments (
  appointment_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  doctor_id INT,
  appointment_date DATE,
  time_slot VARCHAR(50),
  service_type ENUM(
    'consultation',
    'checkup',
    'follow_up',
    'emergency'
  ),
  status ENUM(
    'scheduled',
    'checked_in',
    'cancelled',
    'completed',
    'no-show'
  ),
  checkin_time DATETIME,
  substitute_doctor_id INT,
  modified_by_admin_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  FOREIGN KEY (substitute_doctor_id) REFERENCES doctors(doctor_id),
  FOREIGN KEY (modified_by_admin_id) REFERENCES admins(admin_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  rating INT,
  comment TEXT,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE schedules (
  schedule_id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  schedule_date DATE NOT NULL,
  shift ENUM('morning', 'afternoon', 'evening') NOT NULL,
  is_available BOOLEAN NOT NULL DEFAULT TRUE,
  note TEXT,
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT,
  patient_id INT,
  type ENUM('email', 'sms', 'line') NOT NULL,
  message TEXT,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- 預設醫生 user
INSERT INTO users (user_id, id_number, password, role, name, email)
VALUES (
    'doc101',
    'A123456789',
    '$2y$10$PDSbrnBI0HB2Ac4oQcIYy.XmAzBtwCC0R.X.mLqzpUxYGJNC9TR5m',
    'doctor',
    '林小芳',
    'doc101@example.c'
  ),
  (
    'doc102',
    'B123456789',
    '$2y$10$wXuMuUAC1S8de1exbSRTSe9wjUzSJqF2F3L8.kZJSsFWhbDYcKJb2',
    'doctor',
    '李小華',
    'doc102@example.com'
  ),
  (
    'doc103',
    'C123456789',
    '$2y$10$bCiZf3S4WI5OaH5jtFAY4eYyQ8TipR91O5MWThPj3A8oFed5QuxTG',
    'doctor',
    '陳美麗',
    'doc103@example.com'
  ),
  (
    'doc104',
    'D123456789',
    '$2y$10$LmONmJ5ZVD/TgNzzWQU5weTakqCoDvWvgBSivIxZlKDzJ9Ap9WUMm',
    'doctor',
    '張志強',
    'doc104@example.com'
  ),
  (
    'doc105',
    'E123456789',
    '$2y$10$Vn2a5j.Bg3dwjMl3SWT0u.twfCzdkr.pI9fYauiEq6ktXr7QX90UW',
    'doctor',
    '王大明',
    'doc105@example.com'
  ),
  (
    'doc106',
    'F123456789',
    '$2y$10$rljD.pJwhEY6St5s/w3wBOTMUEi/sU8uleP2VQwpLLqyMdiRIVbdG',
    'doctor',
    '黃偉哲',
    'doc106@example.com'
  ),
  (
    'doc107',
    'G123456789',
    '$2y$10$jwf3cD.jiMn.F2ve6oEYPOjslLc.jj0jF4VLDFIlmCnyEJCwK6I0m',
    'doctor',
    '吳怡君',
    'doc107@example.com'
  ),
  (
    'doc108',
    'H123456789',
    '$2y$10$DNB1c/OsVHTdFyNH07.sCeDkrwYP8x3.dU3Q6RNTS88EjjoUFda52',
    'doctor',
    '周建國',
    'doc108@example.com'
  ),
  (
    'doc109',
    'I123456789',
    '$2y$10$edGzciDYZDnv5ShWKO2G...fq0Kqdc.tl6e/d2oGlJTBGQXH0vUvq',
    'doctor',
    '許雅婷',
    'doc109@example.com'
  ),
  (
    'doc110',
    'J123456789',
    '$2y$10$HEuLSRX8FyEFZtW4isocGOIuYBirSVIZzWj841yOOwy78x33AcqDK',
    'doctor',
    '鄭文豪',
    'doc110@example.com'
  );
-- 將 user id 取出對應 doctors
INSERT INTO doctors (
    user_id,
    department_id,
    profile,
    is_active,
    photo_url
  )
VALUES (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc101'
    ),
    101,
    '眼科專長：白內障、青光眼',
    '1',
    "1.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc102'
    ),
    101,
    '眼科專長：視網膜、近視雷射',
    '1',
    "2.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc103'
    ),
    102,
    '耳鼻喉專長：過敏性鼻炎',
    '1',
    "3.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc104'
    ),
    102,
    '耳鼻喉專長：中耳炎、聽力障礙',
    '1',
    "4.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc105'
    ),
    103,
    '小兒科專長：新生兒照護',
    '1',
    "5.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc106'
    ),
    103,
    '小兒科專長：兒童過敏、氣喘',
    '1',
    "6.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc107'
    ),
    104,
    '皮膚科專長：青春痘、濕疹',
    '1',
    "7.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc108'
    ),
    104,
    '皮膚科專長：皮膚過敏、蕁麻疹',
    '1',
    "10.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc109'
    ),
    105,
    '骨科專長：關節炎、骨折',
    '1',
    "8.jpg"
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'doc110'
    ),
    105,
    '骨科專長：脊椎側彎、運動傷害',
    '1',
    "9.jpg"
  );