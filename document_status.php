<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$search_query = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    $notification_id = $_POST['notification_id'] ?? null;
    
    if ($notification_id) {
        $stmt = $db->prepare("
            UPDATE document_notifications 
            SET is_read = 1, read_at = NOW()
            WHERE id = ? AND admin_id = ?
        ");
        $stmt->execute([$notification_id, $admin_id]);
    }
    
    header('Location: document_status.php');
    exit;
}

$pageTitle = 'Document Status & Notifications';
require_once 'includes/head.php';

$notifications = $db->fetchAll("
    SELECT 
        dn.*,
        ds.original_filename,
        ds.approval_status,
        ds.current_stage,
        ds.created_at as document_created_at,
        est.firstname as est_firstname,
        est.surname as est_surname,
        reg.firstname as reg_firstname,
        reg.surname as reg_surname
    FROM document_notifications dn
    JOIN document_submissions ds ON dn.document_id = ds.id
    LEFT JOIN admin_users est ON ds.establishment_approved_by = est.id
    LEFT JOIN admin_users reg ON ds.registrar_approved_by = reg.id
    WHERE dn.admin_id = ?
    ORDER BY dn.created_at DESC
", [$admin_id]);

$unread_count = $db->fetchOne("
    SELECT COUNT(*) as count FROM document_notifications 
    WHERE admin_id = ? AND is_read = 0
", [$admin_id])['count'] ?? 0;

$where_search = '';
$params = [$admin_id];
if (!empty($search_query)) {
    $where_search = "AND ds.original_filename LIKE ?";
    $params[] = '%' . $search_query . '%';
}

$documents = $db->fetchAll("
    SELECT 
        ds.*,
        est.firstname as est_firstname,
        est.surname as est_surname,
        reg.firstname as reg_firstname,
        reg.surname as reg_surname
    FROM document_submissions ds
    LEFT JOIN admin_users est ON ds.establishment_approved_by = est.id
    LEFT JOIN admin_users reg ON ds.registrar_approved_by = reg.id
    WHERE ds.admin_id = ? $where_search
    ORDER BY ds.created_at DESC
", $params);

$document_stats = [
    'pending' => 0,
    'establishment_approved' => 0,
    'registrar_approved' => 0,
    'rejected' => 0
];

foreach ($documents as $doc) {
    $document_stats[$doc['approval_status']]++;
}

?>

<div class="max-w-6xl mx-auto">
    <div class="card mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-bell text-blue-600 mr-2"></i>Document Status & Notifications
            </h2>
            <?php if ($unread_count > 0): ?>
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                    <?php echo $unread_count; ?> unread
                </span>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-blue-600"><?php echo $document_stats['pending']; ?></div>
                <div class="text-gray-600 text-sm">Pending</div>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $document_stats['establishment_approved']; ?></div>
                <div class="text-gray-600 text-sm">Establishment Approved</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-green-600"><?php echo $document_stats['registrar_approved']; ?></div>
                <div class="text-gray-600 text-sm">Approved</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-red-600"><?php echo $document_stats['rejected']; ?></div>
                <div class="text-gray-600 text-sm">Rejected</div>
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-inbox text-blue-600 mr-2"></i>Notifications (<?php echo count($notifications); ?>)
        </h3>

        <?php if (empty($notifications)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-bell-slash text-gray-400 text-3xl mb-2"></i>
                <p class="text-gray-600">No notifications yet</p>
            </div>
        <?php else: ?>
            <div class="space-y-3 mb-6">
                <?php foreach ($notifications as $notif): ?>
                    <div class="border rounded-lg p-4 <?php echo $notif['is_read'] ? 'bg-gray-50' : 'bg-blue-50 border-blue-300'; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <?php
                                    $icon_class = '';
                                    $type_text = '';
                                    switch ($notif['notification_type']) {
                                        case 'establishment_approved':
                                            $icon_class = 'fas fa-check-circle text-yellow-600';
                                            $type_text = 'Establishment Approved';
                                            break;
                                        case 'registrar_approved':
                                            $icon_class = 'fas fa-check-circle text-green-600';
                                            $type_text = 'Approved';
                                            break;
                                        case 'rejected':
                                            $icon_class = 'fas fa-times-circle text-red-600';
                                            $type_text = 'Rejected';
                                            break;
                                        default:
                                            $icon_class = 'fas fa-info-circle text-blue-600';
                                            $type_text = 'Update';
                                    }
                                    ?>
                                    <i class="<?php echo $icon_class; ?>"></i>
                                    <span class="font-semibold text-gray-800"><?php echo $type_text; ?></span>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="ml-auto text-xs bg-blue-500 text-white px-2 py-1 rounded">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></p>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <form method="POST" action="?action=mark_read" class="ml-4">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                        Mark as read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-file-archive text-green-600 mr-2"></i>My Documents (<?php echo count($documents); ?>)
                </h3>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" placeholder="Search by document name..." value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="document_status.php" class="btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($documents)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-inbox text-gray-400 text-3xl mb-2"></i>
                <p class="text-gray-600 mb-4">No documents uploaded yet</p>
                <a href="upload_documents.php" class="btn-primary">Upload a Document</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="px-4 py-3 text-left">Document</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Current Stage</th>
                            <th class="px-4 py-3 text-left">Submitted</th>
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
                                        <span><?php echo htmlspecialchars($doc['original_filename']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $status_badge = '';
                                    switch ($doc['approval_status']) {
                                        case 'pending':
                                            $status_badge = '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">Pending</span>';
                                            break;
                                        case 'establishment_approved':
                                            $status_badge = '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">Establishment Approved</span>';
                                            break;
                                        case 'registrar_approved':
                                            $status_badge = '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">✓ Approved</span>';
                                            break;
                                        case 'rejected':
                                            $status_badge = '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">Rejected</span>';
                                            break;
                                    }
                                    echo $status_badge;
                                    ?>
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
                                <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-sm btn-primary">
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
