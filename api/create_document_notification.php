<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = Database::getInstance();

$input = json_decode(file_get_contents('php://input'), true);
$admin_id = $input['admin_id'] ?? null;
$document_id = $input['document_id'] ?? null;
$notification_type = $input['notification_type'] ?? null;
$message = $input['message'] ?? null;

if (!$admin_id || !$document_id || !$notification_type || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$allowed_types = ['submitted', 'establishment_approved', 'registrar_approved', 'rejected'];
if (!in_array($notification_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification type']);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO document_notifications (admin_id, document_id, notification_type, message)
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$admin_id, $document_id, $notification_type, $message])) {
        echo json_encode(['success' => true, 'message' => 'Notification created']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create notification']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
