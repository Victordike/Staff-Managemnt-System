<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    
    echo "Adding columns to admin_roles... ";
    $db->query("ALTER TABLE admin_roles ADD COLUMN IF NOT EXISTS department VARCHAR(100) AFTER role_name");
    $db->query("ALTER TABLE admin_roles ADD COLUMN IF NOT EXISTS faculty VARCHAR(100) AFTER department");
    echo "Done.<br>";
    
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
