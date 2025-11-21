<?php
$pageTitle = 'View Memo';
require_once 'includes/head.php';

if (!isset($_GET['id'])) {
    header('Location: ' . ($userRole === 'superadmin' ? 'manage_memos.php' : 'view_received_memos.php'));
    exit;
}

$memo_id = intval($_GET['id']);
$can_view = false;
$memo = null;

try {
    $db = Database::getInstance();
    
    // Check permissions based on user role
    if ($userRole === 'superadmin') {
        // Super admin can view their own sent memos
        $memo = $db->fetchOne(
            "SELECT m.*, u.firstname, u.lastname FROM memos m 
             JOIN users u ON m.sender_id = u.id 
             WHERE m.id = ? AND m.sender_id = ?",
            [$memo_id, $_SESSION['user_id']]
        );
    } else {
        // Admin can view memos sent to them
        $memo = $db->fetchOne(
            "SELECT m.*, u.firstname, u.lastname FROM memos m 
             JOIN users u ON m.sender_id = u.id 
             JOIN memo_recipients mr ON m.id = mr.memo_id 
             WHERE m.id = ? AND mr.admin_id = ?",
            [$memo_id, $_SESSION['admin_id']]
        );
        
        // Mark as read
        if ($memo) {
            $db->query(
                "UPDATE memo_recipients SET read_at = CURRENT_TIMESTAMP WHERE memo_id = ? AND admin_id = ? AND read_at IS NULL",
                [$memo_id, $_SESSION['admin_id']]
            );
        }
    }
    
    $can_view = $memo !== null;
} catch (Exception $e) {
    error_log($e->getMessage());
}

if (!$can_view) {
    echo '<div class="container mx-auto mt-10"><div class="card bg-red-50 text-red-600"><p>Memo not found or access denied</p></div></div>';
    require_once 'includes/foot.php';
    exit;
}
?>

<div class="container mx-auto">
    <!-- Memo Header -->
    <div class="card bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900 dark:to-indigo-900 mb-6">
        <div class="flex justify-between items-start gap-4">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2"><?php echo htmlspecialchars($memo['title']); ?></h1>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    <i class="fas fa-user mr-2"></i>From: <strong><?php echo htmlspecialchars($memo['firstname'] . ' ' . $memo['lastname']); ?></strong>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-calendar mr-2"></i><?php echo date('F j, Y \a\t g:i A', strtotime($memo['created_at'])); ?>
                </p>
            </div>
            <a href="<?php echo htmlspecialchars($memo['file_path']); ?>" download class="btn-primary whitespace-nowrap">
                <i class="fas fa-download mr-2"></i>Download
            </a>
        </div>
    </div>

    <!-- Memo Description -->
    <?php if ($memo['description']): ?>
        <div class="card mb-6 bg-gray-50 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Description</h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($memo['description']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Memo Content Preview -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
            <i class="fas fa-file-alt mr-2"></i>Document Preview
        </h3>

        <?php
        $file_path = $memo['file_path'];
        $file_type = $memo['file_type'];
        
        if (file_exists($file_path)):
            if (in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])): ?>
                <!-- Image Preview -->
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Memo Image" class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg">
                </div>
            <?php elseif ($file_type === 'application/pdf'): ?>
                <!-- PDF Viewer -->
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                    <embed src="<?php echo htmlspecialchars($file_path); ?>#toolbar=1&navpanes=0&scrollbar=1" 
                           type="application/pdf" 
                           class="w-full" 
                           style="height: 600px;">
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                    <i class="fas fa-info-circle mr-2"></i>If the PDF viewer doesn't load, please <a href="<?php echo htmlspecialchars($file_path); ?>" download class="text-blue-600 dark:text-blue-400 hover:underline">download the file</a>.
                </p>
            <?php elseif (in_array($file_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])): ?>
                <!-- Word Document -->
                <div class="bg-blue-50 dark:bg-blue-900 border-2 border-blue-200 dark:border-blue-700 rounded-lg p-6 text-center">
                    <i class="fas fa-file-word text-4xl text-blue-600 dark:text-blue-400 mb-3"></i>
                    <p class="text-gray-700 dark:text-gray-300 font-semibold mb-3">Word Document Preview</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <?php 
                        $type_label = $file_type === 'application/msword' ? 'Microsoft Word 97-2003' : 'Microsoft Word (DOCX)';
                        echo $type_label;
                        ?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Word documents cannot be previewed in browser. Please download to view the full document.
                    </p>
                    <a href="<?php echo htmlspecialchars($file_path); ?>" download class="btn-primary inline-block">
                        <i class="fas fa-download mr-2"></i>Download Word Document
                    </a>
                </div>
            <?php else: ?>
                <!-- Unknown File Type -->
                <div class="bg-gray-50 dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center">
                    <i class="fas fa-file text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-700 dark:text-gray-300 font-semibold mb-3">File Preview Not Available</p>
                    <a href="<?php echo htmlspecialchars($file_path); ?>" download class="btn-primary inline-block">
                        <i class="fas fa-download mr-2"></i>Download File
                    </a>
                </div>
            <?php endif;
        else: ?>
            <div class="bg-red-50 dark:bg-red-900 border border-red-300 dark:border-red-700 rounded-lg p-4 text-center">
                <i class="fas fa-exclamation-triangle text-2xl text-red-600 dark:text-red-400 mb-2"></i>
                <p class="text-red-700 dark:text-red-300">File not found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Back Button -->
    <div class="mt-6 text-center">
        <a href="<?php echo $userRole === 'superadmin' ? 'manage_memos.php' : 'view_received_memos.php'; ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
