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
            "SELECT m.*, au.firstname, au.surname as lastname FROM memos m 
             JOIN admin_users au ON m.sender_id = au.id 
             WHERE m.id = ?",
            [$memo_id]
        );
    } else {
        // Admin can view memos sent to them
        $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
        if ($admin_id) {
            $memo = $db->fetchOne(
                "SELECT m.*, au.firstname, au.surname as lastname FROM memos m 
                 JOIN admin_users au ON m.sender_id = au.id 
                 JOIN memo_recipients mr ON m.id = mr.memo_id 
                 WHERE m.id = ? AND mr.recipient_id = ?",
                [$memo_id, $admin_id]
            );
            
            // Mark as read
            if ($memo) {
                $db->query(
                    "UPDATE memo_recipients SET read_at = CURRENT_TIMESTAMP WHERE memo_id = ? AND recipient_id = ? AND read_at IS NULL",
                    [$memo_id, $admin_id]
                );
            }
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
        
        <?php if ($memo['status'] === 'rejected'): ?>
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
                <h3 class="font-bold text-red-800 dark:text-red-200">
                    <i class="fas fa-times-circle mr-2"></i>Memo Rejected
                </h3>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                    <strong>Reason:</strong> <?php echo htmlspecialchars($memo['rejection_reason']); ?>
                </p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                    Rejected on <?php echo date('F j, Y \a\t g:i A', strtotime($memo['rejected_at'])); ?>
                </p>
            </div>
        <?php elseif ($memo['current_stage'] !== 'completed' && $memo['recipient_id'] == ($_SESSION['admin_id'] ?? $_SESSION['user_id'])): ?>
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h3 class="font-bold text-yellow-800 dark:text-yellow-200">Action Required</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">This memo requires your approval or rejection (Current stage: <?php echo str_replace('_', ' ', $memo['current_stage']); ?>).</p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button onclick="forwardMemo(<?php echo $memo['id']; ?>)" class="btn-primary bg-green-600 hover:bg-green-700 border-none flex-1">
                        <i class="fas fa-check mr-2"></i>Approve & Forward
                    </button>
                    <button onclick="showRejectModal()" class="btn-primary bg-red-600 hover:bg-red-700 border-none flex-1">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                </div>
            </div>
        <?php endif; ?>
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
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden mb-4">
                    <embed src="<?php echo htmlspecialchars($file_path); ?>#toolbar=1&navpanes=0&scrollbar=1" 
                           type="application/pdf" 
                           class="w-full" 
                           style="height: 600px;">
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-2"></i>If the PDF viewer doesn't load, please <a href="<?php echo htmlspecialchars($file_path); ?>" download class="text-blue-600 dark:text-blue-400 hover:underline">download the file</a>.
                </p>
            <?php elseif (in_array($file_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])): ?>
                <!-- Word Document Text Preview -->
                <?php 
                $word_text = null;
                if ($file_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $word_text = extractWordDocumentText($file_path);
                }
                ?>
                <?php if ($word_text): ?>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-4 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-sm leading-relaxed">
                            <?php echo htmlspecialchars($word_text); ?>
                        </p>
                    </div>
                    <div class="text-center">
                        <a href="<?php echo htmlspecialchars($file_path); ?>" download class="btn-secondary inline-block">
                            <i class="fas fa-download mr-2"></i>Download Full Document
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-blue-50 dark:bg-blue-900 border-2 border-blue-200 dark:border-blue-700 rounded-lg p-6 text-center">
                        <i class="fas fa-file-word text-4xl text-blue-600 dark:text-blue-400 mb-3"></i>
                        <p class="text-gray-700 dark:text-gray-300 font-semibold mb-3">Word Document</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <?php 
                            $type_label = $file_type === 'application/msword' ? 'Microsoft Word 97-2003' : 'Microsoft Word (DOCX)';
                            echo $type_label;
                            ?>
                        </p>
                        <a href="<?php echo htmlspecialchars($file_path); ?>" download class="btn-primary inline-block">
                            <i class="fas fa-download mr-2"></i>Download Document
                        </a>
                    </div>
                <?php endif; ?>
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

<!-- Rejection Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Reject Memo</h3>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason for Rejection *</label>
            <textarea id="rejectionReason" class="input-field w-full h-32" placeholder="Explain why this memo is being rejected..."></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <button onclick="hideRejectModal()" class="btn-secondary">Cancel</button>
            <button onclick="submitRejection(<?php echo $memo['id']; ?>)" id="confirmRejectBtn" class="btn-primary bg-red-600 hover:bg-red-700 border-none">
                Confirm Rejection
            </button>
        </div>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

function submitRejection(memoId) {
    const reason = document.getElementById('rejectionReason').value.trim();
    if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    
    const btn = document.getElementById('confirmRejectBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Rejecting...';
    
    const formData = new FormData();
    formData.append('memo_id', memoId);
    formData.append('reason', reason);
    
    fetch('api/reject_memo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function forwardMemo(memoId) {
    if (!confirm('Are you sure you want to forward this memo to the next stage?')) return;
    
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Forwarding...';
    
    const formData = new FormData();
    formData.append('memo_id', memoId);
    
    fetch('api/forward_memo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

<?php require_once 'includes/foot.php'; ?>
