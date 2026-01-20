<?php
require_once 'includes/functions.php';
requireSuperAdmin();

$pageTitle = 'Document Submissions';
require_once 'includes/head.php';

$db = Database::getInstance();

$submissions = $db->fetchAll("
    SELECT 
        ds.*, 
        au.firstname, 
        au.surname, 
        au.staff_id,
        COUNT(ar.id) as role_count
    FROM document_submissions ds
    JOIN admin_users au ON ds.admin_id = au.id
    LEFT JOIN admin_roles ar ON au.id = ar.admin_id AND ar.removed_at IS NULL
    GROUP BY ds.id, au.id, au.firstname, au.surname, au.staff_id
    HAVING role_count = 0
    ORDER BY ds.created_at DESC
");

$total_documents = count($submissions);
$total_by_staff = [];
foreach ($submissions as $doc) {
    $key = $doc['staff_id'];
    if (!isset($total_by_staff[$key])) {
        $total_by_staff[$key] = ['firstname' => $doc['firstname'], 'surname' => $doc['surname'], 'count' => 0];
    }
    $total_by_staff[$key]['count']++;
}
?>

<div class="max-w-6xl mx-auto">
    <div class="card mb-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-archive text-blue-600 mr-2"></i>Document Submissions
            </h2>
            <p class="text-gray-600">View documents submitted by staff without assigned roles</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-blue-600"><?php echo $total_documents; ?></div>
                <div class="text-gray-600">Total Documents</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-green-600"><?php echo count($total_by_staff); ?></div>
                <div class="text-gray-600">Staff Members</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="text-3xl font-bold text-purple-600">
                    <?php echo round(array_sum(array_column($total_by_staff, 'count')) / (count($total_by_staff) ?: 1), 1); ?>
                </div>
                <div class="text-gray-600">Avg Documents/Staff</div>
            </div>
        </div>
    </div>
    
    <?php if (empty($submissions)): ?>
        <div class="card text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No document submissions yet</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Staff Member</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Document</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Size</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Uploaded</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $doc): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($doc['firstname'] . ' ' . $doc['surname']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($doc['staff_id']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700">
                                        <?php 
                                        $icon = strpos($doc['file_type'], 'image') !== false ? 'fa-image' : 'fa-file-pdf';
                                        echo '<i class="fas ' . $icon . ' mr-2"></i>' . htmlspecialchars($doc['original_filename']); 
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?php echo strpos($doc['file_type'], 'image') !== false ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo strpos($doc['file_type'], 'image') !== false ? 'Image' : 'PDF'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo round($doc['file_size'] / 1024, 2); ?>KB
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo date('M d, Y', strtotime($doc['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>&admin=<?php echo $doc['admin_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download 
                                           class="text-green-600 hover:text-green-800" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
