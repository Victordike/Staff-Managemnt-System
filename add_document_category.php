<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Adding document_category column...</h2>";
    
    $sql = "ALTER TABLE document_submissions ADD COLUMN IF NOT EXISTS document_category VARCHAR(100) DEFAULT NULL AFTER file_size";
    
    $conn->exec($sql);
    echo "<p style='color: green;'><strong>✓ document_category column added successfully!</strong></p>";
    
    echo "<p><a href='upload_documents.php'>Go to Document Upload</a></p>";
    
} catch (Exception $e) {
    echo '<p style="color: red;"><strong>Error:</strong> ' . $e->getMessage() . '</p>';
    exit(1);
}
?>
