<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/email.php';
requireAdmin();

$db = Database::getInstance();
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;

$userRoles = $db->fetchAll("
    SELECT role_name FROM admin_roles 
    WHERE admin_id = ? AND removed_at IS NULL
", [$admin_id]);

$hasEstablishmentRole = false;
foreach ($userRoles as $role) {
    if ($role['role_name'] === 'Establishment') {
        $hasEstablishmentRole = true;
        break;
    }
}

if (!$hasEstablishmentRole && !isSuperAdmin()) {
    die("<div class='text-center py-12'><p class='text-red-600 font-semibold'>Access Denied. Only Establishment admin can access this page.</p></div>");
}

$action = $_GET['action'] ?? null;
$document_id = $_GET['id'] ?? null;
$search_query = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve' && $document_id) {
    $document = $db->fetchOne("SELECT * FROM document_submissions WHERE id = ?", [$document_id]);
    $approval_comments = $_POST['approval_comments'] ?? '';
    
    if (!$document) {
        $_SESSION['error'] = 'Document not found';
        header('Location: establishment_approve_documents.php');
        exit;
    }
    
    if ($document['current_stage'] !== 'establishment' || $document['approval_status'] !== 'pending') {
        $_SESSION['error'] = 'This document cannot be approved at this stage';
        header('Location: establishment_approve_documents.php');
        exit;
    }
    
    $update = $db->prepare("
        UPDATE document_submissions 
        SET approval_status = 'establishment_approved',
            current_stage = 'registrar',
            establishment_approved_by = ?,
            establishment_approved_at = NOW(),
            establishment_comments = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($update->execute([$admin_id, $approval_comments, $document_id])) {
        $notif_stmt = $db->prepare("
            INSERT INTO document_notifications (admin_id, document_id, notification_type, message)
            VALUES (?, ?, 'establishment_approved', ?)
        ");
        $notif_msg = "Your document \"" . $document['original_filename'] . "\" has been approved by Establishment and is now with the Registrar for final approval.";
        $notif_stmt->execute([$document['admin_id'], $document_id, $notif_msg]);
        
        $_SESSION['success'] = 'Document approved and sent to Registrar for review';
    } else {
        $_SESSION['error'] = 'Failed to approve document';
    }
    
    header('Location: establishment_approve_documents.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reject' && $document_id) {
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    $rejection_comments = $_POST['rejection_comments'] ?? '';
    
    if (empty($rejection_reason)) {
        $_SESSION['error'] = 'Please provide a rejection reason';
        header('Location: establishment_approve_documents.php?action=reject&id=' . $document_id);
        exit;
    }
    
    $document = $db->fetchOne("SELECT * FROM document_submissions WHERE id = ?", [$document_id]);
    
    if (!$document) {
        $_SESSION['error'] = 'Document not found';
        header('Location: establishment_approve_documents.php');
        exit;
    }
    
    $update = $db->prepare("
        UPDATE document_submissions 
        SET approval_status = 'rejected',
            rejection_reason = ?,
            establishment_comments = ?,
            rejected_by_admin_id = ?,
            rejected_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($update->execute([$rejection_reason, $rejection_comments, $admin_id, $document_id])) {
        $notif_stmt = $db->prepare("
            INSERT INTO document_notifications (admin_id, document_id, notification_type, message)
            VALUES (?, ?, 'rejected', ?)
        ");
        $notif_msg = "Your document \"" . $document['original_filename'] . "\" has been rejected by Establishment. Reason: " . $rejection_reason;
        $notif_stmt->execute([$document['admin_id'], $document_id, $notif_msg]);
        
        $user_info = $db->fetchOne("SELECT official_email, firstname, surname FROM admin_users WHERE id = ?", [$document['admin_id']]);
        if ($user_info && !empty($user_info['official_email'])) {
            $email_service = new EmailService();
            $user_name = $user_info['firstname'] . ' ' . $user_info['surname'];
            $email_service->sendDocumentRejectedEmail(
                $user_info['official_email'],
                $user_name,
                $document['original_filename'],
                $rejection_reason,
                $rejection_comments
            );
        }
        
        $_SESSION['success'] = 'Document rejected and user notified via email';
    } else {
        $_SESSION['error'] = 'Failed to reject document';
    }
    
    header('Location: establishment_approve_documents.php');
    exit;
}

$pageTitle = 'Document Approvals (Establishment)';
require_once 'includes/head.php';

$where_search = '';
$params = [];
if (!empty($search_query)) {
    $where_search = "AND ds.original_filename LIKE ?";
    $params[] = '%' . $search_query . '%';
}

$submissions = $db->fetchAll("
    SELECT 
        ds.*,
        au.firstname,
        au.surname,
        au.staff_id,
        au.official_email
    FROM document_submissions ds
    JOIN admin_users au ON ds.admin_id = au.id
    WHERE ds.current_stage = 'establishment' AND ds.approval_status = 'pending' $where_search
    ORDER BY ds.created_at DESC
", $params);

if ($action === 'view' && $document_id) {
    $document = $db->fetchOne("
        SELECT 
            ds.*,
            au.firstname,
            au.surname,
            au.staff_id,
            au.official_email
        FROM document_submissions ds
        JOIN admin_users au ON ds.admin_id = au.id
        WHERE ds.id = ?
    ", [$document_id]);
    
    if (!$document) {
        $_SESSION['error'] = 'Document not found';
        header('Location: establishment_approve_documents.php');
        exit;
    }
    ?>
    
    <div class="max-w-4xl mx-auto">
        <div class="card mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-check text-blue-600 mr-2"></i>Document Review
                </h2>
                <a href="establishment_approve_documents.php" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b">
                <div>
                    <p class="text-gray-600 text-sm">Submitted By</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($document['firstname'] . ' ' . $document['surname']); ?></p>
                    <p class="text-gray-700 text-sm"><?php echo htmlspecialchars($document['staff_id']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($document['official_email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Document Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($document['original_filename']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Submitted Date</p>
                    <p class="font-semibold"><?php echo date('M d, Y H:i', strtotime($document['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="mb-6 pb-6 border-b">
                <h3 class="text-lg font-semibold mb-3">Document Preview</h3>
                <?php
                $file_type = $document['file_type'];
                $file_path = $document['file_path'];
                
                if (strpos($file_type, 'image') !== false) {
                    echo '<img src="' . htmlspecialchars($file_path) . '" alt="Document" style="max-height: 500px; max-width: 100%;" class="rounded border">';
                } elseif ($file_type === 'application/pdf') {
                    echo '<iframe src="' . htmlspecialchars($file_path) . '" style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 4px;"></iframe>';
                } else {
                    echo '<p class="text-gray-600">Preview not available for this file type</p>';
                }
                ?>
            </div>
            
            <form method="POST" action="?action=approve&id=<?php echo $document_id; ?>" class="mb-6">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-comment text-blue-600 mr-2"></i>Approval Comments (Optional)
                    </label>
                    <textarea name="approval_comments" class="input-field h-24" placeholder="Add any comments or notes about this approval..."></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="btn-primary" onclick="return confirm('Are you sure you want to approve this document? It will be sent to the Registrar.');">
                        <i class="fas fa-check mr-2"></i>Approve Document
                    </button>
                    <a href="establishment_approve_documents.php?action=reject&id=<?php echo $document_id; ?>" class="btn-danger">
                        <i class="fas fa-times mr-2"></i>Reject Document
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
} elseif ($action === 'reject' && $document_id) {
    $document = $db->fetchOne("SELECT * FROM document_submissions WHERE id = ?", [$document_id]);
    
    if (!$document) {
        $_SESSION['error'] = 'Document not found';
        header('Location: establishment_approve_documents.php');
        exit;
    }
    ?>
    
    <div class="max-w-2xl mx-auto">
        <div class="card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>Reject Document
                </h2>
                <a href="establishment_approve_documents.php" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Cancel
                </a>
            </div>
            
            <form method="POST" action="?action=reject&id=<?php echo $document_id; ?>">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-ban text-red-600 mr-2"></i>Rejection Reason *
                    </label>
                    <textarea name="rejection_reason" required class="input-field h-24" placeholder="Explain why this document is being rejected..."></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-comment text-blue-600 mr-2"></i>Additional Comments (Optional)
                    </label>
                    <textarea name="rejection_comments" class="input-field h-20" placeholder="Provide constructive feedback on what needs to be corrected..."></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to reject this document?');">
                        <i class="fas fa-check mr-2"></i>Confirm Rejection
                    </button>
                    <a href="establishment_approve_documents.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
} else {
    ?>
    
    <div class="max-w-6xl mx-auto">
        <div class="card mb-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-file-check text-blue-600 mr-2"></i>Document Approvals (Establishment)
                </h2>
                <p class="text-gray-600">Review and approve documents submitted by staff</p>
            </div>
            
            <?php
            if (!empty($_SESSION['success'])) {
                echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-700">' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (!empty($_SESSION['error'])) {
                echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-700">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <div class="mb-6">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" placeholder="Search by document name..." value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="establishment_approve_documents.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($submissions)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600 font-semibold">No pending documents for approval</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="px-4 py-3 text-left">Staff Name</th>
                                <th class="px-4 py-3 text-left">Document</th>
                                <th class="px-4 py-3 text-left">File Type</th>
                                <th class="px-4 py-3 text-left">Size</th>
                                <th class="px-4 py-3 text-left">Submitted</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $doc): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <strong><?php echo htmlspecialchars($doc['firstname'] . ' ' . $doc['surname']); ?></strong>
                                        <br><small class="text-gray-600"><?php echo htmlspecialchars($doc['staff_id']); ?></small>
                                    </td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($doc['original_filename']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                            <?php echo htmlspecialchars($doc['file_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?php echo number_format($doc['file_size'] / 1024, 2) . ' KB'; ?></td>
                                    <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?action=view&id=<?php echo $doc['id']; ?>" class="btn-sm btn-primary">
                                            <i class="fas fa-eye mr-1"></i>Review
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
    
    <?php
}
?>
