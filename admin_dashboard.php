<?php
require_once 'includes/functions.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
require_once 'includes/head.php';

try {
    $db = Database::getInstance();
    $staffId = $_SESSION['staff_id'];
    $adminId = $_SESSION['admin_id'];
    
    $userRoles = $db->fetchAll(
        "SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL",
        [$adminId]
    );
    
    $hasEstablishmentRole = false;
    $hasRegistrarRole = false;
    foreach ($userRoles as $role) {
        if ($role['role_name'] === 'Establishment') {
            $hasEstablishmentRole = true;
        }
        if ($role['role_name'] === 'Registrar') {
            $hasRegistrarRole = true;
        }
    }
    
    $profile = $db->fetchOne(
        "SELECT * FROM admin_users WHERE staff_id = ?",
        [$staffId]
    );
    
    $documentsData = $db->fetchOne(
        "SELECT 
            (SELECT COUNT(*) FROM document_submissions WHERE approval_status = 'pending' AND current_stage = 'establishment') as pending_establishment,
            (SELECT COUNT(*) FROM document_submissions WHERE approval_status = 'pending' AND current_stage = 'registrar') as pending_registrar,
            (SELECT COUNT(*) FROM document_submissions WHERE approval_status = 'registrar_approved') as total_approved,
            (SELECT COUNT(*) FROM document_submissions WHERE approval_status = 'rejected') as total_rejected,
            (SELECT COUNT(*) FROM document_submissions WHERE parent_document_id IS NOT NULL) as resubmitted,
            (SELECT COUNT(*) FROM document_submissions) as total_documents
        FROM DUAL"
    );
    
    $unreadNotifications = $db->fetchOne(
        "SELECT COUNT(*) as count FROM document_notifications WHERE admin_id = ? AND is_read = 0",
        [$adminId]
    );
    
    $recentDocuments = $db->fetchAll(
        "SELECT ds.*, au.firstname, au.surname 
         FROM document_submissions ds
         LEFT JOIN admin_users au ON ds.admin_id = au.id
         ORDER BY ds.created_at DESC LIMIT 8"
    );
    
    $recentApprovals = $db->fetchAll(
        "SELECT ds.id, ds.original_filename, ds.approval_status, 
                ds.establishment_approved_at, ds.registrar_approved_at,
                au.firstname, au.surname
         FROM document_submissions ds
         LEFT JOIN admin_users au ON ds.admin_id = au.id
         WHERE ds.approval_status IN ('establishment_approved', 'registrar_approved', 'rejected')
         ORDER BY COALESCE(ds.registrar_approved_at, ds.establishment_approved_at) DESC LIMIT 5"
    );
    
    $recentUnreadNotifs = $db->fetchAll(
        "SELECT dn.*, ds.original_filename 
         FROM document_notifications dn
         LEFT JOIN document_submissions ds ON dn.document_id = ds.id
         WHERE dn.admin_id = ? AND dn.is_read = 0
         ORDER BY dn.created_at DESC LIMIT 5",
        [$adminId]
    );
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $profile = null;
    $documentsData = [];
    $unreadNotifications = ['count' => 0];
    $recentDocuments = [];
    $recentApprovals = [];
    $recentUnreadNotifs = [];
    $hasEstablishmentRole = false;
    $hasRegistrarRole = false;
}
?>

<!-- Welcome Banner -->
<div class="card bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white mb-8 shadow-xl">
    <div class="flex flex-col md:flex-row items-center justify-between">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h1>
            <p class="text-indigo-100 text-lg">Federal Polytechnic of Oil and Gas, Bonny Island, Rivers State, Nigeria</p>
            <p class="text-indigo-200 text-sm mt-2"><i class="fas fa-calendar mr-2"></i><?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="mt-6 md:mt-0 text-6xl opacity-20"><i class="fas fa-file-alt"></i></div>
    </div>
</div>

<!-- Document Pipeline Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Pending Establishment Review -->
    <div class="card bg-gradient-to-br from-yellow-500 to-orange-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-black text-sm mb-1 font-semibold">Establishment Review</p>
                <h3 class="text-4xl font-bold text-black"><?php echo $documentsData['pending_establishment'] ?? 0; ?></h3>
            </div>
            <i class="fas fa-hourglass-half text-5xl text-black opacity-50"></i>
        </div>
        <div class="w-full bg-yellow-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo ($documentsData['total_documents'] > 0) ? (($documentsData['pending_establishment'] / $documentsData['total_documents']) * 100) : 0; ?>%"></div>
        </div>
    </div>
    
    <!-- Pending Registrar Review -->
    <div class="card bg-gradient-to-br from-blue-300 to-blue-700 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-black text-sm mb-1 font-semibold">Registrar Review</p>
                <h3 class="text-4xl font-bold text-black"><?php echo $documentsData['pending_registrar'] ?? 0; ?></h3>
            </div>
            <i class="fas fa-clipboard-list text-5xl text-black opacity-50"></i>
        </div>
        <div class="w-full bg-blue-300 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo ($documentsData['total_documents'] > 0) ? (($documentsData['pending_registrar'] / $documentsData['total_documents']) * 100) : 0; ?>%"></div>
        </div>
    </div>
    
    <!-- Total Approved -->
    <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-black text-sm mb-1 font-semibold">Approved Documents</p>
                <h3 class="text-4xl font-bold text-black"><?php echo $documentsData['total_approved'] ?? 0; ?></h3>
            </div>
            <i class="fas fa-check-circle text-5xl text-black opacity-50"></i>
        </div>
        <div class="w-full bg-green-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo ($documentsData['total_documents'] > 0) ? (($documentsData['total_approved'] / $documentsData['total_documents']) * 100) : 0; ?>%"></div>
        </div>
    </div>
    
    <!-- Total Rejected -->
    <div class="card bg-gradient-to-br from-blue-300 to-blue-700 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-black text-sm mb-1 font-semibold">Rejected Documents</p>
                <h3 class="text-4xl font-bold text-black"><?php echo $documentsData['total_rejected'] ?? 0; ?></h3>
            </div>
            <i class="fas fa-times-circle text-5xl text-black opacity-50"></i>
        </div>
        <div class="w-full bg-blue-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo ($documentsData['total_documents'] > 0) ? (($documentsData['total_rejected'] / $documentsData['total_documents']) * 100) : 0; ?>%"></div>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- System Overview -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b-2 border-blue-500">
            <i class="fas fa-chart-bar text-blue-600 mr-2"></i>System Overview
        </h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between p-2 bg-blue-50 rounded">
                <span class="text-gray-700 font-semibold">Total Documents</span>
                <span class="text-2xl font-bold text-blue-600"><?php echo $documentsData['total_documents'] ?? 0; ?></span>
            </div>
            <div class="flex items-center justify-between p-2 bg-purple-50 rounded">
                <span class="text-gray-700 font-semibold">Resubmitted</span>
                <span class="text-2xl font-bold text-purple-600"><?php echo $documentsData['resubmitted'] ?? 0; ?></span>
            </div>
            <div class="flex items-center justify-between p-2 bg-indigo-50 rounded">
                <span class="text-gray-700 font-semibold">Unread Notifications</span>
                <span class="inline-block bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold"><?php echo $unreadNotifications['count'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b-2 border-green-500">
            <i class="fas fa-bolt text-green-600 mr-2"></i>Quick Actions
        </h3>
        <div class="space-y-2">
            <?php if (isSuperAdmin() || $hasEstablishmentRole): ?>
                <a href="establishment_approve_documents.php" class="block w-full bg-yellow-100 hover:bg-yellow-200 text-yellow-800 py-2 px-3 rounded-lg transition text-center text-sm font-semibold">
                    <i class="fas fa-check mr-2"></i>Establishment Review
                </a>
            <?php endif; ?>
            <?php if (isSuperAdmin() || $hasRegistrarRole): ?>
                <a href="registrar_approve_documents.php" class="block w-full bg-orange-100 hover:bg-orange-200 text-orange-800 py-2 px-3 rounded-lg transition text-center text-sm font-semibold">
                    <i class="fas fa-pen-fancy mr-2"></i>Registrar Review
                </a>
            <?php endif; ?>
            <a href="document_history.php" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-3 rounded-lg transition text-center text-sm font-semibold">
                <i class="fas fa-history mr-2"></i>View History
            </a>
        </div>
    </div>
    
    <!-- Personal Info -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b-2 border-purple-500">
            <i class="fas fa-user text-purple-600 mr-2"></i>Your Profile
        </h3>
        <div class="space-y-2 text-sm text-gray-700">
            <div class="bg-gray-50 rounded p-2">
                <p class="text-xs text-gray-500 font-semibold">Position</p>
                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($profile['position'] ?? 'N/A'); ?></p>
            </div>
            <div class="bg-gray-50 rounded p-2">
                <p class="text-xs text-gray-500 font-semibold">Department</p>
                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($profile['department'] ?? 'N/A'); ?></p>
            </div>
            <div class="bg-gray-50 rounded p-2">
                <p class="text-xs text-gray-500 font-semibold">Email</p>
                <p class="text-xs font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($profile['official_email'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Unread Notifications -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Unread Notifications -->
    <div class="card">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-bell mr-2 text-red-600"></i>Unread Notifications
            <?php if ($unreadNotifications['count'] > 0): ?>
                <span class="inline-block bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold ml-2"><?php echo $unreadNotifications['count']; ?></span>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($recentUnreadNotifs)): ?>
            <p class="text-gray-500 text-center py-6">No unread notifications</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recentUnreadNotifs as $notif): ?>
                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($notif['original_filename'] ?? 'Document'); ?></p>
                                <p class="text-gray-600 text-xs mt-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <p class="text-gray-500 text-xs mt-2"><?php echo formatDate($notif['created_at']); ?></p>
                            </div>
                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                <?php 
                                    $typeMap = [
                                        'submitted' => 'Submitted',
                                        'establishment_approved' => 'Est. Approved',
                                        'registrar_approved' => 'Registrar Approved',
                                        'rejected' => 'Rejected'
                                    ];
                                    echo $typeMap[$notif['notification_type']] ?? ucfirst(str_replace('_', ' ', $notif['notification_type']));
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Approvals/Rejections -->
    <div class="card">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-history mr-2 text-blue-600"></i>Recent Approvals & Rejections
        </h2>
        
        <?php if (empty($recentApprovals)): ?>
            <p class="text-gray-500 text-center py-6">No approvals yet</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recentApprovals as $approval): ?>
                    <div class="p-3 bg-gray-50 border-l-4 rounded hover:bg-gray-100 transition <?php 
                        if ($approval['approval_status'] === 'rejected') {
                            echo 'border-red-500';
                        } elseif ($approval['approval_status'] === 'establishment_approved') {
                            echo 'border-yellow-500';
                        } else {
                            echo 'border-green-500';
                        }
                    ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm truncate"><?php echo htmlspecialchars($approval['original_filename']); ?></p>
                                <p class="text-gray-600 text-xs mt-1">
                                    By: <span class="font-semibold"><?php echo htmlspecialchars($approval['firstname'] . ' ' . $approval['surname']); ?></span>
                                </p>
                            </div>
                            <span class="inline-block <?php 
                                if ($approval['approval_status'] === 'rejected') {
                                    echo 'bg-red-100 text-red-800';
                                } elseif ($approval['approval_status'] === 'establishment_approved') {
                                    echo 'bg-yellow-100 text-yellow-800';
                                } else {
                                    echo 'bg-green-100 text-green-800';
                                }
                            ?> px-2 py-1 rounded text-xs font-semibold ml-2">
                                <?php 
                                    $statusMap = [
                                        'rejected' => 'Rejected',
                                        'establishment_approved' => 'Est. Approved',
                                        'registrar_approved' => 'Approved'
                                    ];
                                    echo $statusMap[$approval['approval_status']] ?? ucfirst(str_replace('_', ' ', $approval['approval_status']));
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Document Submissions -->
<div class="card mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-file-upload mr-2 text-blue-600"></i>Recent Document Submissions
    </h2>
    
    <?php if (empty($recentDocuments)): ?>
        <p class="text-gray-500 text-center py-8">No documents submitted yet</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Document Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Submitted By</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Stage</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Submitted On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentDocuments as $index => $doc): ?>
                        <tr class="border-b border-gray-100 hover:bg-blue-50 transition <?php echo $index % 2 === 0 ? 'bg-gray-50' : ''; ?>">
                            <td class="py-3 px-4 font-semibold text-blue-600 truncate" title="<?php echo htmlspecialchars($doc['original_filename']); ?>">
                                <i class="fas fa-file mr-2"></i><?php echo htmlspecialchars(substr($doc['original_filename'], 0, 30)); ?>
                            </td>
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($doc['firstname'] . ' ' . $doc['surname']); ?></td>
                            <td class="py-3 px-4">
                                <span class="inline-block <?php 
                                    if ($doc['approval_status'] === 'pending') {
                                        echo 'bg-yellow-100 text-yellow-800';
                                    } elseif ($doc['approval_status'] === 'registrar_approved') {
                                        echo 'bg-green-100 text-green-800';
                                    } else {
                                        echo 'bg-red-100 text-red-800';
                                    }
                                ?> px-2 py-1 rounded text-xs font-semibold">
                                    <?php 
                                        $statusMap = [
                                            'pending' => 'Pending',
                                            'establishment_approved' => 'Est. Approved',
                                            'registrar_approved' => 'Approved',
                                            'rejected' => 'Rejected'
                                        ];
                                        echo $statusMap[$doc['approval_status']] ?? ucfirst(str_replace('_', ' ', $doc['approval_status']));
                                    ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                    <?php echo ucfirst($doc['current_stage']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?php echo formatDate($doc['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Animated Information Ticker -->
<div class="ticker-container">
    <div class="ticker-content">
        <span class="ticker-item"><i class="fas fa-file-alt mr-2 text-blue-500"></i><?php echo $documentsData['total_documents']; ?> total documents in system</span>
        <span class="ticker-item"><i class="fas fa-hourglass-half mr-2 text-yellow-500"></i><?php echo $documentsData['pending_establishment'] + $documentsData['pending_registrar']; ?> documents pending review</span>
        <span class="ticker-item"><i class="fas fa-check-circle mr-2 text-green-500"></i><?php echo $documentsData['total_approved']; ?> documents approved</span>
        <span class="ticker-item"><i class="fas fa-redo mr-2 text-purple-500"></i><?php echo $documentsData['resubmitted']; ?> resubmitted documents</span>
        <span class="ticker-item"><i class="fas fa-bell mr-2 text-red-500"></i><?php echo $unreadNotifications['count']; ?> unread notifications</span>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
