<?php
// Central Database Connection (PostgreSQL for Railway + Local SQLite Fallback for Dev)

// 1. Fix XAMPP / Windows / Linux Session Save Path Warning
$savePath = session_save_path();
if (empty($savePath) || !is_dir($savePath) || !is_writable($savePath)) {
    $tempDir = sys_get_temp_dir();
    if (is_dir($tempDir) && is_writable($tempDir)) {
        @session_save_path($tempDir);
    }
}

if (!function_exists('getPgPDO')) {
    function getPgPDO() {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        // Check Railway DATABASE_URL first
        $dbUrl = getenv('DATABASE_URL');
        if ($dbUrl) {
            $dbopts = parse_url($dbUrl);
            $host = $dbopts['host'] ?? 'localhost';
            $port = $dbopts['port'] ?? 5432;
            $user = $dbopts['user'] ?? 'postgres';
            $pass = $dbopts['pass'] ?? '';
            $dbname = ltrim($dbopts['path'] ?? '/postgres', '/');

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            try {
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
                // Auto-initialize PostgreSQL schema & default admin on Railway
                initPgSqlSchema($pdo);

                return $pdo;
            } catch (PDOException $e) {
                error_log("Railway PostgreSQL Connection Error: " . $e->getMessage());
            }
        }

        // Try local PostgreSQL with environment or default credentials
        $host = getenv('PGHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
        $port = getenv('PGPORT') ?: (getenv('DB_PORT') ?: '5432');
        $dbname = getenv('PGDATABASE') ?: (getenv('DB_NAME') ?: 'Online_Education');
        $user = getenv('PGUSER') ?: (getenv('DB_USER') ?: 'postgres');
        $pass = getenv('PGPASSWORD') ?: (getenv('DB_PASS') ?: 'postgres');

        try {
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            initPgSqlSchema($pdo);
            return $pdo;
        } catch (PDOException $e) {
            // Try MySQL (XAMPP / local MySQL)
            try {
                $myHost = defined('DB_HOST') ? DB_HOST : 'localhost';
                $myDb   = defined('DB_NAME') ? DB_NAME : 'online_education';
                $myUser = defined('DB_USER') ? DB_USER : 'root';
                $myPass = defined('DB_PASS') ? DB_PASS : '';
                $pdo = new PDO("mysql:host=$myHost;dbname=$myDb;charset=utf8mb4", $myUser, $myPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                initMySqlSchema($pdo);
                return $pdo;
            } catch (PDOException $eMysql) {
                // Persistent Local Fallback Database (SQLite) for offline local development
                $sqliteFile = __DIR__ . '/database.sqlite';
                $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                initSqliteSchema($pdo);
                return $pdo;
            }
        }
    }
}

if (!function_exists('initPgSqlSchema')) {
    function initPgSqlSchema($pdo) {
        static $initialized = false;
        if ($initialized) return;
        $initialized = true;

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT 'student',
                    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS profiles (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    first_name VARCHAR(100),
                    last_name VARCHAR(100),
                    full_name VARCHAR(200),
                    age INT,
                    date_of_birth VARCHAR(50),
                    gender VARCHAR(50),
                    blood_group VARCHAR(20),
                    phone_number VARCHAR(50),
                    address TEXT,
                    qualifications TEXT,
                    teacher_user_id VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS categories (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    icon VARCHAR(100) DEFAULT 'fas fa-book'
                );

                CREATE TABLE IF NOT EXISTS courses (
                    id SERIAL PRIMARY KEY,
                    course_code VARCHAR(100) UNIQUE,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE,
                    description TEXT,
                    category_id INT,
                    instructor_id INT,
                    price NUMERIC(10,2) DEFAULT 0.00,
                    duration VARCHAR(100),
                    level VARCHAR(50) DEFAULT 'Beginner',
                    thumbnail VARCHAR(255),
                    tutorials_count INT DEFAULT 0,
                    is_published BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS lessons (
                    id SERIAL PRIMARY KEY,
                    course_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    content TEXT,
                    video_url VARCHAR(255),
                    duration VARCHAR(100),
                    sort_order INT DEFAULT 1
                );

                CREATE TABLE IF NOT EXISTS enrollments (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    status VARCHAR(50) DEFAULT 'ENROLLED',
                    progress_percent INT DEFAULT 0,
                    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(user_id, course_id)
                );

                CREATE TABLE IF NOT EXISTS payments (
                    id SERIAL PRIMARY KEY,
                    transaction_id VARCHAR(100) NOT NULL UNIQUE,
                    user_id INT,
                    email VARCHAR(255) NOT NULL,
                    course_name VARCHAR(255) NOT NULL,
                    course_id INT,
                    amount VARCHAR(50) NOT NULL,
                    currency VARCHAR(10) DEFAULT 'BDT',
                    payment_method VARCHAR(50) NOT NULL,
                    status VARCHAR(50) DEFAULT 'PENDING',
                    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS admin_registration (
                    id SERIAL PRIMARY KEY,
                    admin_name VARCHAR(200),
                    age INT,
                    date_of_birth VARCHAR(50),
                    blood_group VARCHAR(20),
                    phone_number VARCHAR(50),
                    address TEXT,
                    email VARCHAR(255) UNIQUE,
                    username VARCHAR(255) UNIQUE,
                    password VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS teachers (
                    id SERIAL PRIMARY KEY,
                    teacher_name VARCHAR(100) NOT NULL,
                    age INT,
                    date_of_birth VARCHAR(50),
                    blood_group VARCHAR(20),
                    phone_number VARCHAR(20),
                    address TEXT,
                    email VARCHAR(100) UNIQUE,
                    qualifications TEXT,
                    user_id VARCHAR(50),
                    username VARCHAR(50) UNIQUE,
                    password VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS student_registration (
                    id SERIAL PRIMARY KEY,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    contact VARCHAR(20),
                    gender VARCHAR(20),
                    blood_group VARCHAR(20),
                    user_type VARCHAR(50) DEFAULT 'Student',
                    email VARCHAR(100) UNIQUE,
                    password VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS assignments (
                    id SERIAL PRIMARY KEY,
                    course_id INT NOT NULL,
                    teacher_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    total_marks INT DEFAULT 100,
                    due_date VARCHAR(50),
                    status VARCHAR(50) DEFAULT 'ACTIVE',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS admin_audit_logs (
                    id SERIAL PRIMARY KEY,
                    admin_id INT,
                    action VARCHAR(100),
                    entity_type VARCHAR(100),
                    entity_id INT,
                    details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
            ");

            // Seed default Admin if users table has no admin
            $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($checkAdmin == 0) {
                $pdo->exec("
                    INSERT INTO users (id, email, username, password_hash, role, status) VALUES
                    (100, 'admin@platform.com', 'admin', '$2y$10$8PvhEPs3da.FRVrz/Zchn.0ftaxwe8G1IQxjVNaMy7h6IEBX.dDHG', 'admin', 'ACTIVE')
                    ON CONFLICT (id) DO NOTHING;

                    INSERT INTO profiles (user_id, first_name, last_name, full_name) VALUES
                    (100, 'System', 'Admin', 'System Administrator')
                    ON CONFLICT (user_id) DO NOTHING;
                ");
            }

            // Seed default categories if empty
            $checkCat = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            if ($checkCat == 0) {
                $pdo->exec("
                    INSERT INTO categories (id, name, slug, description) VALUES
                    (1, 'Web Development', 'web-development', 'HTML, CSS, JS, PHP, PostgreSQL'),
                    (2, 'Python & AI', 'python-ai', 'Machine Learning & Deep Learning'),
                    (3, 'Data Science', 'data-science', 'SQL Analytics, PowerBI & Tableau'),
                    (4, 'Cyber Security', 'cyber-security', 'Ethical Hacking & Defense'),
                    (5, 'Mobile App', 'mobile-app', 'Flutter & React Native')
                    ON CONFLICT (id) DO NOTHING;
                ");
            }

            // Seed sample courses if empty
            $checkCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            if ($checkCourses == 0) {
                $pdo->exec("
                    INSERT INTO courses (title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, is_published) VALUES
                    ('Full Stack Modern Web Development', 'full-stack-web-dev', 'Master HTML5, CSS3, JavaScript, PHP, PDO, PostgreSQL, and modern responsive glassmorphism UI frameworks.', 1, 100, 1500.00, '12 Weeks', 'Beginner', 'PUBLIC/pic/img.jpg', TRUE),
                    ('Python Programming & AI Essentials', 'python-programming-ai', 'From core syntax to Machine Learning models, Neural Networks, Pandas, NumPy, and Scikit-Learn.', 2, 100, 0.00, '8 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', TRUE),
                    ('Data Analytics & Business Intelligence', 'data-analytics-bi', 'Transform raw relational databases into interactive PowerBI & Tableau dashboards with advanced SQL analytics.', 3, 100, 2000.00, '10 Weeks', 'Advanced', 'PUBLIC/pic/img.jpg', TRUE)
                    ON CONFLICT DO NOTHING;
                ");
            }
        } catch (Exception $e) {
            error_log("PostgreSQL Schema Auto-Init Warning: " . $e->getMessage());
        }
    }
}

if (!function_exists('initMySqlSchema')) {
    function initMySqlSchema($pdo) {
        static $initialized = false;
        if ($initialized) return;
        $initialized = true;

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT 'student',
                    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS profiles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    first_name VARCHAR(100),
                    last_name VARCHAR(100),
                    full_name VARCHAR(200),
                    age INT,
                    date_of_birth VARCHAR(50),
                    gender VARCHAR(50),
                    blood_group VARCHAR(20),
                    phone_number VARCHAR(50),
                    address TEXT,
                    qualifications TEXT,
                    teacher_user_id VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    icon VARCHAR(100) DEFAULT 'fas fa-book'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS courses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    course_code VARCHAR(100) UNIQUE,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE,
                    description TEXT,
                    category_id INT,
                    instructor_id INT,
                    price DECIMAL(10,2) DEFAULT 0.00,
                    duration VARCHAR(100),
                    level VARCHAR(50) DEFAULT 'Beginner',
                    thumbnail VARCHAR(255),
                    tutorials_count INT DEFAULT 0,
                    is_published TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS enrollments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    status VARCHAR(50) DEFAULT 'ENROLLED',
                    progress_percent INT DEFAULT 0,
                    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_course (user_id, course_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS teachers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    teacher_name VARCHAR(100) NOT NULL,
                    age INT,
                    date_of_birth VARCHAR(50),
                    blood_group VARCHAR(20),
                    phone_number VARCHAR(20),
                    address TEXT,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    qualifications TEXT,
                    user_id VARCHAR(50),
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS student_registration (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    contact VARCHAR(20),
                    gender VARCHAR(20),
                    blood_group VARCHAR(20),
                    user_type VARCHAR(50) DEFAULT 'Student',
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS assignments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    course_id INT NOT NULL,
                    teacher_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    total_marks INT DEFAULT 100,
                    due_date VARCHAR(50),
                    status VARCHAR(50) DEFAULT 'ACTIVE',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS admin_audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT,
                    action VARCHAR(100),
                    entity_type VARCHAR(100),
                    entity_id INT,
                    details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Auto-sync teachers table into users & profiles
            try {
                $teachersStmt = $pdo->query("SELECT * FROM teachers");
                if ($teachersStmt) {
                    while ($t = $teachersStmt->fetch(PDO::FETCH_ASSOC)) {
                        $email = trim($t['email'] ?? '');
                        $username = trim($t['username'] ?? '');
                        if (empty($email) || empty($username)) continue;

                        $uCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                        $uCheck->execute([$email, $username]);
                        $uRow = $uCheck->fetch(PDO::FETCH_ASSOC);

                        if (!$uRow) {
                            $pw = !empty($t['password']) ? $t['password'] : password_hash('teacher123', PASSWORD_BCRYPT);
                            $insU = $pdo->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'teacher', 'ACTIVE')");
                            $insU->execute([$email, $username, $pw]);
                            $newUid = $pdo->lastInsertId();
                        } else {
                            $newUid = $uRow['id'];
                        }

                        $pCheck = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
                        $pCheck->execute([$newUid]);
                        if (!$pCheck->fetchColumn()) {
                            $parts = explode(' ', trim($t['teacher_name'] ?? 'Teacher'), 2);
                            $insP = $pdo->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, age, date_of_birth, blood_group, phone_number, address, qualifications, teacher_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $insP->execute([
                                $newUid,
                                $parts[0],
                                $parts[1] ?? '',
                                trim($t['teacher_name'] ?? 'Teacher'),
                                $t['age'] ?? null,
                                $t['date_of_birth'] ?? null,
                                $t['blood_group'] ?? null,
                                $t['phone_number'] ?? null,
                                $t['address'] ?? null,
                                $t['qualifications'] ?? null,
                                $t['user_id'] ?? null
                            ]);
                        }
                    }
                }
            } catch (Throwable $exT) {}

            // Auto-sync student_registration table into users & profiles
            try {
                $studentsStmt = $pdo->query("SELECT * FROM student_registration");
                if ($studentsStmt) {
                    while ($s = $studentsStmt->fetch(PDO::FETCH_ASSOC)) {
                        $email = trim($s['email'] ?? '');
                        if (empty($email)) continue;
                        $username = strstr($email, '@', true) ?: $email;

                        $uCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                        $uCheck->execute([$email, $username]);
                        $uRow = $uCheck->fetch(PDO::FETCH_ASSOC);

                        if (!$uRow) {
                            $pw = !empty($s['password']) ? $s['password'] : password_hash('pass1234', PASSWORD_BCRYPT);
                            $insU = $pdo->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'student', 'ACTIVE')");
                            $insU->execute([$email, $username, $pw]);
                            $newUid = $pdo->lastInsertId();
                        } else {
                            $newUid = $uRow['id'];
                        }

                        $pCheck = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
                        $pCheck->execute([$newUid]);
                        if (!$pCheck->fetchColumn()) {
                            $insP = $pdo->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, gender, blood_group, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $insP->execute([
                                $newUid,
                                $s['first_name'] ?? '',
                                $s['last_name'] ?? '',
                                trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')),
                                $s['gender'] ?? null,
                                $s['blood_group'] ?? null,
                                $s['contact'] ?? null
                            ]);
                        }
                    }
                }
            } catch (Throwable $exS) {}

            $checkCat = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            if ($checkCat == 0) {
                $pdo->exec("
                    INSERT IGNORE INTO categories (id, name, slug, description) VALUES
                    (1, 'Web Development', 'web-development', 'HTML, CSS, JS, PHP, MySQL'),
                    (2, 'Python & AI', 'python-ai', 'Machine Learning & Deep Learning'),
                    (3, 'Data Science', 'data-science', 'SQL Analytics, PowerBI & Tableau'),
                    (4, 'Cyber Security', 'cyber-security', 'Ethical Hacking & Defense'),
                    (5, 'Mobile App', 'mobile-app', 'Flutter & React Native');
                ");
            }

            $checkCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            if ($checkCourses == 0) {
                $pdo->exec("
                    INSERT IGNORE INTO courses (title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, is_published) VALUES
                    ('Full Stack Modern Web Development', 'full-stack-web-dev', 'Master HTML5, CSS3, JavaScript, PHP, PDO, PostgreSQL, and modern responsive glassmorphism UI frameworks.', 1, 100, 1500.00, '12 Weeks', 'Beginner', 'PUBLIC/pic/img.jpg', 1),
                    ('Python Programming & AI Essentials', 'python-programming-ai', 'From core syntax to Machine Learning models, Neural Networks, Pandas, NumPy, and Scikit-Learn.', 2, 100, 0.00, '8 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', 1),
                    ('Data Analytics & Business Intelligence', 'data-analytics-bi', 'Transform raw relational databases into interactive PowerBI & Tableau dashboards with advanced SQL analytics.', 3, 100, 2000.00, '10 Weeks', 'Advanced', 'PUBLIC/pic/img.jpg', 1);
                ");
            }
        } catch (Exception $e) {
            error_log("MySQL Schema Auto-Init Warning: " . $e->getMessage());
        }
    }
}

if (!function_exists('initSqliteSchema')) {
    function initSqliteSchema($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'student',
                status TEXT NOT NULL DEFAULT 'ACTIVE',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                first_name TEXT,
                last_name TEXT,
                full_name TEXT,
                age INTEGER,
                date_of_birth TEXT,
                gender TEXT,
                blood_group TEXT,
                phone_number TEXT,
                address TEXT,
                qualifications TEXT,
                teacher_user_id TEXT UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS teachers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                teacher_name TEXT NOT NULL,
                age INTEGER,
                date_of_birth TEXT,
                blood_group TEXT,
                phone_number TEXT,
                address TEXT,
                email TEXT UNIQUE,
                qualifications TEXT,
                user_id TEXT,
                username TEXT UNIQUE,
                password TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS student_registration (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                contact TEXT,
                gender TEXT,
                blood_group TEXT,
                user_type TEXT DEFAULT 'Student',
                email TEXT UNIQUE,
                password TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                total_marks INTEGER DEFAULT 100,
                due_date TEXT,
                status TEXT DEFAULT 'ACTIVE',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                icon TEXT DEFAULT 'fas fa-book'
            );

            CREATE TABLE IF NOT EXISTS courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_code TEXT UNIQUE,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                category_id INTEGER,
                instructor_id INTEGER,
                price REAL NOT NULL DEFAULT 0.00,
                duration TEXT,
                level TEXT DEFAULT 'Beginner',
                thumbnail TEXT,
                tutorials_count INTEGER DEFAULT 0,
                is_published INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS lessons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT,
                video_url TEXT,
                duration TEXT,
                sort_order INTEGER DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                status TEXT DEFAULT 'ENROLLED',
                progress_percent INTEGER DEFAULT 0,
                enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, course_id)
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                email TEXT NOT NULL,
                course_name TEXT NOT NULL,
                course_id INTEGER,
                amount TEXT NOT NULL,
                currency TEXT DEFAULT 'BDT',
                payment_method TEXT NOT NULL,
                status TEXT DEFAULT 'PENDING',
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admin_registration (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_name TEXT,
                age INTEGER,
                date_of_birth TEXT,
                blood_group TEXT,
                phone_number TEXT,
                address TEXT,
                email TEXT UNIQUE,
                username TEXT UNIQUE,
                password TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER,
                action TEXT,
                entity_type TEXT,
                entity_id INTEGER,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- Default Admin User (admin@platform.com / admin / Admin2026!)
            INSERT OR IGNORE INTO users (id, email, username, password_hash, role, status) VALUES
            (100, 'admin@platform.com', 'admin', '$2y$10$8PvhEPs3da.FRVrz/Zchn.0ftaxwe8G1IQxjVNaMy7h6IEBX.dDHG', 'admin', 'ACTIVE');

            INSERT OR IGNORE INTO profiles (user_id, first_name, last_name, full_name) VALUES
            (100, 'System', 'Admin', 'System Administrator');
        ");
    }
}

if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        return getPgPDO();
    }
}

try {
    $conn = getPgPDO();
    $pdo = $conn;
    $db = $conn;
} catch (Exception $e) {
    $conn = null;
    $pdo = null;
    $db = null;
}
?>