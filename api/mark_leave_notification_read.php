<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adminId = $_SESSION['admin_id'];
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'] ?? null;

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

try {
    $db = Database::getInstance();
    if ($notificationId === 'all') {
        $db->query(
            "UPDATE leave_notifications SET is_read = 1, read_at = NOW() WHERE admin_id = ? AND is_read = 0",
            [$adminId]
        );
    } else {
        $db->query(
            "UPDATE leave_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND admin_id = ?",
            [$notificationId, $adminId]
        );
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>