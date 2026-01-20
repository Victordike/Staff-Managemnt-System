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
$newRoles = array_map('trim', array_filter($data['roles']));
$superAdminId = $_SESSION['user_id'] ?? null;

if (!$superAdminId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Super admin ID not found in session']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get current roles
    $stmt = $db->prepare("
        SELECT role_name FROM admin_roles 
        WHERE admin_id = ? AND removed_at IS NULL
    ");
    $stmt->execute([$adminId]);
    $currentRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Roles to remove (in current but not in new)
    $rolesToRemove = array_diff($currentRoles, $newRoles);
    
    // Roles to add (in new but not in current)
    $rolesToAdd = array_diff($newRoles, $currentRoles);
    
    // Remove roles
    if (!empty($rolesToRemove)) {
        $placeholders = implode(',', array_fill(0, count($rolesToRemove), '?'));
        $removeStmt = $db->prepare("
            UPDATE admin_roles 
            SET removed_at = NOW() 
            WHERE admin_id = ? AND role_name IN ($placeholders) AND removed_at IS NULL
        ");
        $params = array_merge([$adminId], $rolesToRemove);
        $removeStmt->execute($params);
    }
    
    // Add new roles
    foreach ($rolesToAdd as $role) {
        // Check if role exists but is removed
        $checkStmt = $db->prepare("
            SELECT id FROM admin_roles 
            WHERE admin_id = ? AND role_name = ? AND removed_at IS NOT NULL
        ");
        $checkStmt->execute([$adminId, $role]);
        $existingRole = $checkStmt->fetch();
        
        if ($existingRole) {
            // Restore removed role
            $updateStmt = $db->prepare("
                UPDATE admin_roles 
                SET removed_at = NULL, assigned_at = NOW(), assigned_by = ?
                WHERE admin_id = ? AND role_name = ?
            ");
            $updateStmt->execute([$superAdminId, $adminId, $role]);
        } else {
            // Insert new role
            $insertStmt = $db->prepare("
                INSERT INTO admin_roles (admin_id, role_name, assigned_by) 
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([$adminId, $role, $superAdminId]);
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
