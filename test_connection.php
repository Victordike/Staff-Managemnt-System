<?php
echo "=== FPOG Database Connection Diagnostic Tool ===\n\n";

// Test 1: Check if MySQL extension is loaded
echo "1. Checking PHP MySQL extension...\n";
if (extension_loaded('pdo_mysql')) {
    echo "   ✓ PDO MySQL extension is loaded\n\n";
} else {
    echo "   ✗ PDO MySQL extension is NOT loaded\n\n";
}

// Test 2: Try different connection methods
$hosts = ['127.0.0.1', 'localhost'];
$ports = [3306, 3307, 3308];

echo "2. Testing MySQL connection on different hosts/ports...\n";
foreach ($hosts as $host) {
    foreach ($ports as $port) {
        try {
            $conn = new PDO(
                "mysql:host=$host;port=$port;charset=utf8mb4",
                'root',
                '',
                [PDO::ATTR_TIMEOUT => 3]
            );
            echo "   ✓ SUCCESS: Connected to mysql://$host:$port\n";
            
            // If we connected, try to select the database
            try {
                $stmt = $conn->query("SELECT DATABASE()");
                $db = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "      Current database: " . ($db[0] ?? 'None selected') . "\n";
                
                // Check if fpog_sms database exists
                $stmt = $conn->query("SHOW DATABASES LIKE 'fpog_sms'");
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($result) > 0) {
                    echo "      ✓ Database 'fpog_sms' EXISTS\n";
                } else {
                    echo "      ✗ Database 'fpog_sms' NOT FOUND\n";
                }
            } catch (Exception $e) {
                echo "      Error: " . $e->getMessage() . "\n";
            }
            
            $conn = null;
        } catch (PDOException $e) {
            echo "   ✗ Failed: mysql://$host:$port - " . $e->getMessage() . "\n";
        }
    }
}

echo "\n3. Checking XAMPP MySQL status...\n";
echo "   Please make sure:\n";
echo "   - XAMPP Control Panel is open\n";
echo "   - MySQL shows status: RUNNING (green indicator)\n";
echo "   - Port: 3306\n";
echo "   - If MySQL won't start, try:\n";
echo "     a) Click 'Stop' first\n";
echo "     b) Wait 5 seconds\n";
echo "     c) Click 'Start' again\n";
echo "     d) Check if port 3306 is already in use\n\n";

echo "=== Configuration ===\n";
require_once 'config/config.php';
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
?>
