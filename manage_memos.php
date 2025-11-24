<?php
$pageTitle = 'Manage Memos';
require_once 'includes/head.php';

// Check if user is superadmin
if ($userRole !== 'superadmin') {
    http_response_code(403);
    die('Access denied');
}

$error = '';
$success = '';

// Handle memo deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_memo'])) {
    $memo_id = intval($_POST['memo_id']);
    try {
        $db = Database::getInstance();
        
        // Get memo to get file path
        $memo = $db->fetchOne("SELECT file_path FROM memos WHERE id = ? AND sender_id = ?", [$memo_id, $_SESSION['user_id']]);
        
        if ($memo) {
            // Delete file
            if (file_exists($memo['file_path'])) {
                unlink($memo['file_path']);
            }
            
            // Delete memo and recipients
            $db->query("DELETE FROM memo_recipients WHERE memo_id = ?", [$memo_id]);
            $db->query("DELETE FROM memos WHERE id = ?", [$memo_id]);
            
            $success = 'Memo deleted successfully';
        }
    } catch (Exception $e) {
        $error = 'Failed to delete memo';
    }
}

try {
    $db = Database::getInstance();
    
    // Get all memos sent by super admin with recipient count
    $memos = $db->fetchAll(
        "SELECT m.*, COUNT(mr.id) as recipient_count, COUNT(CASE WHEN mr.read_at IS NOT NULL THEN 1 END) as read_count
         FROM memos m
         LEFT JOIN memo_recipients mr ON m.id = mr.memo_id
         WHERE m.sender_id = ?
         GROUP BY m.id
         ORDER BY m.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    error_log($e->getMessage());
    $memos = [];
}
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Manage Memos</h2>
        <p class="text-gray-600 dark:text-gray-300">View and manage all memos you have sent</p>
    </div>

    <?php if ($error): ?>
        <div class="card mb-6 bg-red-50 dark:bg-red-900 border border-red-300 dark:border-red-700">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl mr-3"></i>
                <p class="text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="card mb-6 bg-green-50 dark:bg-green-900 border border-green-300 dark:border-green-700">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3"></i>
                <p class="text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($memos)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-envelope text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No memos sent yet</p>
            <a href="upload_memo.php" class="btn-primary mt-6 inline-block">
                <i class="fas fa-plus mr-2"></i>Create Memo
            </a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Title</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden md:table-cell">Type</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Recipients</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden lg:table-cell">Sent Date</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memos as $memo): ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 font-semibold">
                                    <?php echo htmlspecialchars($memo['title']); ?>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 hidden md:table-cell">
                                    <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded">
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
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200">
                                    <span class="text-xs">
                                        <strong><?php echo $memo['read_count']; ?></strong>/<strong><?php echo $memo['recipient_count']; ?></strong>
                                    </span>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 hidden lg:table-cell text-xs">
                                    <?php echo date('M j, Y', strtotime($memo['created_at'])); ?>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-xs space-x-2">
                                    <a href="view_memo.php?id=<?php echo $memo['id']; ?>" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo htmlspecialchars($memo['file_path']); ?>" download class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button type="button" onclick="deleteMemoConfirm(<?php echo $memo['id']; ?>)" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    
                                    <form id="deleteMemoForm<?php echo $memo['id']; ?>" method="POST" class="hidden">
                                        <input type="hidden" name="memo_id" value="<?php echo $memo['id']; ?>">
                                        <button type="submit" name="delete_memo" value="1"></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteMemoConfirm(memoId) {
    showConfirmDialog(
        'Delete Memo',
        'Are you sure you want to delete this memo? This action cannot be undone.',
        function() {
            document.getElementById('deleteMemoForm' + memoId).submit();
        }
    );
}
</script>

<?php require_once 'includes/foot.php'; ?>
