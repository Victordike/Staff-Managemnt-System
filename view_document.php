<?php
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'View Document';
require_once 'includes/head.php';

$db = Database::getInstance();
$current_admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$doc_id = intval($_GET['id'] ?? 0);
$view_admin_id = intval($_GET['admin'] ?? $current_admin_id);

if (!$doc_id) {
    $redirect = ($userRole === 'superadmin') ? 'view_document_submissions.php' : 'upload_documents.php';
    header("Location: $redirect");
    exit;
}

$document = $db->fetchOne(
    "SELECT * FROM document_submissions WHERE id = ?",
    [$doc_id]
);

if (!$document) {
    $redirect = ($userRole === 'superadmin') ? 'view_document_submissions.php' : 'upload_documents.php';
    header("Location: $redirect");
    exit;
}

if ($userRole === 'superadmin') {
    $admin_info = $db->fetchOne(
        "SELECT au.*, COUNT(ar.id) as role_count FROM admin_users au 
         LEFT JOIN admin_roles ar ON au.id = ar.admin_id AND ar.removed_at IS NULL
         WHERE au.id = ? GROUP BY au.id",
        [$document['admin_id']]
    );
    
    if (!$admin_info || $admin_info['role_count'] > 0) {
        header('Location: view_document_submissions.php');
        exit;
    }
} else {
    if ($document['admin_id'] !== $current_admin_id) {
        header('Location: upload_documents.php');
        exit;
    }
}

$is_image = strpos($document['file_type'], 'image') !== false;
$is_pdf = $document['file_type'] === 'application/pdf';
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($document['original_filename']); ?>
                </h2>
                <p class="text-gray-600">
                    Uploaded: <?php echo date('F d, Y H:i', strtotime($document['created_at'])); ?> • 
                    Size: <?php echo round($document['file_size'] / 1024, 2); ?>KB
                </p>
            </div>
            <div class="flex gap-2">
                <a href="<?php echo htmlspecialchars($document['file_path']); ?>" download class="btn-primary">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
                <a href="<?php echo $userRole === 'superadmin' ? 'view_document_submissions.php' : 'upload_documents.php'; ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
        
        <div class="border rounded-lg p-6 bg-gray-50 mb-6">
            <?php if ($is_image): ?>
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($document['file_path']); ?>" alt="Document" class="max-w-full h-auto rounded-lg shadow-lg mx-auto" style="max-height: 600px;">
                </div>
            <?php elseif ($is_pdf): ?>
                <div class="text-center">
                    <iframe src="<?php echo htmlspecialchars($document['file_path']); ?>#toolbar=0" width="100%" height="600" style="border: none; border-radius: 0.5rem;"></iframe>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-500">
                    <i class="fas fa-file text-6xl mb-4"></i>
                    <p>Preview not available for this file type</p>
                    <a href="<?php echo htmlspecialchars($document['file_path']); ?>" download class="btn-primary mt-4">Download to view</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($document['approval_status'] !== 'pending' && $document['approval_status'] !== 'establishment_approved'): ?>
            <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-comments text-blue-600 mr-2"></i>Approval Feedback
                </h3>

                <div class="space-y-4">
                    <?php if ($document['approval_status'] === 'registrar_approved'): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-check-circle text-green-600 text-xl mt-1"></i>
                                <div class="flex-1">
                                    <p class="font-semibold text-green-800">Approved by Registrar</p>
                                    <p class="text-sm text-gray-600">Approved on <?php echo date('F d, Y', strtotime($document['registrar_approved_at'])); ?></p>
                                    <?php if ($document['registrar_comments']): ?>
                                        <div class="mt-2 bg-white p-3 rounded border-l-4 border-blue-500">
                                            <?php echo nl2br(htmlspecialchars($document['registrar_comments'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($document['approval_status'] === 'rejected'): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-times-circle text-red-600 text-xl mt-1"></i>
                                <div class="flex-1">
                                    <p class="font-semibold text-red-800">Document Rejected</p>
                                    <p class="text-sm text-gray-600">Rejected on <?php echo date('F d, Y', strtotime($document['rejected_at'])); ?></p>
                                    <p class="text-red-700 font-semibold mt-2">Reason: <?php echo htmlspecialchars($document['rejection_reason']); ?></p>
                                    <?php if ($document['establishment_comments']): ?>
                                        <div class="mt-2 bg-white p-3 rounded border-l-4 border-blue-500">
                                            <p class="text-sm text-gray-700 font-semibold mb-1">Feedback:</p>
                                            <?php echo nl2br(htmlspecialchars($document['establishment_comments'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($document['registrar_comments']): ?>
                                        <div class="mt-2 bg-white p-3 rounded border-l-4 border-purple-500">
                                            <p class="text-sm text-gray-700 font-semibold mb-1">Registrar Feedback:</p>
                                            <?php echo nl2br(htmlspecialchars($document['registrar_comments'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <a href="resubmit_document.php?id=<?php echo $document['id']; ?>" class="btn-primary inline-block">
                            <i class="fas fa-redo mr-2"></i>Resubmit Document
                        </a>
                    <?php endif; ?>

                    <?php if ($document['establishment_comments'] && $document['approval_status'] === 'registrar_approved'): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-comment text-yellow-600 mr-2"></i>Establishment Comments
                            </p>
                            <div class="bg-white p-3 rounded border-l-4 border-blue-500">
                                <?php echo nl2br(htmlspecialchars($document['establishment_comments'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
