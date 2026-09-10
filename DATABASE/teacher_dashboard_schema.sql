-- Teacher Dashboard & Financial Integration Schema (MySQL)
-- Database: online_education

USE online_education;

-- 1. Courses Table with Taka (৳ BDT) Pricing Support
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    course_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Active', 'Upcoming', 'Archived') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Assignments Table with Foreign Key
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    deadline DATETIME NOT NULL,
    total_marks INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Initial Data Seeding
INSERT IGNORE INTO courses (id, teacher_id, course_name, course_code, description, fee_amount, status) VALUES
(101, 1, 'Full Stack Web Development with PHP & MySQL', 'CSE-401', 'Master modern web app development, MVC architecture, RESTful API design, and database optimization.', 15000.00, 'Active'),
(102, 1, 'Advanced Python & Artificial Intelligence', 'AI-502', 'Deep dive into Machine Learning, Neural Networks, Computer Vision, and Data Analytics.', 18500.00, 'Active'),
(103, 1, 'Cyber Security & Network Defense', 'SEC-303', 'Ethical hacking fundamentals, penetration testing, network auditing, and secure coding practices.', 12000.00, 'Upcoming');

INSERT IGNORE INTO assignments (id, course_id, teacher_id, title, description, deadline, total_marks) VALUES
(201, 101, 1, 'PHP MVC Architecture Implementation', 'Build a mini-framework in raw PHP supporting Routing, Controllers, and PDO Database abstraction.', '2026-10-15 23:59:00', 100),
(202, 101, 1, 'Database Indexing & Query Tuning', 'Optimize high-concurrency MySQL schemas using multi-column indexes and explain plans.', '2026-10-20 23:59:00', 50),
(203, 102, 1, 'Supervised Machine Learning Model', 'Train a linear regression and random forest classification model using Scikit-Learn.', '2026-11-01 23:59:00', 100);
