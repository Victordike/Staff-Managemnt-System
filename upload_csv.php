<?php
require_once 'includes/functions.php';
requireSuperAdmin();

$pageTitle = 'Upload CSV';
require_once 'includes/head.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $fileName = uploadCSV($_FILES['csv_file']);
        $filePath = UPLOAD_DIR . $fileName;
        
        $csvData = parseCSV($filePath);
        
        if (empty($csvData)) {
            throw new Exception('CSV file is empty or invalid');
        }
        
        $db = Database::getInstance()->getConnection();
        $imported = 0;
        $skipped = 0;
        $alreadyRegistered = 0;
        $importDetails = [];
        
        foreach ($csvData as $row) {
            // Validate required fields
            if (empty($row['Staff ID']) || empty($row['Surname']) || empty($row['Firstname'])) {
                $skipped++;
                continue;
            }
            
            $staffId = $row['Staff ID'];
            
            try {
                // Check if already registered as admin user
                $existingAdmin = $db->prepare("SELECT id FROM admin_users WHERE staff_id = ?");
                $existingAdmin->execute([$staffId]);
                if ($existingAdmin->fetch()) {
                    $alreadyRegistered++;
                    $importDetails[] = "⊘ {$row['Firstname']} {$row['Surname']} (ID: {$staffId}) - Already registered";
                    continue;
                }
                
                // Check if exists in pre_users
                $existingPre = $db->prepare("SELECT id FROM pre_users WHERE staff_id = ?");
                $existingPre->execute([$staffId]);
                $exists = $existingPre->fetch();
                
                if ($exists) {
                    // Update existing record
                    $updateStmt = $db->prepare(
                        "UPDATE pre_users SET 
                         surname = ?, firstname = ?, othername = ?, 
                         salary_structure = ?, gl = ?, step = ?, rank = ?
                         WHERE staff_id = ?"
                    );
                    $updateStmt->execute([
                        $row['Surname'] ?? '',
                        $row['Firstname'] ?? '',
                        $row['Othername'] ?? '',
                        $row['Salary Structure'] ?? '',
                        $row['GL'] ?? '',
                        $row['STEP'] ?? '',
                        $row['Rank'] ?? '',
                        $staffId
                    ]);
                    $importDetails[] = "↻ {$row['Firstname']} {$row['Surname']} (ID: {$staffId}) - Updated";
                } else {
                    // Insert new record
                    $insertStmt = $db->prepare(
                        "INSERT INTO pre_users (surname, firstname, othername, staff_id, salary_structure, gl, step, rank) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $insertStmt->execute([
                        $row['Surname'] ?? '',
                        $row['Firstname'] ?? '',
                        $row['Othername'] ?? '',
                        $staffId,
                        $row['Salary Structure'] ?? '',
                        $row['GL'] ?? '',
                        $row['STEP'] ?? '',
                        $row['Rank'] ?? ''
                    ]);
                    $importDetails[] = "✓ {$row['Firstname']} {$row['Surname']} (ID: {$staffId}) - Added";
                }
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                $importDetails[] = "✗ {$row['Firstname']} {$row['Surname']} (ID: {$staffId}) - Error: " . $e->getMessage();
                error_log("Error importing row: " . $e->getMessage());
            }
        }
        
        $successMessage = "CSV import completed!<br>";
        $successMessage .= "✓ Records processed: <strong>$imported</strong><br>";
        $successMessage .= "⊘ Already registered: <strong>$alreadyRegistered</strong><br>";
        $successMessage .= "⊗ Skipped: <strong>$skipped</strong>";
        
        if (count($importDetails) > 0 && count($importDetails) <= 20) {
            $successMessage .= "<br><br><strong>Import Details:</strong><br><div style='max-height: 300px; overflow-y: auto; background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;'>";
            $successMessage .= implode("<br>", array_slice($importDetails, 0, 20));
            if (count($importDetails) > 20) {
                $successMessage .= "<br>... and " . (count($importDetails) - 20) . " more";
            }
            $successMessage .= "</div>";
        }
        
        $success = $successMessage;
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("CSV upload error: " . $e->getMessage());
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-upload text-blue-600 mr-2"></i>Upload Pre-Verification CSV
            </h2>
            <p class="text-gray-600">Upload a CSV file containing staff information for pre-verification during registration.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <!-- CSV Format Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>CSV Format Requirements
            </h3>
            <p class="text-sm text-blue-700 mb-3">The CSV file must contain the following columns:</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                <span class="bg-white px-3 py-1 rounded">Surname</span>
                <span class="bg-white px-3 py-1 rounded">Firstname</span>
                <span class="bg-white px-3 py-1 rounded">Othername</span>
                <span class="bg-white px-3 py-1 rounded">Staff ID</span>
                <span class="bg-white px-3 py-1 rounded">Salary Structure</span>
                <span class="bg-white px-3 py-1 rounded">GL</span>
                <span class="bg-white px-3 py-1 rounded">STEP</span>
                <span class="bg-white px-3 py-1 rounded">Rank</span>
            </div>
        </div>
        
        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fas fa-file-csv mr-2"></i>Select CSV File
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition">
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 mb-2">Click to select or drag and drop your CSV file</p>
                    <input type="file" name="csv_file" accept=".csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-xs text-gray-500 mt-2">Maximum file size: 5MB</p>
                </div>
            </div>
            
            <button type="submit" class="btn-primary w-full">
                <i class="fas fa-upload mr-2"></i>Upload and Import CSV
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
