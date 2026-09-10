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

    echo "\n=========================================================\n";
    echo " SYNC COMPLETE! YOU CAN NOW LOGIN WITH: {$studentEmail} / {$studentPass}\n";
    echo "=========================================================\n";

} catch (Throwable $e) {
    echo "\n[!] ERROR DURING SYNC: " . $e->getMessage() . "\n";
}
?>
