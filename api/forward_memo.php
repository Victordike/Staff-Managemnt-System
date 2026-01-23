<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$memo_id = intval($_POST['memo_id'] ?? 0);
if (!$memo_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid memo ID']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if user is the current recipient
    $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    $memo = $db->fetchOne("SELECT * FROM memos WHERE id = ?", [$memo_id]);
    
    if (!$memo || $memo['recipient_id'] != $admin_id) {
        echo json_encode(['success' => false, 'message' => 'Access denied or memo not found']);
        exit;
    }
    
    if ($memo['current_stage'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Memo is already completed']);
        exit;
    }
    
    $result = forwardMemo($memo_id, $db);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
