CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(150) NOT NULL UNIQUE
);


CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    program_code VARCHAR(40) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100),
    login_id VARCHAR(30) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('student','professor','registrar') NOT NULL,
    account_status ENUM('Pending','Confirmed','Rejected') DEFAULT 'Confirmed'
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    student_id VARCHAR(50),
    department_id INT,
    program VARCHAR(50) DEFAULT 'BSCS',
    year_level INT DEFAULT 1,
    semester_level INT DEFAULT 1,
    enrollment_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    employee_id VARCHAR(50),
    specialization VARCHAR(100),
    department_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20),
    title VARCHAR(100),
    credits INT,
    professor_id INT,
    department_id INT,
    prerequisite_course_id INT NULL,
    year_level INT DEFAULT 1,
    semester_level INT DEFAULT 1,
    program VARCHAR(50) DEFAULT 'BSCS',
    FOREIGN KEY (professor_id) REFERENCES professors(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id)
);

CREATE TABLE course_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    professor_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    school_year VARCHAR(20) DEFAULT '2026-2027',
    semester_label VARCHAR(50) DEFAULT '1st Semester',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (professor_id) REFERENCES professors(id)
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    section_id INT NULL,
    semester VARCHAR(50),
    grade VARCHAR(10),
    status VARCHAR(20) DEFAULT 'Enrolled',
    remarks VARCHAR(20) DEFAULT 'ONGOING',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

INSERT INTO departments(department_name) VALUES
('College of Computer and Informatics (CCI)'),
('College of Education (COE)'),
('College of Engineering and Architecture (CEA)'),
('College of Industrial Technology (CIT)'),
('College of Arts and Sciences (CAS)');

INSERT INTO programs(department_id, program_code, program_name) VALUES
(1, 'BSCS', 'Bachelor of Science in Computer Science'),
(1, 'BSIT', 'Bachelor of Science in Information Technology'),
(1, 'BSIS', 'Bachelor of Science in Information System'),
(2, 'BEED', 'Bachelor of Elementary Education'),
(2, 'BSED-FIL', 'Bachelor of Secondary Education - Filipino'),
(2, 'BSED-MATH', 'Bachelor of Secondary Education - Mathematics'),
(2, 'BSED-SCI', 'Bachelor of Secondary Education - Science'),
(2, 'BSED-TLE', 'Bachelor of Secondary Education - Technology and Livelihood Education'),
(3, 'BSECE', 'Bachelor of Science in Electronics Engineering'),
(3, 'BSARCH', 'Bachelor of Science in Architecture'),
(3, 'BSEE', 'Bachelor of Science in Electrical Engineering'),
(3, 'BSME-AE', 'Bachelor of Science in Mechanical Engineering - Major in Automotive Engineering'),
(4, 'BSAT', 'Bachelor of Science in Automotive Technology'),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management'),
(4, 'BSTM', 'Bachelor of Science in Tourism Management'),
(5, 'BAEL', 'Bachelor of Arts in English Language'),
(5, 'BACD', 'Bachelor of Arts in Community Development'),
(5, 'BSBIO-BIOTECH', 'Bachelor of Science in Biology - Biotechnology'),
(5, 'BSMATH', 'Bachelor of Science in Mathematics'),
(5, 'BSMET', 'Bachelor of Science in Meteorology'),
(5, 'BHS', 'Bachelor in Human Services');


INSERT INTO users(fullname, login_id, email, password, role, account_status)
VALUES
('Admin Registrar', 'REG-0001', 'registrar@gmail.com', '$2y$12$V.q/EMXOjVP.4qHjen821um5uXrrUbEM7yUBxZJkeh82640XjH/bS', 'registrar', 'Confirmed'),
('Juan Dela Cruz', '2026-0001-A', 'student@gmail.com', '$2y$12$cAZFHh5twL3Y/7lc0mDWs.Bt7lZV8r2oapav6zoj0NNLGCZ7LGHYy', 'student', 'Confirmed'),
('Professor Santos', 'PROF-0001', 'professor@gmail.com', '$2y$12$wYy7Bq3htDtbGX5p76wQ6uWKv32j3omiyb4WKZwRU3bUHLUaEVVuC', 'professor', 'Confirmed');

INSERT INTO students(user_id, student_id, department_id, program, year_level, semester_level, enrollment_date)
VALUES (2, '2026-0001-A', 1, 'BSCS', 1, 1, CURDATE());

INSERT INTO professors(user_id, employee_id, specialization, department_id)
VALUES (3, 'PROF-0001', 'Computer Science', 1);
