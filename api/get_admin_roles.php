<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

// Check if user is superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['admin_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'admin_id required']);
    exit;
}

$adminId = (int)$_GET['admin_id'];
$db = Database::getInstance()->getConnection();

try {
    // Get current roles for the admin (where removed_at is NULL)
    $stmt = $db->prepare("
        SELECT role_name FROM admin_roles 
        WHERE admin_id = ? AND removed_at IS NULL
        ORDER BY role_name ASC
    ");
    $stmt->execute([$adminId]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'roles' => $roles]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
