<?php
/**
 * Cron script to mark approved leaves as completed once they end.
 * This should be run daily via a task scheduler or manually triggered.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$today = date('Y-m-d');

try {
    echo "Starting leave completion process for $today...\n";

    // Find all approved leaves that have ended
    $endedLeaves = $db->fetchAll("
        SELECT la.*, lt.name as leave_type 
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        WHERE la.status = 'approved' AND la.end_date < ?
    ", [$today]);

    $count = 0;
    foreach ($endedLeaves as $leave) {
        // Update status to completed
        $db->query("UPDATE leave_applications SET status = 'completed' WHERE id = ?", [$leave['id']]);
        
        // Notify the user
        $message = "Your {$leave['leave_type']} that started on {$leave['start_date']} and ended on {$leave['end_date']} is now marked as completed. You can now apply for another leave if needed.";
        addLeaveNotification($leave['admin_id'], $leave['id'], 'completed', $message);
        
        echo "Marked leave #{$leave['id']} for Admin ID {$leave['admin_id']} as completed.\n";
        $count++;
    }

    echo "Successfully completed $count leave applications.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
