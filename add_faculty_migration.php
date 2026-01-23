<?php
require_once 'config/config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if faculty column exists in admin_users
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'faculty'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN faculty VARCHAR(100) AFTER department");
        echo "Added faculty column to admin_users\n";
    } else {
        echo "Faculty column already exists in admin_users\n";
    }

    // Check if faculty column exists in registration_draft
    $stmt = $pdo->query("SHOW COLUMNS FROM registration_draft LIKE 'faculty'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE registration_draft ADD COLUMN faculty VARCHAR(100) AFTER department");
        echo "Added faculty column to registration_draft\n";
    } else {
        echo "Faculty column already exists in registration_draft\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>