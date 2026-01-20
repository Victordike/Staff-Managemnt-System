<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Establishment Verifications';
require_once 'includes/head.php';

$db = Database::getInstance();

// Check if user is Establishment or Superadmin
$isEst = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if ($role['role_name'] === 'Establishment') $isEst = true;
}

if (!$isEst && $userRole !== 'superadmin') {
    die('Access denied');
}

// Handle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId = $_POST['application_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'];
    
    $status = ($action === 'verify') ? 'establishment_verified' : 'establishment_rejected';
    
    try {
        $db->query(
            "UPDATE leave_applications SET status = ?, establishment_id = ?, establishment_verified_at = NOW(), establishment_remarks = ? WHERE id = ?",
            [$status, $adminId, $remarks, $appId]
        );
        setFlashMessage('Verification submitted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch applications cleared by Dean
$pendingApps = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.department, au.staff_id,
            d.firstname as dean_fname, d.surname as dean_sname, la.dean_remarks,
            h.firstname as hod_fname, h.surname as hod_sname, la.hod_remarks 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     JOIN admin_users au ON la.admin_id = au.id 
     LEFT JOIN admin_users d ON la.dean_id = d.id
     LEFT JOIN admin_users h ON la.hod_id = h.id
     WHERE la.status = 'dean_cleared' 
     ORDER BY la.dean_cleared_at ASC"
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Establishment Verifications</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Verify leave eligibility and check leave balances for applicants</p>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if (empty($pendingApps)): ?>
        <div class="card p-10 text-center text-gray-500">
            <i class="fas fa-id-card text-5xl mb-3 block opacity-20 text-purple-500"></i>
            No leave applications awaiting establishment verification.
        </div>
    <?php else: ?>
        <?php foreach ($pendingApps as $app): ?>
            <div class="card shadow-md border-l-4 border-purple-500 overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl font-bold mr-4">
                                <?php echo strtoupper($app['firstname'][0] . $app['surname'][0]); ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                                    <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($app['staff_id']); ?> | <?php echo htmlspecialchars($app['department']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <div class="bg-blue-50 text-blue-700 px-3 py-1 rounded border border-blue-100 text-xs">
                                <p class="font-bold">HOD OK</p>
                            </div>
                            <div class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded border border-indigo-100 text-xs">
                                <p class="font-bold">DEAN OK</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Leave Type</p>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($app['leave_type']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Period</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200"><?php echo date('M j, Y', strtotime($app['start_date'])); ?> - <?php echo date('M j, Y', strtotime($app['end_date'])); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Duration</p>
                                <p class="text-sm font-bold text-blue-600"><?php echo $app['duration']; ?> Days</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Current Balance</p>
                                <?php 
                                    $bal = $db->fetchOne("SELECT remaining_days FROM leave_balances WHERE admin_id = ? AND leave_type_id = ? AND year = ?", [$app['admin_id'], $app['leave_type_id'], date('Y', strtotime($app['start_date']))]);
                                    if ($bal) {
                                        echo '<p class="text-sm font-black text-green-600">' . $bal['remaining_days'] . ' Days Remaining</p>';
                                    } else {
                                        echo '<p class="text-sm font-bold text-red-500">Not Initialized</p>';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">HOD Remarks (<?php echo htmlspecialchars($app['hod_fname']); ?>)</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 italic">"<?php echo htmlspecialchars($app['hod_remarks'] ?: 'N/A'); ?>"</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Dean Remarks (<?php echo htmlspecialchars($app['dean_fname']); ?>)</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 italic">"<?php echo htmlspecialchars($app['dean_remarks'] ?: 'N/A'); ?>"</p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-6 flex flex-col gap-4">
                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                        <textarea name="remarks" placeholder="Establishment Verification notes (e.g., 'Leave balance confirmed', 'Service record verified')..." class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 transition" rows="2"></textarea>
                        
                        <div class="flex gap-3 justify-end">
                            <button type="submit" name="action" value="reject" class="px-6 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-bold transition">
                                <i class="fas fa-times-circle mr-2"></i>Ineligible
                            </button>
                            <button type="submit" name="action" value="verify" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold shadow-md transition">
                                <i class="fas fa-id-card mr-2"></i>Verify & Forward
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
