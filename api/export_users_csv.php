<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

// Check if user is superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Fetch all admin users with complete details
    $stmt = $db->prepare("
        SELECT 
            id, firstname, surname, othername, staff_id, official_email, position, 
            department, phone_number, date_of_birth, sex, marital_status, 
            permanent_home_address, lga_origin, type_of_employment, date_of_assumption, 
            cadre, salary_structure, gl, step, rank, bank_name, account_name, 
            account_number, pfa_name, pfa_pin, nok_fullname, nok_phone_number, 
            nok_relationship, nok_address, is_active, created_at, updated_at
        FROM admin_users 
        ORDER BY firstname ASC, surname ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create CSV
    $filename = 'admin_users_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output BOM for UTF-8
    echo "\xEF\xBB\xBF";
    
    // Create file handle
    $output = fopen('php://output', 'w');
    
    // Header row
    $headers = [
        'First Name', 'Surname', 'Other Name', 'Staff ID', 'Email', 'Position', 'Department',
        'Phone Number', 'Date of Birth', 'Sex', 'Marital Status', 'Home Address', 'LGA Origin',
        'Employment Type', 'Date of Assumption', 'Cadre', 'Salary Structure', 'GL', 'STEP', 'Rank',
        'Bank Name', 'Account Name', 'Account Number', 'PFA Name', 'PFA PIN',
        'NOK Full Name', 'NOK Phone', 'NOK Relationship', 'NOK Address', 'Status', 'Created Date', 'Updated Date'
    ];
    fputcsv($output, $headers);
    
    // Data rows
    foreach ($users as $user) {
        $row = [
            $user['firstname'],
            $user['surname'],
            $user['othername'],
            $user['staff_id'],
            $user['official_email'],
            $user['position'],
            $user['department'],
            $user['phone_number'],
            $user['date_of_birth'],
            $user['sex'],
            $user['marital_status'],
            $user['permanent_home_address'],
            $user['lga_origin'],
            $user['type_of_employment'],
            $user['date_of_assumption'],
            $user['cadre'],
            $user['salary_structure'],
            $user['gl'],
            $user['step'],
            $user['rank'],
            $user['bank_name'],
            $user['account_name'],
            $user['account_number'],
            $user['pfa_name'],
            $user['pfa_pin'],
            $user['nok_fullname'],
            $user['nok_phone_number'],
            $user['nok_relationship'],
            $user['nok_address'],
            $user['is_active'] ? 'Active' : 'Inactive',
            $user['created_at'],
            $user['updated_at']
        ];
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
