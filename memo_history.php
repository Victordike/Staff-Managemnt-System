<?php
$pageTitle = 'Memo History';
require_once 'includes/head.php';

// Check if user is admin
if ($userRole !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

$filter_status = $_GET['status'] ?? 'all'; // all, read, unread
$filter_type = $_GET['type'] ?? 'all'; // all, image, pdf, document

try {
    $db = Database::getInstance();
    
    // Get admin_id from session with fallback to user_id
    $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$admin_id) {
        $memos = [];
    } else {
        // Build query based on filters (deduplicated by file_path, showing only most recent)
        $query = "SELECT DISTINCT ON (m.file_path) m.*, u.firstname, u.lastname, mr.read_at
                 FROM memos m
                 JOIN memo_recipients mr ON m.id = mr.memo_id
                 JOIN users u ON m.sender_id = u.id
                 WHERE mr.admin_id = ?";
        $params = [$admin_id];
        
        // Filter by read status
        if ($filter_status === 'read') {
            $query .= " AND mr.read_at IS NOT NULL";
        } elseif ($filter_status === 'unread') {
            $query .= " AND mr.read_at IS NULL";
        }
        
        // Filter by file type
        if ($filter_type !== 'all') {
            if ($filter_type === 'image') {
                $query .= " AND m.file_type IN ('image/jpeg', 'image/png', 'image/gif')";
            } elseif ($filter_type === 'pdf') {
                $query .= " AND m.file_type = 'application/pdf'";
            } elseif ($filter_type === 'document') {
                $query .= " AND m.file_type IN ('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')";
            }
        }
        
        $query .= " ORDER BY m.file_path, m.created_at DESC";
        
        $memos = $db->fetchAll($query, $params);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $memos = [];
}

// Count statistics
$read_count = 0;
$unread_count = 0;
foreach ($memos as $memo) {
    if ($memo['read_at']) {
        $read_count++;
    } else {
        $unread_count++;
    }
}
?>

<div class="container mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Memo History</h2>
                <p class="text-gray-600 dark:text-gray-300">View and manage all your memos</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Total Memos</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo count($memos); ?></p>
            </div>
            <div class="card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Read Memos</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $read_count; ?></p>
            </div>
            <div class="card bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Unread Memos</p>
                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400"><?php echo $unread_count; ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                <i class="fas fa-filter mr-2"></i>Filter Memos
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Status
                    </label>
                    <select id="statusFilter" class="input-field" onchange="updateFilters()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Memos</option>
                        <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file mr-2"></i>File Type
                    </label>
                    <select id="typeFilter" class="input-field" onchange="updateFilters()">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="image" <?php echo $filter_type === 'image' ? 'selected' : ''; ?>>Images</option>
                        <option value="pdf" <?php echo $filter_type === 'pdf' ? 'selected' : ''; ?>>PDFs</option>
                        <option value="document" <?php echo $filter_type === 'document' ? 'selected' : ''; ?>>Documents</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Memos List -->
    <?php if (empty($memos)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-inbox text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No memos found matching your filters</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($memos as $memo): ?>
                <div class="card hover:shadow-lg transition">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($memo['title']); ?></h3>
                                <?php if (!$memo['read_at']): ?>
                                    <span class="inline-block px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 text-xs font-semibold rounded-full">
                                        <i class="fas fa-star mr-1"></i>Unread
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i>Read
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <i class="fas fa-user mr-1"></i>From: <strong><?php echo htmlspecialchars($memo['firstname'] . ' ' . $memo['lastname']); ?></strong>
                            </p>
                            <?php if ($memo['description']): ?>
                                <p class="text-gray-700 dark:text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars(substr($memo['description'], 0, 100)) . (strlen($memo['description']) > 100 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('M j, Y \a\t g:i A', strtotime($memo['created_at'])); ?></span>
                                <?php if ($memo['read_at']): ?>
                                    <span><i class="fas fa-clock mr-1"></i>Read: <?php echo date('M j, Y \a\t g:i A', strtotime($memo['read_at'])); ?></span>
                                <?php endif; ?>
                            </div>
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

<script>
function updateFilters() {
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    const url = new URL(window.location);
    url.searchParams.set('status', status);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
}
</script>

<?php require_once 'includes/foot.php'; ?>
