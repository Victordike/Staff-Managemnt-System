<?php
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // 1. Add gender column to leave_types if it doesn't exist
    $db->query("ALTER TABLE leave_types ADD COLUMN gender ENUM('Any', 'Male', 'Female') DEFAULT 'Any' AFTER default_days");
    echo "Added gender column to leave_types.<br>";
} catch (Exception $e) {
    echo "Gender column might already exist.<br>";
}

try {
    // 2. Update Maternity Leave
    $db->query("UPDATE leave_types SET default_days = 120, gender = 'Female' WHERE name = 'Maternity Leave'");
    echo "Updated Maternity Leave.<br>";
    
    // 3. Insert or Update Paternity Leave
    $paternity = $db->fetchOne("SELECT id FROM leave_types WHERE name = 'Paternity Leave'");
    if ($paternity) {
        $db->query("UPDATE leave_types SET default_days = 15, gender = 'Male' WHERE id = ?", [$paternity['id']]);
        echo "Updated Paternity Leave.<br>";
    } else {
        $db->query("INSERT INTO leave_types (name, description, default_days, gender) VALUES ('Paternity Leave', 'Leave for male staff for childbirth and childcare', 15, 'Male')");
        echo "Inserted Paternity Leave.<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "Migration completed.";
?>
