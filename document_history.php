<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;

$sort_by = $_GET['sort_by'] ?? 'date_desc';
$filter_status = $_GET['filter'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$sort_options = [
    'date_desc' => 'Newest First',
    'date_asc' => 'Oldest First',
    'filename_asc' => 'Filename (A-Z)',
    'filename_desc' => 'Filename (Z-A)',
    'status_asc' => 'Status (A-Z)',
];

$order_sql = '';
switch ($sort_by) {
    case 'date_asc':
        $order_sql = "ORDER BY ds.created_at ASC";
        break;
    case 'filename_asc':
        $order_sql = "ORDER BY ds.original_filename ASC";
        break;
    case 'filename_desc':
        $order_sql = "ORDER BY ds.original_filename DESC";
        break;
    case 'status_asc':
        $order_sql = "ORDER BY ds.approval_status ASC, ds.created_at DESC";
        break;
    default:
        $order_sql = "ORDER BY ds.created_at DESC";
}

$where_status = '';
if ($filter_status !== 'all') {
    if ($filter_status === 'approved') {
        $where_status = "AND ds.approval_status = 'registrar_approved'";
    } elseif ($filter_status === 'rejected') {
        $where_status = "AND ds.approval_status = 'rejected'";
    } elseif ($filter_status === 'pending') {
        $where_status = "AND ds.approval_status IN ('pending', 'establishment_approved')";
    }
}

$where_search = '';
if (!empty($search_query)) {
    $where_search = "AND ds.original_filename LIKE ?";
}

$params = [$admin_id];
if (!empty($search_query)) {
    $params[] = '%' . $search_query . '%';
}

$documents = $db->fetchAll("
    SELECT 
        ds.*,
        est.firstname as est_firstname,
        est.surname as est_surname,
        reg.firstname as reg_firstname,
        reg.surname as reg_surname,
        rej.firstname as rej_firstname,
        rej.surname as rej_surname
    FROM document_submissions ds
    LEFT JOIN admin_users est ON ds.establishment_approved_by = est.id
    LEFT JOIN admin_users reg ON ds.registrar_approved_by = reg.id
    LEFT JOIN admin_users rej ON ds.rejected_by_admin_id = rej.id
    WHERE ds.admin_id = ? $where_status $where_search
    $order_sql
", $params);

$stats = [
    'total' => 0,
    'approved' => 0,
    'rejected' => 0,
    'pending' => 0
];

foreach ($documents as $doc) {
    $stats['total']++;
    if ($doc['approval_status'] === 'registrar_approved') {
        $stats['approved']++;
    } elseif ($doc['approval_status'] === 'rejected') {
        $stats['rejected']++;
    } else {
        $stats['pending']++;
    }
}

$pageTitle = 'Document History';
require_once 'includes/head.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="card mb-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-history text-blue-600 mr-2"></i>Document History
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-gray-700"><?php echo $stats['total']; ?></div>
                <div class="text-gray-600 text-sm">Total Documents</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-green-600"><?php echo $stats['approved']; ?></div>
                <div class="text-gray-600 text-sm">Approved</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-red-600"><?php echo $stats['rejected']; ?></div>
                <div class="text-gray-600 text-sm">Rejected</div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-blue-600"><?php echo $stats['pending']; ?></div>
                <div class="text-gray-600 text-sm">Pending</div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 mb-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-search text-blue-600 mr-2"></i>Search Documents
                </label>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="text" name="search" placeholder="Search by document name..." value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="document_history.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sort text-blue-600 mr-2"></i>Sort By
                    </label>
                    <form method="GET" class="flex gap-2">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter_status); ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <select name="sort_by" onchange="this.form.submit()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            <?php foreach ($sort_options as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $sort_by === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-filter text-blue-600 mr-2"></i>Filter By Status
                    </label>
                    <form method="GET" class="flex gap-2">
                        <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <select name="filter" onchange="this.form.submit()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Documents</option>
                            <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>✓ Approved Only</option>
                            <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected Only</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-file-archive text-green-600 mr-2"></i>Documents
        </h3>

        <?php if (empty($documents)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-inbox text-gray-400 text-3xl mb-2"></i>
                <p class="text-gray-600 mb-4">No documents found</p>
                <a href="upload_documents.php" class="btn-primary">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>Upload a Document
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="px-4 py-3 text-left">Document</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Uploaded</th>
                            <th class="px-4 py-3 text-left">Stage</th>
                            <th class="px-4 py-3 text-left">Details</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <?php if (strpos($doc['file_type'], 'image') !== false): ?>
                                            <i class="fas fa-image text-blue-600 mr-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                                        <?php endif; ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($doc['original_filename']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $status_badge = '';
                                    $status_icon = '';
                                    switch ($doc['approval_status']) {
                                        case 'pending':
                                            $status_badge = 'bg-blue-100 text-blue-800';
                                            $status_icon = '<i class="fas fa-hourglass-half mr-1"></i>';
                                            $status_text = 'Pending';
                                            break;
                                        case 'establishment_approved':
                                            $status_badge = 'bg-yellow-100 text-yellow-800';
                                            $status_icon = '<i class="fas fa-check-circle mr-1"></i>';
                                            $status_text = 'Est. Approved';
                                            break;
                                        case 'registrar_approved':
                                            $status_badge = 'bg-green-100 text-green-800';
                                            $status_icon = '<i class="fas fa-check-double mr-1"></i>';
                                            $status_text = '✓ Approved';
                                            break;
                                        case 'rejected':
                                            $status_badge = 'bg-red-100 text-red-800';
                                            $status_icon = '<i class="fas fa-times-circle mr-1"></i>';
                                            $status_text = 'Rejected';
                                            break;
                                    }
                                    echo '<span class="px-3 py-1 ' . $status_badge . ' rounded-full text-sm font-semibold">' . $status_icon . $status_text . '</span>';
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600">
                                        <?php echo date('M d, Y H:i', strtotime($doc['created_at'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm">
                                        <?php
                                        $stages = [
                                            'establishment' => '1. Establishment',
                                            'registrar' => '2. Registrar',
                                            'completed' => '✓ Completed'
                                        ];
                                        echo $stages[$doc['current_stage']] ?? $doc['current_stage'];
                                        ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="space-y-1">
                                        <?php if ($doc['approval_status'] === 'registrar_approved'): ?>
                                            <div class="text-green-700">
                                                <i class="fas fa-check text-sm"></i>
                                                Approved on <?php echo date('M d, Y', strtotime($doc['registrar_approved_at'])); ?>
                                            </div>
                                        <?php elseif ($doc['approval_status'] === 'rejected'): ?>
                                            <div class="text-red-700">
                                                <i class="fas fa-times text-sm"></i>
                                                Rejected on <?php echo date('M d, Y', strtotime($doc['rejected_at'])); ?>
                                            </div>
                                            <?php if ($doc['rejection_reason']): ?>
                                                <div class="text-xs text-red-600 mt-1">
                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($doc['rejection_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($doc['approval_status'] === 'establishment_approved'): ?>
                                            <div class="text-yellow-700">
                                                <i class="fas fa-arrow-right text-sm"></i>
                                                Est. approved on <?php echo date('M d, Y', strtotime($doc['establishment_approved_at'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-blue-700">
                                                <i class="fas fa-clock text-sm"></i>
                                                Awaiting review
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-sm btn-primary inline-flex items-center">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
