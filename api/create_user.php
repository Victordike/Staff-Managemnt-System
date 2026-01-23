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

$required = ['firstname', 'surname', 'staff_id', 'official_email', 'position', 'department', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

$db = Database::getInstance()->getConnection();
$db->beginTransaction();

try {
    // Ensure staff_id exists in pre_users to satisfy foreign key
    $stmtCheck = $db->prepare("SELECT id FROM pre_users WHERE staff_id = ?");
    $stmtCheck->execute([$data['staff_id']]);
    if (!$stmtCheck->fetch()) {
        $stmtPre = $db->prepare("
            INSERT INTO pre_users (surname, firstname, othername, staff_id, salary_structure, gl, step, rank, is_registered) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
        ");
        $stmtPre->execute([
            trim($data['surname']),
            trim($data['firstname']),
            trim($data['othername'] ?? ''),
            trim($data['staff_id']),
            trim($data['salary_structure'] ?? ''),
            trim($data['gl'] ?? ''),
            trim($data['step'] ?? ''),
            trim($data['rank'] ?? '')
        ]);
    } else {
        // Update pre_users to mark as registered if it exists
        $stmtUpdatePre = $db->prepare("UPDATE pre_users SET is_registered = TRUE WHERE staff_id = ?");
        $stmtUpdatePre->execute([$data['staff_id']]);
    }

    $stmt = $db->prepare("
        INSERT INTO admin_users (
            firstname, surname, othername, staff_id, official_email, 
            faculty, position, department, salary_structure, gl, step, rank, 
            password, phone_number, state_origin, lga_origin,
            date_of_birth, sex, marital_status, permanent_home_address,
            bank_name, account_name, account_number, pfa_name, pfa_pin,
            nok_fullname, nok_phone_number, nok_relationship, nok_address,
            is_active
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1990-01-01', 'Other', 'Single', 'N/A', 'N/A', 'N/A', '0000000000', 'N/A', '0000', 'N/A', '0000', 'N/A', 'N/A', TRUE)
    ");
    
    $stmt->execute([
        $data['firstname'],
        $data['surname'],
        $data['othername'] ?? '',
        $data['staff_id'],
        $data['official_email'],
        $data['faculty'] ?? '',
        $data['position'],
        $data['department'],
        $data['salary_structure'] ?? '',
        $data['gl'] ?? '',
        $data['step'] ?? '',
        $data['rank'] ?? '',
        hashPassword($data['password']),
        $data['phone_number'] ?? '',
        $data['state_origin'] ?? '',
        $data['lga_origin'] ?? ''
    ]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'User created successfully']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
