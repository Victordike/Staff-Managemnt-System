<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Release session lock
session_write_close();

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        SELECT id, firstname, surname, othername, staff_id, official_email, position, department, faculty,
               salary_structure, gl, step, rank, phone_number, state_origin, lga_origin, is_active, profile_picture, created_at
        FROM admin_users
        ORDER BY firstname ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
