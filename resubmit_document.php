<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$document_id = $_GET['id'] ?? null;

if (!$document_id) {
    header('Location: upload_documents.php');
    exit;
}

$original_doc = $db->fetchOne("
    SELECT * FROM document_submissions WHERE id = ? AND admin_id = ?
", [$document_id, $admin_id]);

if (!$original_doc || $original_doc['approval_status'] !== 'rejected') {
    die("<div class='text-center py-12'><p class='text-red-600 font-semibold'>Invalid document or not eligible for resubmission.</p></div>");
}

$resubmitted_versions = $db->fetchAll("
    SELECT id, version_number, original_filename, created_at, approval_status 
    FROM document_submissions 
    WHERE parent_document_id = ? 
    ORDER BY version_number DESC
", [$document_id]);

$pageTitle = 'Resubmit Document';
require_once 'includes/head.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="card mb-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-redo text-orange-600 mr-2"></i>Resubmit Rejected Document
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-3">
                    <i class="fas fa-times-circle mr-2"></i>Original Submission
                </h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <strong class="text-gray-700">Document:</strong>
                        <div class="text-gray-600"><?php echo htmlspecialchars($original_doc['original_filename']); ?></div>
                    </div>
                    <div>
                        <strong class="text-gray-700">Uploaded:</strong>
                        <div class="text-gray-600"><?php echo date('M d, Y H:i', strtotime($original_doc['created_at'])); ?></div>
                    </div>
                    <div>
                        <strong class="text-gray-700">Rejection Reason:</strong>
                        <div class="text-red-700 font-semibold mt-1"><?php echo htmlspecialchars($original_doc['rejection_reason']); ?></div>
                    </div>
                    <?php if ($original_doc['establishment_comments']): ?>
                        <div>
                            <strong class="text-gray-700">Establishment Feedback:</strong>
                            <div class="text-gray-600 bg-white p-2 rounded mt-1 border-l-4 border-blue-500">
                                <i class="fas fa-info-circle text-blue-600 mr-1"></i><?php echo nl2br(htmlspecialchars($original_doc['establishment_comments'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($original_doc['registrar_comments']): ?>
                        <div>
                            <strong class="text-gray-700">Registrar Feedback:</strong>
                            <div class="text-gray-600 bg-white p-2 rounded mt-1 border-l-4 border-purple-500">
                                <i class="fas fa-info-circle text-purple-600 mr-1"></i><?php echo nl2br(htmlspecialchars($original_doc['registrar_comments'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Resubmission Guidelines
                </h3>
                <ul class="text-sm text-blue-700 space-y-2">
                    <li><i class="fas fa-check text-green-600 mr-2"></i>Address the rejection reason</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i>Follow any feedback provided</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i>Use same file format (JPG, PNG, PDF)</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i>Max file size: 5MB</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i>New version will restart approval workflow</li>
                </ul>
            </div>
        </div>

        <form method="POST" action="upload_documents.php" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="parent_document_id" value="<?php echo $original_doc['id']; ?>">
            
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>Select Replacement Document
                </label>
                <div class="border-2 border-dashed border-orange-300 rounded-lg p-8 text-center hover:border-orange-500 transition cursor-pointer" id="dropZone">
                    <i class="fas fa-cloud-upload-alt text-5xl text-orange-400 mb-4"></i>
                    <p class="text-gray-600 mb-2">Click to select or drag and drop your corrected file</p>
                    <input type="file" name="documents[]" id="documents" accept=".jpg,.jpeg,.png,.pdf" class="hidden" required>
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, PDF • Max 5MB</p>
                </div>
                <div id="fileList" class="mt-4 space-y-2"></div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary">
                    <i class="fas fa-upload mr-2"></i>Resubmit Document
                </button>
                <a href="upload_documents.php" class="flex-1 btn-secondary text-center">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <?php if (!empty($resubmitted_versions)): ?>
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-history text-blue-600 mr-2"></i>Resubmission History
            </h3>
            
            <div class="space-y-3">
                <?php foreach ($resubmitted_versions as $version): ?>
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded">Version <?php echo $version['version_number']; ?></span>
                                    <?php
                                    $status_color = '';
                                    $status_icon = '';
                                    switch ($version['approval_status']) {
                                        case 'pending':
                                            $status_color = 'bg-blue-100 text-blue-800';
                                            $status_icon = '<i class="fas fa-hourglass-half"></i>';
                                            break;
                                        case 'establishment_approved':
                                            $status_color = 'bg-yellow-100 text-yellow-800';
                                            $status_icon = '<i class="fas fa-check-circle"></i>';
                                            break;
                                        case 'registrar_approved':
                                            $status_color = 'bg-green-100 text-green-800';
                                            $status_icon = '<i class="fas fa-check-double"></i>';
                                            break;
                                        case 'rejected':
                                            $status_color = 'bg-red-100 text-red-800';
                                            $status_icon = '<i class="fas fa-times-circle"></i>';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_color; ?> text-xs font-bold px-2 py-1 rounded"><?php echo $status_icon; ?> <?php echo ucfirst(str_replace('_', ' ', $version['approval_status'])); ?></span>
                                </div>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($version['original_filename']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Resubmitted: <?php echo date('M d, Y H:i', strtotime($version['created_at'])); ?></p>
                            </div>
                            <a href="view_document.php?id=<?php echo $version['id']; ?>" class="btn-sm btn-primary">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('documents');
const fileList = document.getElementById('fileList');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-orange-500', 'bg-orange-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-orange-500', 'bg-orange-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-orange-500', 'bg-orange-50');
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
            <div class="bg-orange-50 border border-orange-200 rounded p-3 flex justify-between items-center">
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
