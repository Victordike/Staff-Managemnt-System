<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Release session lock
session_write_close();

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        UPDATE admin_users
        SET firstname = ?, surname = ?, othername = ?, staff_id = ?, official_email = ?,
            position = ?, department = ?, faculty = ?, salary_structure = ?, gl = ?, step = ?, rank = ?,
            phone_number = ?, state_origin = ?, lga_origin = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $data['firstname'],
        $data['surname'],
        $data['othername'] ?? '',
        $data['staff_id'],
        $data['official_email'],
        $data['position'],
        $data['department'],
        $data['faculty'] ?? '',
        $data['salary_structure'] ?? '',
        $data['gl'] ?? '',
        $data['step'] ?? '',
        $data['rank'] ?? '',
        $data['phone_number'] ?? '',
        $data['state_origin'] ?? '',
        $data['lga_origin'] ?? '',
        $data['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
