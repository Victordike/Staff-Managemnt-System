<?php
require_once 'includes/functions.php';
requireAdmin();

$pageTitle = 'Document Upload';
require_once 'includes/head.php';

$db = Database::getInstance();
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$success = '';
$error = '';

try {
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE document_submissions ADD COLUMN IF NOT EXISTS document_category VARCHAR(100) DEFAULT NULL AFTER file_size");
} catch (Exception $e) {
    error_log("Column addition attempt: " . $e->getMessage());
}

$document_categories = [
    'Academic' => [
        'admission_letter' => 'Admission Letter',
        'transcript' => 'Transcript of Records',
        'degree_certificate' => 'Degree Certificate',
        'course_completion' => 'Course Completion Certificate',
        'enrollment_proof' => 'Proof of Enrollment',
        'attendance_records' => 'Attendance Records',
        'exam_results' => 'Examination Results',
        'academic_performance' => 'Academic Performance Report',
        'letter_of_standing' => 'Letter of Good Standing'
    ],
    'Administrative' => [
        'student_id' => 'Student ID Card',
        'matriculation' => 'Matriculation Certificate',
        'identification' => 'Identification Number Proof',
        'birth_certificate' => 'Birth Certificate',
        'national_id' => 'National ID/Passport',
        'proof_address' => 'Proof of Address',
        'course_change_form' => 'Change of Course Form',
        'registration_form' => 'Student Registration Form',
        'registration_proof' => 'Course Registration Proof'
    ],
    'Financial' => [
        'payment_receipt' => 'Tuition Payment Receipt',
        'bursar_clearance' => 'Bursar\'s Clearance Certificate',
        'fee_exemption' => 'Fee Exemption Document',
        'scholarship_letter' => 'Scholarship Award Letter',
        'financial_aid' => 'Financial Aid Letter',
        'proof_payment' => 'Proof of Payment',
        'invoice' => 'Invoice/Billing Statement',
        'account_statement' => 'Student Account Statement'
    ],
    'Professional/Certification' => [
        'professional_cert' => 'Professional Certification (HND, OND)',
        'professional_license' => 'Professional License/Registration',
        'continuing_education' => 'Continuing Education Certificate',
        'dev_record' => 'Professional Development Record',
        'internship_cert' => 'Internship/Apprenticeship Certificate',
        'skills_training' => 'Skills Training Certificate'
    ],
    'Staff/Employment' => [
        'employment_letter' => 'Employment Letter',
        'appointment_letter' => 'Appointment Letter',
        'promotion_cert' => 'Promotion Certificate',
        'staff_id' => 'Staff ID Card',
        'clearance_cert' => 'Clearance Certificate',
        'performance_appraisal' => 'Performance Appraisal',
        'leave_approval' => 'Leave Approval Document',
        'qualification_cert' => 'Professional Qualification Certificate'
    ],
    'Medical/Health' => [
        'medical_exam' => 'Medical Examination Report',
        'vaccination_cert' => 'Vaccination Certificate',
        'health_clearance' => 'Health Clearance Letter',
        'disability_cert' => 'Disability Certificate'
    ],
    'Support Documents' => [
        'recommendation_letter' => 'Letter of Recommendation',
        'reference_letter' => 'Reference Letter',
        'character_reference' => 'Character Reference',
        'parental_consent' => 'Parental Consent Form',
        'nok_document' => 'Next of Kin Document',
        'affidavit' => 'Affidavit',
        'statutory_declaration' => 'Statutory Declaration'
    ],
    'Programme-Specific' => [
        'project_report' => 'Project/Thesis Report',
        'capstone_project' => 'Capstone Project Document',
        'training_report' => 'Industrial Training Report',
        'placement_cert' => 'Work Placement Certificate',
        'field_study' => 'Field Study Report',
        'research_publication' => 'Research Publication'
    ],
    'Legal/Compliance' => [
        'consent_form' => 'Signed Consent Form',
        'data_protection' => 'Data Protection Agreement',
        'code_of_conduct' => 'Code of Conduct Acknowledgment',
        'plagiarism_declaration' => 'Anti-Plagiarism Declaration',
        'copyright_assignment' => 'Copyright Assignment Form'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    $files = $_FILES['documents'];
    $uploaded_count = 0;
    $failed_count = 0;
    $failed_details = [];
    $parent_document_id = $_POST['parent_document_id'] ?? null;
    $document_category = $_POST['document_category'] ?? 'Administrative';
    
    $upload_dir = 'uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 5 * 1024 * 1024;
    
    $file_count = is_array($files['name']) ? count($files['name']) : 1;
    
    try {
        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $files['name'][$i];
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];
            $file_tmp = $files['tmp_name'][$i];
            $file_error = $files['error'][$i];
            
            if ($file_error !== UPLOAD_ERR_OK) {
                $failed_count++;
                $failed_details[] = "$file_name - Upload error";
                continue;
            }
            
            if (!in_array($file_type, $allowed_types)) {
                $failed_count++;
                $failed_details[] = "$file_name - Invalid file type (only JPG, PNG, PDF allowed)";
                continue;
            }
            
            if ($file_size > $max_file_size) {
                $failed_count++;
                $failed_details[] = "$file_name - File size exceeds 5MB limit";
                continue;
            }
            
            $stored_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
            $file_path = $upload_dir . $stored_filename;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                $next_version = 1;
                
                if ($parent_document_id) {
                    $parent_doc = $db->fetchOne("SELECT version_number FROM document_submissions WHERE id = ?", [$parent_document_id]);
                    if ($parent_doc) {
                        $next_version = ($parent_doc['version_number'] ?? 1) + 1;
                    }
                    
                    $db->query(
                        "INSERT INTO document_submissions (admin_id, original_filename, stored_filename, file_path, file_type, file_size, document_category, parent_document_id, version_number, approval_status, current_stage)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'establishment')",
                        [$admin_id, $file_name, $stored_filename, $file_path, $file_type, $file_size, $document_category, $parent_document_id, $next_version]
                    );
                } else {
                    $db->query(
                        "INSERT INTO document_submissions (admin_id, original_filename, stored_filename, file_path, file_type, file_size, document_category, version_number)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
                        [$admin_id, $file_name, $stored_filename, $file_path, $file_type, $file_size, $document_category]
                    );
                }
                $uploaded_count++;
            } else {
                $failed_count++;
                $failed_details[] = "$file_name - Failed to save file";
            }
        }
        
        if ($uploaded_count > 0) {
            if ($parent_document_id) {
                $success = "✓ Successfully resubmitted $uploaded_count document(s)";
            } else {
                $success = "✓ Successfully uploaded $uploaded_count document(s)";
            }
            if ($failed_count > 0) {
                $success .= " ($failed_count failed)";
            }
        } elseif ($failed_count > 0) {
            $error = "Failed to upload all documents. Details:<br>" . implode("<br>", $failed_details);
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}

$documents = [];
try {
    $documents = $db->fetchAll(
        "SELECT * FROM document_submissions WHERE admin_id = ? ORDER BY created_at DESC",
        [$admin_id]
    );
} catch (Exception $e) {
    error_log("Document fetch error: " . $e->getMessage());
    $documents = [];
}
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-upload text-blue-600 mr-2"></i>Document Upload
            </h2>
            <p class="text-gray-600">Upload your documents (Images or PDF) for record keeping</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Upload Guidelines
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>✓ Allowed formats: JPG, PNG, PDF</li>
                    <li>✓ Maximum file size: 5MB per file</li>
                    <li>✓ You can upload multiple files at once</li>
                    <li>✓ All uploads are securely stored</li>
                </ul>
            </div>
            
            <div>
                <label for="category" class="block text-gray-700 font-semibold mb-3">
                    <i class="fas fa-folder-open mr-2"></i>Document Category
                </label>
                <select name="document_category" id="category" class="input-field mb-6" required>
                    <option value="">-- Select Document Category --</option>
                    <?php foreach ($document_categories as $category => $documents): ?>
                        <optgroup label="<?php echo htmlspecialchars($category); ?>">
                            <?php foreach ($documents as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" data-type="<?php echo htmlspecialchars($key); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>Select Documents
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition cursor-pointer" id="dropZone">
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 mb-2">Click to select or drag and drop your files</p>
                    <input type="file" name="documents[]" id="documents" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden" required>
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, PDF • Max 5MB per file</p>
                </div>
                <div id="fileList" class="mt-4 space-y-2"></div>
            </div>
            
            <button type="submit" class="w-full btn-primary">
                <i class="fas fa-upload mr-2"></i>Upload Documents
            </button>
        </form>
        
        <?php if (!empty($documents)): ?>
            <div class="mt-8 pt-8 border-t">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-file-archive text-green-600 mr-2"></i>My Documents (<?php echo count($documents); ?>)
                    </h3>
                    <a href="document_status.php" class="btn-secondary text-sm">
                        <i class="fas fa-bell mr-1"></i>View Status & Notifications
                    </a>
                </div>
                <div class="space-y-3">
                    <?php if (is_array($documents) && !empty($documents)): ?>
                        <?php foreach ($documents as $doc): ?>
                            <?php if (!is_array($doc)) continue; ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2 flex-wrap gap-2">
                                            <?php if (isset($doc['file_type']) && strpos($doc['file_type'], 'image') !== false): ?>
                                            <i class="fas fa-image text-blue-600 mr-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                                        <?php endif; ?>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($doc['original_filename'] ?? 'Unknown'); ?></span>
                                            
                                            <?php if (isset($doc['document_category']) && !empty($doc['document_category'])): ?>
                                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-semibold">
                                                    <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($doc['document_category']); ?>
                                                </span>
                                            <?php endif; ?>
                                        
                                            <?php
                                            $status_badge = '';
                                            $approval_status = $doc['approval_status'] ?? 'pending';
                                            switch ($approval_status) {
                                                case 'pending':
                                                    $status_badge = '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">Pending</span>';
                                                    break;
                                                case 'establishment_approved':
                                                    $status_badge = '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">Est. Approved</span>';
                                                    break;
                                                case 'registrar_approved':
                                                    $status_badge = '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">✓ Approved</span>';
                                                    break;
                                                case 'rejected':
                                                    $status_badge = '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">Rejected</span>';
                                                    break;
                                            }
                                            echo $status_badge;
                                            ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Size: <?php echo round(($doc['file_size'] ?? 0) / 1024, 2); ?>KB • Uploaded: <?php echo date('M d, Y H:i', strtotime($doc['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <?php if (isset($doc['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="btn-secondary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isset($doc['id'])): ?>
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-secondary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isset($doc['id']) && isset($doc['approval_status']) && $doc['approval_status'] === 'rejected'): ?>
                                        <a href="resubmit_document.php?id=<?php echo $doc['id']; ?>" class="btn-secondary text-orange-600 hover:bg-orange-50" title="Resubmit">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('documents');
const fileList = document.getElementById('fileList');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    fileInput.files = e.dataTransfer.files;
    updateFileList();
});

fileInput.addEventListener('change', updateFileList);

function updateFileList() {
    fileList.innerHTML = '';
    const files = Array.from(fileInput.files);
    
    if (files.length === 0) return;
    
    files.forEach((file, index) => {
        const size = (file.size / 1024).toFixed(2);
        const type = file.type.startsWith('image') ? '<i class="fas fa-image text-blue-600 mr-2"></i>' : '<i class="fas fa-file-pdf text-red-600 mr-2"></i>';
        
        fileList.innerHTML += `
            <div class="bg-blue-50 border border-blue-200 rounded p-3 flex justify-between items-center">
                <span>${type} ${file.name} (${size}KB)</span>
                <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });
}

function removeFile(index) {
    const dt = new DataTransfer();
    const files = fileInput.files;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    fileInput.files = dt.files;
    updateFileList();
}
</script>

<?php require_once 'includes/foot.php'; ?>
