<?php
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    echo "<h2>Setting up Holidays Table...</h2>";
    
    $db->query("CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_date DATE NOT NULL UNIQUE,
        holiday_name VARCHAR(100) NOT NULL,
        year INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "Created 'holidays' table.<br>";
    
    // Insert some default holidays for 2026 (based on current date Jan 2026)
    $holidays = [
        ['2026-01-01', 'New Year\'s Day', 2026],
        ['2026-03-30', 'Good Friday', 2026],
        ['2026-04-02', 'Easter Monday', 2026],
        ['2026-05-01', 'Worker\'s Day', 2026],
        ['2026-05-27', 'Children\'s Day', 2026],
        ['2026-06-12', 'Democracy Day', 2026],
        ['2026-10-01', 'Independence Day', 2026],
        ['2026-12-25', 'Christmas Day', 2026],
        ['2026-12-26', 'Boxing Day', 2026],
    ];
    
    foreach ($holidays as $h) {
        try {
            $db->query("INSERT IGNORE INTO holidays (holiday_date, holiday_name, year) VALUES (?, ?, ?)", $h);
            echo "Inserted holiday: {$h[1]} ({$h[0]})<br>";
        } catch (Exception $e) {
            echo "Error inserting {$h[1]}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ Holidays setup completed successfully!</h3>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
