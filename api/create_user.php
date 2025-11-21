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

$data = json_decode(file_get_contents('php://input'), true);

$required = ['firstname', 'surname', 'staff_id', 'official_email', 'position', 'department', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        INSERT INTO admin_users (firstname, surname, staff_id, official_email, position, department, password, phone_number, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
    ");
    
    $stmt->execute([
        $data['firstname'],
        $data['surname'],
        $data['staff_id'],
        $data['official_email'],
        $data['position'],
        $data['department'],
        hashPassword($data['password']),
        ''
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User created successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
