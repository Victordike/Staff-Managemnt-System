<?php
$pageTitle = 'Upload Memo';
require_once 'includes/head.php';

// Check if user is superadmin
if ($userRole !== 'superadmin') {
    http_response_code(403);
    die('Access denied');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $recipient_type = sanitize($_POST['recipient_type'] ?? 'all');
    $recipient_id = null;
    
    if ($recipient_type === 'single') {
        $recipient_id = !empty($_POST['recipient_id']) ? intval($_POST['recipient_id']) : null;
    }
    
    if (!$title || !isset($_FILES['memo_file'])) {
        $error = 'Title and file are required';
    } elseif ($recipient_type === 'single' && !$recipient_id) {
        $error = 'Please select a staff member when sending to a specific person';
    } elseif ($_FILES['memo_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['memo_file']['type'];
        $file_size = $_FILES['memo_file']['size'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Only JPG, PNG, PDF, and Word files are allowed';
        } elseif ($file_size > $max_size) {
            $error = 'File size must not exceed 10MB';
        } else {
            // Check blur for images
            $blur_detected = 0;
            if (in_array($file_type, ['image/jpeg', 'image/png'])) {
                $blur_status = detectBlurInImage($_FILES['memo_file']['tmp_name']);
                $blur_detected = $blur_status ? 1 : 0;
                if ($blur_detected) {
                    $error = 'The image text appears too blurry. Please provide a clearer image.';
                }
            }
            
            if (!$error) {
                $ext = pathinfo($_FILES['memo_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/memos/' . $filename;
                
                if (move_uploaded_file($_FILES['memo_file']['tmp_name'], $upload_path)) {
                    try {
                        $db = Database::getInstance();
                        
                        // Insert memo
                        $db->query(
                            "INSERT INTO memos (sender_id, title, description, file_path, file_type, recipient_type, recipient_id, blur_detected) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id",
                            [$_SESSION['user_id'], $title, $description, $upload_path, $file_type, $recipient_type, $recipient_id, $blur_detected]
                        );
                        
                        $result = $db->fetchOne(
                            "SELECT id FROM memos WHERE file_path = ? ORDER BY created_at DESC LIMIT 1",
                            [$upload_path]
                        );
                        $memo_id = $result['id'] ?? null;
                        
                        if ($memo_id) {
                            // Add recipients
                            if ($recipient_type === 'all') {
                                $admins = $db->fetchAll("SELECT id FROM admin_users WHERE is_active = true");
                                foreach ($admins as $admin) {
                                    $db->query(
                                        "INSERT INTO memo_recipients (memo_id, admin_id) VALUES (?, ?)",
                                        [$memo_id, $admin['id']]
                                    );
                                }
                            } else if ($recipient_id) {
                                $db->query(
                                    "INSERT INTO memo_recipients (memo_id, admin_id) VALUES (?, ?)",
                                    [$memo_id, $recipient_id]
                                );
                            }
                            
                            $success = 'Memo uploaded and sent successfully!';
                            $_POST = [];
                        } else {
                            throw new Exception('Failed to retrieve memo ID after insertion');
                        }
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                        unlink($upload_path);
                    }
                } else {
                    $error = 'Failed to upload file';
                }
            }
        }
    }
}

// Get list of active admins
try {
    $db = Database::getInstance();
    $admins = $db->fetchAll("SELECT id, firstname, surname, official_email FROM admin_users WHERE is_active = true ORDER BY firstname");
} catch (Exception $e) {
    $admins = [];
}
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Upload Memo</h2>
        <p class="text-gray-600 dark:text-gray-300">Send official memos to staff members</p>
    </div>

    <?php if ($error): ?>
        <div class="card mb-6 bg-red-50 dark:bg-red-900 border border-red-300 dark:border-red-700">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-bold text-red-800 dark:text-red-200">Error</h3>
                    <p class="text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="card mb-6 bg-green-50 dark:bg-green-900 border border-green-300 dark:border-green-700">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-bold text-green-800 dark:text-green-200">Success</h3>
                    <p class="text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-xl">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-2"></i>
                    Image quality check: Blurry text will be detected and rejected automatically
                </p>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Memo Title *</label>
                <input type="text" name="title" class="input-field w-full" placeholder="e.g., Staff Meeting Announcement" required>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Description (Optional)</label>
                <textarea name="description" class="input-field w-full" rows="3" placeholder="Add a brief description of the memo..."></textarea>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Upload File *</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-blue-500 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">Click to select or drag and drop</p>
                    <input type="file" name="memo_file" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer" required>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">PNG, JPG, PDF, or Word (Max 10MB)</p>
                </div>
            </div>

            <!-- Recipient Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-3">Send To *</label>
                    <div class="space-y-3">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="recipient_type" value="all" checked class="form-radio">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">All Active Staff Members</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="recipient_type" value="single" class="form-radio">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">Specific Staff Member</span>
                        </label>
                    </div>
                </div>

                <!-- Recipient Dropdown -->
                <div id="singleRecipient" class="hidden">
                    <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Select Staff Member *</label>
                    <select name="recipient_id" class="input-field w-full">
                        <option value="">-- Choose a staff member --</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>">
                                <?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['surname'] . ' (' . $admin['official_email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-3 pt-4">
                <a href="superadmin_dashboard.php" class="btn-secondary flex-1 text-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-paper-plane mr-2"></i>Send Memo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('singleRecipient').classList.toggle('hidden', this.value === 'all');
    });
});
</script>

<?php require_once 'includes/foot.php'; ?>
