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
$reason = sanitize($_POST['reason'] ?? '');

if (!$memo_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid memo ID']);
    exit;
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
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
        echo json_encode(['success' => false, 'message' => 'Memo is already completed and cannot be rejected']);
        exit;
    }

    if ($memo['status'] === 'rejected') {
        echo json_encode(['success' => false, 'message' => 'Memo is already rejected']);
        exit;
    }
    
    // Update memo status
    $db->query(
        "UPDATE memos SET status = 'rejected', rejection_reason = ?, rejected_by = ?, rejected_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$reason, $admin_id, $memo_id]
    );
    
    echo json_encode(['success' => true, 'message' => 'Memo has been rejected successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
