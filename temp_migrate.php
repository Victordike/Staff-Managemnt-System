<?php
require_once 'config/config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'state_origin'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN state_origin VARCHAR(100) AFTER permanent_home_address");
        echo "Added state_origin to admin_users\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM registration_draft LIKE 'state_origin'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE registration_draft ADD COLUMN state_origin VARCHAR(100) AFTER permanent_home_address");
        echo "Added state_origin to registration_draft\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
