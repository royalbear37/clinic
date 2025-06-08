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
  visit_number INT DEFAULT NULL,
  -- 新增這一行
  checkin_time DATETIME,
  substitute_doctor_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  FOREIGN KEY (substitute_doctor_id) REFERENCES doctors(doctor_id)
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
-- 10位病患 user
INSERT INTO users (user_id, id_number, password, role, name, email)
VALUES (
    'pat201',
    'P123456781',
    '$2y$10$abcde1',
    'patient',
    '王小明',
    'pat201@example.com'
  ),
  (
    'pat202',
    'P123456782',
    '$2y$10$abcde2',
    'patient',
    '李小美',
    'pat202@example.com'
  ),
  (
    'pat203',
    'P123456783',
    '$2y$10$abcde3',
    'patient',
    '張大華',
    'pat203@example.com'
  ),
  (
    'pat204',
    'P123456784',
    '$2y$10$abcde4',
    'patient',
    '陳怡君',
    'pat204@example.com'
  ),
  (
    'pat205',
    'P123456785',
    '$2y$10$abcde5',
    'patient',
    '林志強',
    'pat205@example.com'
  ),
  (
    'pat206',
    'P123456786',
    '$2y$10$abcde6',
    'patient',
    '黃美麗',
    'pat206@example.com'
  ),
  (
    'pat207',
    'P123456787',
    '$2y$10$abcde7',
    'patient',
    '吳建國',
    'pat207@example.com'
  ),
  (
    'pat208',
    'P123456788',
    '$2y$10$abcde8',
    'patient',
    '周雅婷',
    'pat208@example.com'
  ),
  (
    'pat209',
    'P123456789',
    '$2y$10$abcde9',
    'patient',
    '許文豪',
    'pat209@example.com'
  ),
  (
    'pat210',
    'P123456780',
    '$2y$10$abcde0',
    'patient',
    '鄭偉哲',
    'pat210@example.com'
  );
-- 10位病患 patient
INSERT INTO patients (user_id, id_number, phone, gender, birthdate)
VALUES (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat201'
    ),
    'P123456781',
    '0911000001',
    'male',
    '1990-01-01'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat202'
    ),
    'P123456782',
    '0911000002',
    'female',
    '1992-02-02'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat203'
    ),
    'P123456783',
    '0911000003',
    'male',
    '1988-03-03'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat204'
    ),
    'P123456784',
    '0911000004',
    'female',
    '1995-04-04'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat205'
    ),
    'P123456785',
    '0911000005',
    'male',
    '1991-05-05'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat206'
    ),
    'P123456786',
    '0911000006',
    'female',
    '1993-06-06'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat207'
    ),
    'P123456787',
    '0911000007',
    'male',
    '1989-07-07'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat208'
    ),
    'P123456788',
    '0911000008',
    'female',
    '1994-08-08'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat209'
    ),
    'P123456789',
    '0911000009',
    'male',
    '1996-09-09'
  ),
  (
    (
      SELECT id
      FROM users
      WHERE user_id = 'pat210'
    ),
    'P123456780',
    '0911000010',
    'male',
    '1997-10-10'
  );
-- 6/8~6/14 班表（同一醫師一天可多班）
INSERT INTO schedules (
    doctor_id,
    schedule_date,
    shift,
    is_available,
    note
  )
VALUES -- 林小芳 (doctor_id=1, 眼科)
  (1, '2025-06-08', 'morning', 1, NULL),
  (1, '2025-06-08', 'afternoon', 1, NULL),
  (1, '2025-06-09', 'morning', 1, NULL),
  (1, '2025-06-09', 'evening', 1, NULL),
  (1, '2025-06-10', 'morning', 1, NULL),
  (1, '2025-06-10', 'afternoon', 1, NULL),
  (1, '2025-06-11', 'evening', 1, NULL),
  (1, '2025-06-12', 'morning', 1, NULL),
  (1, '2025-06-12', 'afternoon', 1, NULL),
  (1, '2025-06-13', 'morning', 1, NULL),
  (1, '2025-06-13', 'evening', 1, NULL),
  (1, '2025-06-14', 'afternoon', 1, NULL),
  -- 李小華 (doctor_id=2, 眼科)
  (2, '2025-06-08', 'morning', 1, NULL),
  (2, '2025-06-08', 'evening', 1, NULL),
  (2, '2025-06-09', 'afternoon', 1, NULL),
  (2, '2025-06-10', 'morning', 1, NULL),
  (2, '2025-06-10', 'evening', 1, NULL),
  (2, '2025-06-11', 'morning', 1, NULL),
  (2, '2025-06-12', 'evening', 1, NULL),
  (2, '2025-06-13', 'afternoon', 1, NULL),
  (2, '2025-06-14', 'morning', 1, NULL),
  (2, '2025-06-14', 'evening', 1, NULL),
  -- 陳美麗 (doctor_id=3, 耳鼻喉科)
  (3, '2025-06-08', 'morning', 1, NULL),
  (3, '2025-06-08', 'afternoon', 1, NULL),
  (3, '2025-06-09', 'morning', 1, NULL),
  (3, '2025-06-09', 'evening', 1, NULL),
  (3, '2025-06-10', 'morning', 1, NULL),
  (3, '2025-06-10', 'afternoon', 1, NULL),
  (3, '2025-06-11', 'evening', 1, NULL),
  (3, '2025-06-12', 'morning', 1, NULL),
  (3, '2025-06-12', 'afternoon', 1, NULL),
  (3, '2025-06-13', 'morning', 1, NULL),
  (3, '2025-06-13', 'evening', 1, NULL),
  (3, '2025-06-14', 'afternoon', 1, NULL),
  -- 張志強 (doctor_id=4, 耳鼻喉科)
  (4, '2025-06-08', 'morning', 1, NULL),
  (4, '2025-06-08', 'evening', 1, NULL),
  (4, '2025-06-09', 'afternoon', 1, NULL),
  (4, '2025-06-10', 'morning', 1, NULL),
  (4, '2025-06-10', 'evening', 1, NULL),
  (4, '2025-06-11', 'morning', 1, NULL),
  (4, '2025-06-12', 'evening', 1, NULL),
  (4, '2025-06-13', 'afternoon', 1, NULL),
  (4, '2025-06-14', 'morning', 1, NULL),
  (4, '2025-06-14', 'evening', 1, NULL),
  -- 王大明 (doctor_id=5, 小兒科)
  (5, '2025-06-08', 'morning', 1, NULL),
  (5, '2025-06-08', 'afternoon', 1, NULL),
  (5, '2025-06-09', 'morning', 1, NULL),
  (5, '2025-06-09', 'evening', 1, NULL),
  (5, '2025-06-10', 'morning', 1, NULL),
  (5, '2025-06-10', 'afternoon', 1, NULL),
  (5, '2025-06-11', 'evening', 1, NULL),
  (5, '2025-06-12', 'morning', 1, NULL),
  (5, '2025-06-12', 'afternoon', 1, NULL),
  (5, '2025-06-13', 'morning', 1, NULL),
  (5, '2025-06-13', 'evening', 1, NULL),
  (5, '2025-06-14', 'afternoon', 1, NULL),
  -- 黃偉哲 (doctor_id=6, 小兒科)
  (6, '2025-06-08', 'morning', 1, NULL),
  (6, '2025-06-08', 'evening', 1, NULL),
  (6, '2025-06-09', 'afternoon', 1, NULL),
  (6, '2025-06-10', 'morning', 1, NULL),
  (6, '2025-06-10', 'evening', 1, NULL),
  (6, '2025-06-11', 'morning', 1, NULL),
  (6, '2025-06-12', 'evening', 1, NULL),
  (6, '2025-06-13', 'afternoon', 1, NULL),
  (6, '2025-06-14', 'morning', 1, NULL),
  (6, '2025-06-14', 'evening', 1, NULL),
  -- 吳怡君 (doctor_id=7, 皮膚科)
  (7, '2025-06-08', 'morning', 1, NULL),
  (7, '2025-06-08', 'afternoon', 1, NULL),
  (7, '2025-06-09', 'morning', 1, NULL),
  (7, '2025-06-09', 'evening', 1, NULL),
  (7, '2025-06-10', 'morning', 1, NULL),
  (7, '2025-06-10', 'afternoon', 1, NULL),
  (7, '2025-06-11', 'evening', 1, NULL),
  (7, '2025-06-12', 'morning', 1, NULL),
  (7, '2025-06-12', 'afternoon', 1, NULL),
  (7, '2025-06-13', 'morning', 1, NULL),
  (7, '2025-06-13', 'evening', 1, NULL),
  (7, '2025-06-14', 'afternoon', 1, NULL),
  -- 周建國 (doctor_id=8, 皮膚科)
  (8, '2025-06-08', 'morning', 1, NULL),
  (8, '2025-06-08', 'evening', 1, NULL),
  (8, '2025-06-09', 'afternoon', 1, NULL),
  (8, '2025-06-10', 'morning', 1, NULL),
  (8, '2025-06-10', 'evening', 1, NULL),
  (8, '2025-06-11', 'morning', 1, NULL),
  (8, '2025-06-12', 'evening', 1, NULL),
  (8, '2025-06-13', 'afternoon', 1, NULL),
  (8, '2025-06-14', 'morning', 1, NULL),
  (8, '2025-06-14', 'evening', 1, NULL),
  -- 許雅婷 (doctor_id=9, 骨科)
  (9, '2025-06-08', 'morning', 1, NULL),
  (9, '2025-06-08', 'afternoon', 1, NULL),
  (9, '2025-06-09', 'morning', 1, NULL),
  (9, '2025-06-09', 'evening', 1, NULL),
  (9, '2025-06-10', 'morning', 1, NULL),
  (9, '2025-06-10', 'afternoon', 1, NULL),
  (9, '2025-06-11', 'evening', 1, NULL),
  (9, '2025-06-12', 'morning', 1, NULL),
  (9, '2025-06-12', 'afternoon', 1, NULL),
  (9, '2025-06-13', 'morning', 1, NULL),
  (9, '2025-06-13', 'evening', 1, NULL),
  (9, '2025-06-14', 'afternoon', 1, NULL),
  -- 鄭文豪 (doctor_id=10, 骨科)
  (10, '2025-06-08', 'morning', 1, NULL),
  (10, '2025-06-08', 'evening', 1, NULL),
  (10, '2025-06-09', 'afternoon', 1, NULL),
  (10, '2025-06-10', 'morning', 1, NULL),
  (10, '2025-06-10', 'evening', 1, NULL),
  (10, '2025-06-11', 'morning', 1, NULL),
  (10, '2025-06-12', 'evening', 1, NULL),
  (10, '2025-06-13', 'afternoon', 1, NULL),
  (10, '2025-06-14', 'morning', 1, NULL),
  (10, '2025-06-14', 'evening', 1, NULL);
CREATE TABLE prescriptions (
  prescription_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  doctor_id INT NOT NULL,
  patient_id INT NOT NULL,
  medication TEXT NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
  FOREIGN KEY (doctor_id) REFERENCES users(id),
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);
CREATE TABLE medications (
  med_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL
);
INSERT INTO medications (name)
VALUES ('普拿疼（Paracetamol）'),
  ('阿莫西林（Amoxicillin）'),
  ('克流感（Tamiflu）'),
  ('降壓藥（Amlodipine）'),
  ('胃藥（Omeprazole）'),
  ('抗組織胺（Cetirizine）'),
  ('止痛藥（Ibuprofen）'),
  ('糖尿病藥（Metformin）'),
  ('高膽固醇藥（Atorvastatin）'),
  ('感冒糖漿（Dextromethorphan）');