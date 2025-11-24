<?php
$pageTitle = 'Memo History';
require_once 'includes/head.php';

// Check if user is superadmin
if ($userRole !== 'superadmin') {
    http_response_code(403);
    die('Access denied');
}

$filter_status = $_GET['status'] ?? 'all'; // all, delivered, pending
$filter_type = $_GET['type'] ?? 'all'; // all, image, pdf, document
$search = $_GET['search'] ?? '';

try {
    $db = Database::getInstance();
    
    // Build query based on filters
    $query = "SELECT m.id, m.title, m.file_type, m.created_at,
                     COUNT(mr.id) as recipient_count, 
                     COUNT(CASE WHEN mr.read_at IS NOT NULL THEN 1 END) as read_count,
                     COUNT(CASE WHEN mr.read_at IS NULL THEN 1 END) as unread_count
              FROM memos m
              LEFT JOIN memo_recipients mr ON m.id = mr.memo_id
              WHERE m.sender_id = ?";
    
    $params = [$_SESSION['user_id']];
    
    // Filter by delivery status
    if ($filter_status === 'delivered') {
        $query .= " AND EXISTS (SELECT 1 FROM memo_recipients WHERE memo_id = m.id)";
    } elseif ($filter_status === 'pending') {
        $query .= " AND NOT EXISTS (SELECT 1 FROM memo_recipients WHERE memo_id = m.id)";
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
    
    // Search by title
    if (!empty($search)) {
        $query .= " AND m.title ILIKE ?";
        $params[] = '%' . $search . '%';
    }
    
    $query .= " GROUP BY m.id
                ORDER BY m.created_at DESC";
    
    $memos = $db->fetchAll($query, $params);
} catch (Exception $e) {
    error_log($e->getMessage());
    $memos = [];
}

// Calculate statistics
$total_memos = count($memos);
$delivered_memos = 0;
$pending_memos = 0;
$total_reads = 0;

foreach ($memos as $memo) {
    if ($memo['recipient_count'] > 0) {
        $delivered_memos++;
        $total_reads += $memo['read_count'];
    } else {
        $pending_memos++;
    }
}
?>

<div class="container mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Memo History</h2>
                <p class="text-gray-600 dark:text-gray-300">Complete history of all memos sent</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Total Memos</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo $total_memos; ?></p>
            </div>
            <div class="card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Delivered</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $delivered_memos; ?></p>
            </div>
            <div class="card bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Pending</p>
                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400"><?php echo $pending_memos; ?></p>
            </div>
            <div class="card bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Total Reads</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo $total_reads; ?></p>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                <i class="fas fa-filter mr-2"></i>Search & Filter
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-2"></i>Search by Title
                    </label>
                    <input type="text" id="searchInput" placeholder="Search memos..." class="input-field w-full" value="<?php echo htmlspecialchars($search); ?>" onkeyup="updateFilters()">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Status
                    </label>
                    <select id="statusFilter" class="input-field w-full" onchange="updateFilters()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Memos</option>
                        <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-file mr-2"></i>File Type
                    </label>
                    <select id="typeFilter" class="input-field w-full" onchange="updateFilters()">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="image" <?php echo $filter_type === 'image' ? 'selected' : ''; ?>>Images</option>
                        <option value="pdf" <?php echo $filter_type === 'pdf' ? 'selected' : ''; ?>>PDFs</option>
                        <option value="document" <?php echo $filter_type === 'document' ? 'selected' : ''; ?>>Documents</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Memos Table -->
    <?php if (empty($memos)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-history text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No memos found</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Title</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden md:table-cell">Type</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Recipients</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden lg:table-cell">Sent Date</th>
                            <th class="px-3 sm:px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memos as $memo): ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
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
                                    <?php if ($memo['recipient_count'] == 0): ?>
                                        <span class="inline-block px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-semibold rounded">
                                            <i class="fas fa-hourglass-half mr-1"></i>Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded">
                                            <i class="fas fa-check-circle mr-1"></i>Delivered
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200">
                                    <div class="text-xs">
                                        <p><strong><?php echo $memo['read_count']; ?></strong> read / <strong><?php echo $memo['unread_count']; ?></strong> unread</p>
                                        <p class="text-gray-600 dark:text-gray-400">of <?php echo $memo['recipient_count']; ?></p>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-gray-800 dark:text-gray-200 hidden lg:table-cell text-xs">
                                    <?php echo date('M j, Y H:i', strtotime($memo['created_at'])); ?>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-xs space-x-2">
                                    <a href="view_memo.php?id=<?php echo $memo['id']; ?>" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
function updateFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status !== 'all') params.append('status', status);
    if (type !== 'all') params.append('type', type);
    
    window.location.href = 'superadmin_memo_history.php?' + params.toString();
}
</script>

<?php require_once 'includes/foot.php'; ?>
