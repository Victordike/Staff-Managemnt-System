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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['admin_id']) || !isset($data['roles'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'admin_id and roles required']);
    exit;
}

$adminId = (int)$data['admin_id'];
$newRoles = $data['roles']; // Array of objects: {role_name, department, faculty}
$superAdminId = $_SESSION['user_id'] ?? null;

// Release session lock
session_write_close();

if (!$superAdminId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Super admin ID not found in session']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Simplest way: Mark all current roles as removed
    $stmt = $db->prepare("
        UPDATE admin_roles 
        SET removed_at = NOW() 
        WHERE admin_id = ? AND removed_at IS NULL
    ");
    $stmt->execute([$adminId]);
    
    // Add the new roles
    foreach ($newRoles as $roleData) {
        $roleName = $roleData['role_name'];
        $dept = $roleData['department'] ?? null;
        $fac = $roleData['faculty'] ?? null;
        
        // Check if role exists but is removed
        $checkStmt = $db->prepare("
            SELECT id FROM admin_roles 
            WHERE admin_id = ? AND role_name = ? AND removed_at IS NOT NULL
        ");
        $checkStmt->execute([$adminId, $roleName]);
        $existingRole = $checkStmt->fetch();
        
        if ($existingRole) {
            // Restore removed role with updated info
            $updateStmt = $db->prepare("
                UPDATE admin_roles 
                SET removed_at = NULL, department = ?, faculty = ?, assigned_at = NOW(), assigned_by = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$dept, $fac, $superAdminId, $existingRole['id']]);
        } else {
            // Insert new role
            $insertStmt = $db->prepare("
                INSERT INTO admin_roles (admin_id, role_name, department, faculty, assigned_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$adminId, $roleName, $dept, $fac, $superAdminId]);
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Roles updated successfully']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
