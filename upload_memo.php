<?php
$pageTitle = 'Upload Memo';
require_once 'includes/head.php';
require_once 'includes/blur_detection.php';

// Check if user is superadmin or admin
if ($userRole !== 'superadmin' && $userRole !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

$error = '';
$success = '';

try {
    $db = Database::getInstance();
    $sender_id = $_SESSION['user_id'] ?? null;
    
    // Check if sender has any institutional role (HOD, Dean, Rector, etc.)
    $functional_roles = getUserRoles($sender_id, $db);
    $is_management = !empty($functional_roles) || $userRole === 'superadmin';
} catch (Exception $e) {
    $is_management = false;
    $db = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $recipient_type = sanitize($_POST['recipient_type'] ?? ($is_management ? 'all' : 'single'));
    $recipient_id = null;
    $final_recipient_id = null;
    $current_stage = 'completed';
    $is_approved = 1;
    $enable_validation = isset($_POST['enable_validation']) ? true : false;
    $required_text = sanitize($_POST['required_text'] ?? '');
    
    if (!$db) $db = Database::getInstance();
    $sender_id = $_SESSION['user_id'];

    if ($recipient_type === 'single') {
        $final_recipient_id = !empty($_POST['recipient_id']) ? intval($_POST['recipient_id']) : null;
    }
    
    if (!$title || !isset($_FILES['memo_file'])) {
        $error = 'Title and file are required';
    } elseif ($recipient_type === 'single' && !$final_recipient_id) {
        $error = 'Please select a staff member when sending to a specific person';
    } elseif ($recipient_type === 'faculty' && empty($_POST['faculty_name'])) {
        $error = 'Please select a faculty';
    } elseif ($recipient_type === 'department' && empty($_POST['department_name'])) {
        $error = 'Please select a department';
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
            
            // Validate memo content if enabled
            if (!$error && $enable_validation && !empty($required_text)) {
                $validation_result = validateMemoContent($_FILES['memo_file']['tmp_name'], $file_type, $required_text);
                if (!$validation_result['valid']) {
                    $error = $validation_result['message'];
                }
            }
            
            if (!$error) {
                $ext = pathinfo($_FILES['memo_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/memos/' . $filename;
                
                if (move_uploaded_file($_FILES['memo_file']['tmp_name'], $upload_path)) {
                    try {
                        // Handle Routing for Admin Users (for single recipient, regardless of management status)
                        if ($userRole === 'admin' && $recipient_type === 'single') {
                            list($recipient_id, $current_stage, $is_approved) = calculateMemoRouting($sender_id, $final_recipient_id, $db);
                        } else {
                            $recipient_id = ($recipient_type === 'single') ? $final_recipient_id : null;
                        }

                        // Insert memo
                        $db->query(
                            "INSERT INTO memos (sender_id, title, description, file_path, file_type, recipient_type, recipient_id, final_recipient_id, current_stage, is_approved, blur_detected) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$sender_id, $title, $description, $upload_path, $file_type, $recipient_type, $recipient_id, $final_recipient_id, $current_stage, $is_approved, $blur_detected]
                        );
                        
                        $memo_id = $db->lastInsertId();
                        
                        if ($memo_id) {
                            // Add recipients based on type
                            $recipients = [];
                            
                            if ($recipient_type === 'all') {
                                $recipients = getAllStaff($db);
                            } else if ($recipient_type === 'all_deans') {
                                $recipients = getAllDeans($db);
                            } else if ($recipient_type === 'all_hods') {
                                $recipients = getAllHODs($db);
                            } else if ($recipient_type === 'faculty') {
                                $recipients = getStaffByFaculty($_POST['faculty_name'], $db);
                            } else if ($recipient_type === 'department') {
                                $recipients = getStaffByDepartment($_POST['department_name'], $db);
                            } else if ($recipient_id) {
                                // For routed memos, only the FIRST recipient in the chain gets it initially
                                $recipients = [['id' => $recipient_id]];
                            }
                            
                            foreach ($recipients as $r) {
                                $db->query(
                                    "INSERT IGNORE INTO memo_recipients (memo_id, recipient_id) VALUES (?, ?)",
                                    [$memo_id, $r['id']]
                                );
                            }
                            
                            $success = 'Memo uploaded ' . ($is_approved ? 'and sent' : 'and forwarded for approval') . ' successfully!';
                            $_POST = [];
                        } else {
                            throw new Exception('Failed to retrieve memo ID after insertion');
                        }
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                        if (file_exists($upload_path)) unlink($upload_path);
                    }
                } else {
                    $error = 'Failed to upload file';
                }
            }
        }
    }
}

// Get list of active admins and faculty/dept data
try {
    if (!$db) $db = Database::getInstance();
    
    // Get sender's department for reference
    $sender_data = $db->fetchOne("SELECT department FROM admin_users WHERE id = ?", [$sender_id]);
    $user_dept = $sender_data['department'] ?? 'Unknown';

    $dept_hod_id = null;
    $dept_hod_data = null;
    
    if (!$is_management) {
        $dept_hod_id = getHOD($user_dept, $db);
        if ($dept_hod_id) {
            $dept_hod_data = $db->fetchOne("SELECT id, firstname, surname, official_email FROM admin_users WHERE id = ?", [$dept_hod_id]);
        }
    }

    if ($is_management) {
        $admins = $db->fetchAll("SELECT id, firstname, surname, official_email FROM admin_users WHERE is_active = true ORDER BY firstname");
    } else {
        // For non-management users, show all staff so they can select a final recipient
        // The routing logic in calculateMemoRouting will handle the "through" steps automatically
        $admins = $db->fetchAll("SELECT id, firstname, surname, official_email FROM admin_users WHERE id != ? AND is_active = true ORDER BY firstname", [$sender_id]);
    }
    
    $faculties_data = getFacultiesAndDepartments();
} catch (Exception $e) {
    $admins = [];
    $faculties_data = [];
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

            <!-- Content Validation Section -->
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                <div class="flex items-start">
                    <input type="checkbox" id="enableValidation" name="enable_validation" class="mt-1 cursor-pointer" onchange="toggleValidationInput()">
                    <div class="ml-3 flex-1">
                        <label for="enableValidation" class="text-sm font-semibold text-purple-800 dark:text-purple-200 cursor-pointer">
                            <i class="fas fa-shield-alt mr-2"></i>Enable Content Validation
                        </label>
                        <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                            Ensure specific text/criteria exists in PDFs and Word documents. Images will be allowed through without validation.
                        </p>
                    </div>
                </div>
                <div id="validationInput" class="hidden mt-4">
                    <label class="block text-sm font-semibold text-purple-800 dark:text-purple-200 mb-2">
                        <i class="fas fa-search mr-2"></i>Required Text to Find
                    </label>
                    <input type="text" name="required_text" class="input-field w-full" placeholder="e.g., INTERNAL MEMORANDUM" id="requiredTextInput">
                    <p class="text-xs text-purple-700 dark:text-purple-300 mt-2">
                        The memo will only be sent if this text is found in PDF or Word documents (case-insensitive). Images bypass validation.
                    </p>
                </div>
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
            <div>
                <?php if ($is_management): ?>
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
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="recipient_type" value="all_deans" class="form-radio">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">All Deans</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="recipient_type" value="all_hods" class="form-radio">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">All HODs</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="recipient_type" value="faculty" class="form-radio">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Specific Faculty</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="recipient_type" value="department" class="form-radio">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Specific Department</span>
                                </label>
                            </div>
                        </div>

                        <!-- Conditional Dropdowns -->
                        <div class="space-y-4">
                            <!-- Staff Dropdown -->
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

                            <!-- Faculty Dropdown -->
                            <div id="facultyRecipient" class="hidden">
                                <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Select Faculty *</label>
                                <select name="faculty_name" class="input-field w-full">
                                    <option value="">-- Choose a faculty --</option>
                                    <?php foreach (array_keys($faculties_data) as $faculty): ?>
                                        <option value="<?php echo htmlspecialchars($faculty); ?>">
                                            <?php echo htmlspecialchars($faculty); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Department Dropdown -->
                            <div id="departmentRecipient" class="hidden">
                                <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Select Department *</label>
                                <select name="department_name" class="input-field w-full">
                                    <option value="">-- Choose a department --</option>
                                    <?php foreach ($faculties_data as $faculty => $departments): ?>
                                        <optgroup label="<?php echo htmlspecialchars($faculty); ?>">
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                                    <?php echo htmlspecialchars($dept); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Regular Staff Recipient Selection -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-xl border border-gray-200 dark:border-gray-700">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-4">
                            <i class="fas fa-paper-plane mr-2 text-blue-500"></i>Recipient (Department HOD) *
                        </label>
                        <div class="space-y-4">
                            <?php if ($dept_hod_data): ?>
                                <div class="flex items-center p-4 bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-900 shadow-sm">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 mr-4">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($dept_hod_data['firstname'] . ' ' . $dept_hod_data['surname']); ?></p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($dept_hod_data['official_email']); ?></p>
                                    </div>
                                    <input type="hidden" name="recipient_id" value="<?php echo $dept_hod_data['id']; ?>">
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-800">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    No HOD found for your department (<?php echo htmlspecialchars($user_dept); ?>). Please contact the administrator.
                                </div>
                                <select name="recipient_id" class="hidden" required></select>
                            <?php endif; ?>

                            <input type="hidden" name="recipient_type" value="single">
                            
                            <div class="flex items-start gap-3 mt-4 text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                                <i class="fas fa-info-circle mt-1 text-blue-500"></i>
                                <div>
                                    <p class="font-semibold text-blue-800 dark:text-blue-300">Direct Departmental Submission</p>
                                    <p>As a staff member, your memos are restricted to your Departmental Head (HOD/Unit Head). All official communications must be channeled through your HOD.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-3 pt-4">
                <a href="<?php echo $userRole === 'superadmin' ? 'superadmin_dashboard.php' : 'admin_dashboard.php'; ?>" class="btn-secondary flex-1 text-center">Cancel</a>
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
        document.getElementById('singleRecipient').classList.toggle('hidden', this.value !== 'single');
        document.getElementById('facultyRecipient').classList.toggle('hidden', this.value !== 'faculty');
        document.getElementById('departmentRecipient').classList.toggle('hidden', this.value !== 'department');
    });
});

function toggleValidationInput() {
    const enableCheckbox = document.getElementById('enableValidation');
    const validationInput = document.getElementById('validationInput');
    const requiredTextInput = document.getElementById('requiredTextInput');
    
    if (enableCheckbox.checked) {
        validationInput.classList.remove('hidden');
        requiredTextInput.focus();
    } else {
        validationInput.classList.add('hidden');
        requiredTextInput.value = '';
    }
}
</script>

<?php require_once 'includes/foot.php'; ?>
