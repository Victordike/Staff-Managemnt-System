<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Leave Approval History';
require_once 'includes/head.php';

$db = Database::getInstance();

// Identify user roles
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
$roleNames = array_column($roles, 'role_name');
$isSuperAdmin = ($userRole === 'superadmin');

// Build query based on roles
$whereClauses = [];
$params = [];

if ($isSuperAdmin) {
    $whereClauses[] = "la.status NOT IN ('pending')";
} else {
    $subClauses = [];
    if (in_array('HOD', $roleNames)) {
        $subClauses[] = "la.hod_id = ?";
        $params[] = $adminId;
    }
    if (in_array('Dean', $roleNames) || in_array('Academic Dean', $roleNames)) {
        $subClauses[] = "la.dean_id = ?";
        $params[] = $adminId;
    }
    if (in_array('Establishment', $roleNames)) {
        $subClauses[] = "la.establishment_id = ?";
        $params[] = $adminId;
    }
    if (in_array('Registrar', $roleNames) || in_array('Rector', $roleNames)) {
        $subClauses[] = "la.approver_id = ?";
        $params[] = $adminId;
    }
    
    if (empty($subClauses)) {
        die('Access denied: You do not have an approval role.');
    }
    $whereClauses[] = "(" . implode(" OR ", $subClauses) . ")";
}

$whereSql = implode(" AND ", $whereClauses);

$history = $db->fetchAll("
    SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.department, au.staff_id 
    FROM leave_applications la 
    JOIN leave_types lt ON la.leave_type_id = lt.id 
    JOIN admin_users au ON la.admin_id = au.id 
    WHERE $whereSql 
    ORDER BY la.updated_at DESC
", $params);

?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Approval History</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Review your past recommendations, clearances, and approvals.</p>
</div>

<div class="card overflow-hidden">
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Applicant</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Leave Type</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($history as $app): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-bold mr-3">
                                    <?php echo strtoupper($app['firstname'][0] . $app['surname'][0]); ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($app['staff_id']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($app['leave_type']); ?></span>
                            <p class="text-[10px] text-gray-400"><?php echo $app['duration']; ?> Days</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <?php echo date('M j', strtotime($app['start_date'])); ?> - <?php echo date('M j, Y', strtotime($app['end_date'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                                $statusClass = 'bg-gray-100 text-gray-800';
                                if ($app['status'] === 'approved' || $app['status'] === 'completed') $statusClass = 'bg-green-100 text-green-800';
                                if (strpos($app['status'], 'rejected') !== false || $app['status'] === 'rejected') $statusClass = 'bg-red-100 text-red-800';
                                if (strpos($app['status'], 'cleared') !== false || strpos($app['status'], 'recommended') !== false || strpos($app['status'], 'verified') !== false) $statusClass = 'bg-blue-100 text-blue-800';
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?php echo $statusClass; ?>">
                                <?php echo str_replace('_', ' ', $app['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick='viewDetails(<?php echo json_encode($app); ?>)' class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase tracking-widest">
                                Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">No past approval records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-blue-600 p-6 flex justify-between items-center text-white">
            <h3 class="text-xl font-bold">Application Details</h3>
            <button onclick="closeModal()" class="hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-6 max-h-[80vh] overflow-y-auto">
            <div id="modalContent">
                <!-- Data injected here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(app) {
    let html = `
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Applicant</p>
                <p class="font-black text-gray-800 dark:text-white">${app.firstname} ${app.surname}</p>
                <p class="text-sm text-gray-500">${app.department}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Leave Period</p>
                <p class="font-black text-gray-800 dark:text-white">${app.duration} Days</p>
                <p class="text-sm text-gray-500">${app.start_date} to ${app.end_date}</p>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 mb-6">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Reason for Leave</p>
            <p class="text-gray-700 dark:text-gray-300 italic">"${app.reason}"</p>
        </div>
        
        <div class="space-y-4">
            <h4 class="font-bold text-gray-800 dark:text-white border-b pb-2">Workflow History</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-white dark:bg-gray-900 border rounded-lg">
                    <p class="text-[10px] font-black text-gray-400 uppercase">HOD Recommendation</p>
                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">${app.hod_remarks || 'No remarks'}</p>
                    <p class="text-[10px] text-blue-500 font-bold mt-1">${app.hod_recommended_at || ''}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-900 border rounded-lg">
                    <p class="text-[10px] font-black text-gray-400 uppercase">Dean Clearance</p>
                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">${app.dean_remarks || 'No remarks'}</p>
                    <p class="text-[10px] text-blue-500 font-bold mt-1">${app.dean_cleared_at || ''}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-900 border rounded-lg">
                    <p class="text-[10px] font-black text-gray-400 uppercase">Establishment Verification</p>
                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">${app.establishment_remarks || 'No remarks'}</p>
                    <p class="text-[10px] text-blue-500 font-bold mt-1">${app.establishment_verified_at || ''}</p>
                </div>
                <div class="p-3 bg-white dark:bg-gray-900 border rounded-lg">
                    <p class="text-[10px] font-black text-gray-400 uppercase">Final Approval</p>
                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">${app.approver_remarks || 'No remarks'}</p>
                    <p class="text-[10px] text-blue-500 font-bold mt-1">${app.approved_at || ''}</p>
                </div>
            </div>
        </div>
    `;
    
    $('#modalContent').html(html);
    $('#detailsModal').removeClass('hidden').addClass('flex');
}

function closeModal() {
    $('#detailsModal').addClass('hidden').removeClass('flex');
}

$(document).ready(function() {
    $('#detailsModal').on('click', function(e) {
        if (e.target === this) closeModal();
    });
});
</script>

<?php require_once 'includes/foot.php'; ?>
