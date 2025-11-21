<?php
$pageTitle = 'Received Memos';
require_once 'includes/head.php';

// Check if user is admin
if ($userRole !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

try {
    $db = Database::getInstance();
    
    // Get all memos received by this admin
    $memos = $db->fetchAll(
        "SELECT m.*, u.firstname, u.lastname 
         FROM memos m
         JOIN memo_recipients mr ON m.id = mr.memo_id
         JOIN users u ON m.sender_id = u.id
         WHERE mr.admin_id = ?
         ORDER BY m.created_at DESC",
        [$_SESSION['admin_id']]
    );
    
    // Mark memo as read when viewed
    if (isset($_GET['memo_id'])) {
        $memo_id = intval($_GET['memo_id']);
        $db->query(
            "UPDATE memo_recipients SET read_at = CURRENT_TIMESTAMP WHERE memo_id = ? AND admin_id = ? AND read_at IS NULL",
            [$memo_id, $_SESSION['admin_id']]
        );
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $memos = [];
}
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Received Memos</h2>
        <p class="text-gray-600 dark:text-gray-300">View all memos sent to you</p>
    </div>

    <?php if (empty($memos)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-inbox text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No memos received yet</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4">
            <?php foreach ($memos as $memo): ?>
                <div class="card hover:shadow-lg transition">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($memo['title']); ?></h3>
                                <?php if (!$memo['read_at']): ?>
                                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded-full">New</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                From: <strong><?php echo htmlspecialchars($memo['firstname'] . ' ' . $memo['lastname']); ?></strong>
                            </p>
                            <?php if ($memo['description']): ?>
                                <p class="text-gray-700 dark:text-gray-300 mb-2"><?php echo htmlspecialchars(substr($memo['description'], 0, 100)) . (strlen($memo['description']) > 100 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-calendar mr-1"></i><?php echo date('M j, Y \a\t g:i A', strtotime($memo['created_at'])); ?>
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 w-full md:w-auto">
                            <a href="view_memo.php?id=<?php echo $memo['id']; ?>" class="btn-primary text-center text-sm">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            <a href="<?php echo htmlspecialchars($memo['file_path']); ?>" download class="btn-secondary text-center text-sm">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                            <span class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                <?php 
                                $type_map = [
                                    'image/jpeg' => 'JPG',
                                    'image/png' => 'PNG',
                                    'application/pdf' => 'PDF',
                                    'application/msword' => 'DOC',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX'
                                ];
                                echo $type_map[$memo['file_type']] ?? 'File';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
