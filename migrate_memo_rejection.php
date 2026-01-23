<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    
    // Add rejection_reason column if it doesn't exist
    $db->query("ALTER TABLE memos ADD COLUMN IF NOT EXISTS rejection_reason TEXT AFTER is_approved");
    $db->query("ALTER TABLE memos ADD COLUMN IF NOT EXISTS rejected_by INT AFTER rejection_reason");
    $db->query("ALTER TABLE memos ADD COLUMN IF NOT EXISTS rejected_at DATETIME AFTER rejected_by");
    
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
