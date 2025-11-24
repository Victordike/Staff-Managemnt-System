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

// Handle bulk memo deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $memo_ids = isset($_POST['memo_ids']) ? array_map('intval', $_POST['memo_ids']) : [];
    
    if (!empty($memo_ids)) {
        try {
            $db = Database::getInstance();
            $deleted_count = 0;
            
            foreach ($memo_ids as $memo_id) {
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
                    $deleted_count++;
                }
            }
            
            if ($deleted_count > 0) {
                $success = "Successfully deleted $deleted_count memo(s)";
            } else {
                $error = 'No memos were deleted';
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'Failed to delete memos';
        }
    }
}

// Handle single memo deletion
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
        error_log($e->getMessage());
        $error = 'Failed to delete memo';
    }
}

// Get filter values
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_type = $_GET['type'] ?? 'all';

try {
    $db = Database::getInstance();
    
    // Build base query
    $query = "SELECT m.*, COUNT(mr.id) as recipient_count, COUNT(CASE WHEN mr.read_at IS NOT NULL THEN 1 END) as read_count
             FROM memos m
             LEFT JOIN memo_recipients mr ON m.id = mr.memo_id
             WHERE m.sender_id = ?";
    
    $params = [$_SESSION['user_id']];
    
    // Add date range filter
    if (!empty($filter_date_from)) {
        $query .= " AND DATE(m.created_at) >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $query .= " AND DATE(m.created_at) <= ?";
        $params[] = $filter_date_to;
    }
    
    // Add file type filter
    if ($filter_type !== 'all') {
        if ($filter_type === 'image') {
            $query .= " AND m.file_type IN ('image/jpeg', 'image/png', 'image/gif')";
        } elseif ($filter_type === 'pdf') {
            $query .= " AND m.file_type = 'application/pdf'";
        } elseif ($filter_type === 'document') {
            $query .= " AND m.file_type IN ('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')";
        }
    }
    
    $query .= " GROUP BY m.id ORDER BY m.created_at DESC";
    
    // Get all memos sent by super admin with recipient count
    $memos = $db->fetchAll($query, $params);
} catch (Exception $e) {
    error_log($e->getMessage());
    $memos = [];
}
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Manage Memos</h2>
        <p class="text-gray-600 dark:text-gray-300">View, filter, and manage all memos you have sent</p>
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

    <!-- Filter Section -->
    <div class="card mb-6">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
            <i class="fas fa-filter mr-2"></i>Filter Memos
        </h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar mr-2"></i>From Date
                </label>
                <input type="date" name="date_from" class="input-field w-full" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar mr-2"></i>To Date
                </label>
                <input type="date" name="date_to" class="input-field w-full" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-file mr-2"></i>File Type
                </label>
                <select name="type" class="input-field w-full">
                    <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="image" <?php echo $filter_type === 'image' ? 'selected' : ''; ?>>Images</option>
                    <option value="pdf" <?php echo $filter_type === 'pdf' ? 'selected' : ''; ?>>PDFs</option>
                    <option value="document" <?php echo $filter_type === 'document' ? 'selected' : ''; ?>>Documents</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary w-full">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="manage_memos.php" class="btn-secondary w-full text-center">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <?php if (empty($memos)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-envelope text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No memos found</p>
            <a href="upload_memo.php" class="btn-primary mt-6 inline-block">
                <i class="fas fa-plus mr-2"></i>Create Memo
            </a>
        </div>
    <?php else: ?>
        <!-- Bulk Actions -->
        <div id="bulkActionBar" class="card mb-6 bg-blue-50 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 hidden">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <p class="text-blue-800 dark:text-blue-200">
                        <strong><span id="selectedCount">0</span> memo(s) selected</strong>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="bulkDeleteConfirm()" class="btn-danger">
                        <i class="fas fa-trash mr-2"></i>Delete Selected
                    </button>
                    <button type="button" onclick="clearSelection()" class="btn-secondary">
                        <i class="fas fa-times mr-2"></i>Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="overflow-x-auto">
                <form id="bulkForm" method="POST">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 w-12">
                                    <input type="checkbox" id="selectAll" class="cursor-pointer" onchange="toggleSelectAll(this)">
                                </th>
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
                                    <td class="px-3 sm:px-4 py-3 text-center">
                                        <input type="checkbox" name="memo_ids" value="<?php echo $memo['id']; ?>" class="memo-checkbox cursor-pointer" onchange="updateBulkUI()">
                                    </td>
                                    <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 font-semibold max-w-xs truncate">
                                        <?php echo htmlspecialchars($memo['title']); ?>
                                    </td>
                                    <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 hidden md:table-cell">
                                        <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded">
                                            <?php 
                                            $type_map = [
                                                'image/jpeg' => 'JPG',
                                                'image/png' => 'PNG',
                                                'image/gif' => 'GIF',
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
                                        <?php echo date('M j, Y H:i', strtotime($memo['created_at'])); ?>
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
                                        
                                        <form id="deleteMemoForm<?php echo $memo['id']; ?>" method="POST" style="display: none;">
                                            <input type="hidden" name="memo_id" value="<?php echo $memo['id']; ?>">
                                            <input type="hidden" name="delete_memo" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="bulk_delete" value="1">
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSelectAll(checkbox) {
    const memoCheckboxes = document.querySelectorAll('.memo-checkbox');
    memoCheckboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkUI();
}

function updateBulkUI() {
    const selectedCheckboxes = document.querySelectorAll('.memo-checkbox:checked');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = selectedCheckboxes.length;
    
    if (selectedCheckboxes.length > 0) {
        bulkActionBar.classList.remove('hidden');
    } else {
        bulkActionBar.classList.add('hidden');
        document.getElementById('selectAll').checked = false;
    }
}

function clearSelection() {
    document.querySelectorAll('.memo-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkUI();
}

function bulkDeleteConfirm() {
    const selectedCount = document.querySelectorAll('.memo-checkbox:checked').length;
    if (selectedCount === 0) return;
    
    showConfirmDialog(
        'Delete Multiple Memos',
        `Are you sure you want to delete ${selectedCount} memo(s)? This action cannot be undone.`,
        function() {
            document.getElementById('bulkForm').submit();
        }
    );
}

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
