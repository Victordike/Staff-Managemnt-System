<?php
require_once 'includes/db.php';

$db = Database::getInstance()->getConnection();

try {
    echo "Updating admin_roles table...<br>";
    $db->exec("ALTER TABLE admin_roles ADD COLUMN department VARCHAR(100) AFTER role_name");
    $db->exec("ALTER TABLE admin_roles ADD COLUMN faculty VARCHAR(100) AFTER department");
    echo "Done.<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
