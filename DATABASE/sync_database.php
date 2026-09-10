<?php
// Database Maintenance & Student Account Sync Script
require_once __DIR__ . '/db_connection.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=========================================================\n";
echo " ONLINE LEARNING PLATFORM - RAILWAY / LOCAL DATABASE SYNC\n";
echo "=========================================================\n\n";

try {
    $pdo = getPgPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "[+] Database Driver: " . strtoupper($driver) . "\n";

    // 1. Force initialize schema
    if ($driver === 'pgsql') {
        initPgSqlSchema($pdo);
    } elseif ($driver === 'mysql') {
        initMySqlSchema($pdo);
    } else {
        initSqliteSchema($pdo);
    }
    echo "[+] Base Schema Initialized Successfully.\n";

    // 2. Ensure default student (smeain@gmail.com / 1234567890) exists and is ACTIVE
    $studentEmail = 'smeain@gmail.com';
    $studentUser = 'smeain';
    $studentPass = '1234567890';
    $hash = password_hash($studentPass, PASSWORD_BCRYPT);

    $checkStmt = $pdo->prepare("SELECT id, email, username, role FROM users WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)");
    $checkStmt->execute([$studentEmail, $studentUser]);
    $uRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$uRow) {
        $insU = $pdo->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'student', 'ACTIVE')");
        $insU->execute([$studentEmail, $studentUser, $hash]);
        $newUid = $pdo->lastInsertId();

        if ($newUid) {
            $insP = $pdo->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, gender, blood_group, phone_number) VALUES (?, 'Sadman', 'Maein', 'Sadman Maein', 'male', 'A+', '01754393923')");
            $insP->execute([$newUid]);
        }
        echo "[+] Account created: {$studentEmail} / {$studentPass} (Role: student)\n";
    } else {
        // Update password and force role = student
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'student', status = 'ACTIVE' WHERE id = ?");
        $upd->execute([$hash, $uRow['id']]);
        echo "[+] Account updated & verified: {$studentEmail} / {$studentPass} (User ID: {$uRow['id']})\n";
    }

    // 3. Ensure student_registration entry exists
    try {
        $stuRegStmt = $pdo->prepare("SELECT id FROM student_registration WHERE LOWER(email) = LOWER(?)");
        $stuRegStmt->execute([$studentEmail]);
        if (!$stuRegStmt->fetch()) {
            $insSr = $pdo->prepare("INSERT INTO student_registration (first_name, last_name, contact, gender, blood_group, user_type, email, password) VALUES ('Sadman', 'Maein', '01754393923', 'male', 'A+', 'Student', ?, ?)");
            $insSr->execute([$studentEmail, $hash]);
            echo "[+] student_registration legacy table entry created for {$studentEmail}.\n";
        }
    } catch (Throwable $eSr) {
        echo "[!] Notice: Legacy student_registration check: " . $eSr->getMessage() . "\n";
    }

    // 4. Output Summary of Registered Users
    echo "\n---------------------------------------------------------\n";
    echo "CURRENT DATABASE USER LIST:\n";
    echo "---------------------------------------------------------\n";
    $usersStmt = $pdo->query("SELECT id, email, username, role, status FROM users ORDER BY id ASC");
    if ($usersStmt) {
        while ($r = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("ID: %-4d | Email: %-30s | User: %-15s | Role: %-10s | Status: %s\n", 
                $r['id'], $r['email'], $r['username'], $r['role'], $r['status']);
        }
    }

    // 4. Ensure categories exist
    try {
        $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($catCount == 0) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $pdo->exec("
                    INSERT OR IGNORE INTO categories (id, name, slug, description) VALUES
                    (1, 'Web Development', 'web-development', 'HTML, CSS, JS, PHP, PostgreSQL'),
                    (2, 'Python & AI', 'python-ai', 'Machine Learning & Deep Learning'),
                    (3, 'Data Science', 'data-science', 'SQL Analytics, PowerBI & Tableau'),
                    (4, 'Cyber Security', 'cyber-security', 'Ethical Hacking & Defense'),
                    (5, 'Mobile App', 'mobile-app', 'Flutter & React Native');
                ");
            } else {
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
            echo "[+] Categories seeded successfully.\n";
        } else {
            echo "[+] Categories already exist ({$catCount} found).\n";
        }
    } catch (Throwable $eCat) {
        echo "[!] Category seeding notice: " . $eCat->getMessage() . "\n";
    }

    // 5. Ensure courses exist
    try {
        $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        if ($courseCount == 0) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $pdo->exec("
                    INSERT OR IGNORE INTO courses (course_code, title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, is_published) VALUES
                    ('CSE-401', 'Full Stack Modern Web Development with PHP & MySQL', 'full-stack-web-dev', 'Master HTML5, CSS3, JavaScript, PHP PDO, MySQL database design, and modern responsive glassmorphism UI frameworks.', 1, 100, 4500.00, '12 Weeks', 'Beginner', 'PUBLIC/pic/img.jpg', 1),
                    ('AI-302', 'Python Programming & Artificial Intelligence Essentials', 'python-programming-ai', 'From core syntax to Machine Learning models, Deep Neural Networks, Pandas, NumPy, and Scikit-Learn data science stack.', 2, 100, 6000.00, '10 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', 1),
                    ('DAT-205', 'Data Analytics & Business Intelligence Dashboarding', 'data-analytics-bi', 'Transform raw relational databases into interactive PowerBI & Tableau dashboards with advanced SQL data analytics.', 3, 100, 3500.00, '8 Weeks', 'Advanced', 'PUBLIC/pic/img.jpg', 1),
                    ('SEC-101', 'Cyber Security Essentials & Network Defense', 'cyber-security-essentials', 'Ethical hacking methodologies, penetration testing fundamentals, network security architecture, and vulnerability assessment.', 4, 100, 5000.00, '8 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', 1);
                ");
            } else {
                $pdo->exec("
                    INSERT INTO courses (course_code, title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, is_published) VALUES
                    ('CSE-401', 'Full Stack Modern Web Development with PHP & MySQL', 'full-stack-web-dev', 'Master HTML5, CSS3, JavaScript, PHP PDO, MySQL database design, and modern responsive glassmorphism UI frameworks.', 1, 100, 4500.00, '12 Weeks', 'Beginner', 'PUBLIC/pic/img.jpg', TRUE),
                    ('AI-302', 'Python Programming & Artificial Intelligence Essentials', 'python-programming-ai', 'From core syntax to Machine Learning models, Deep Neural Networks, Pandas, NumPy, and Scikit-Learn data science stack.', 2, 100, 6000.00, '10 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', TRUE),
                    ('DAT-205', 'Data Analytics & Business Intelligence Dashboarding', 'data-analytics-bi', 'Transform raw relational databases into interactive PowerBI & Tableau dashboards with advanced SQL data analytics.', 3, 100, 3500.00, '8 Weeks', 'Advanced', 'PUBLIC/pic/img.jpg', TRUE),
                    ('SEC-101', 'Cyber Security Essentials & Network Defense', 'cyber-security-essentials', 'Ethical hacking methodologies, penetration testing fundamentals, network security architecture, and vulnerability assessment.', 4, 100, 5000.00, '8 Weeks', 'Intermediate', 'PUBLIC/pic/img.jpg', TRUE)
                    ON CONFLICT DO NOTHING;
                ");
            }
            echo "[+] Demo courses seeded successfully (4 courses).\n";
        } else {
            echo "[+] Courses already exist ({$courseCount} found).\n";
        }
    } catch (Throwable $eCrs) {
        echo "[!] Course seeding notice: " . $eCrs->getMessage() . "\n";
    }

    // 6. Output Summary of Registered Users
    echo "\n---------------------------------------------------------\n";
    echo "CURRENT DATABASE USER LIST:\n";
    echo "---------------------------------------------------------\n";
    $usersStmt = $pdo->query("SELECT id, email, username, role, status FROM users ORDER BY id ASC");
    if ($usersStmt) {
        while ($r = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("ID: %-4d | Email: %-30s | User: %-15s | Role: %-10s | Status: %s\n", 
                $r['id'], $r['email'], $r['username'], $r['role'], $r['status']);
        }
    }

    // 7. Output Course List
    echo "\n---------------------------------------------------------\n";
    echo "AVAILABLE COURSES:\n";
    echo "---------------------------------------------------------\n";
    $coursesStmt = $pdo->query("SELECT id, course_code, title, price, duration FROM courses ORDER BY id ASC");
    if ($coursesStmt) {
        while ($c = $coursesStmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("ID: %-4d | Code: %-10s | Title: %-50s | Price: %-10s | Duration: %s\n",
                $c['id'], $c['course_code'] ?? 'N/A', $c['title'], $c['price'], $c['duration'] ?? 'N/A');
        }
    }

    echo "\n=========================================================\n";
    echo " SYNC COMPLETE! Courses & accounts are ready.\n";
    echo " Login: {$studentEmail} / {$studentPass}\n";
    echo "=========================================================\n";

} catch (Throwable $e) {
    echo "\n[!] ERROR DURING SYNC: " . $e->getMessage() . "\n";
}
?>
