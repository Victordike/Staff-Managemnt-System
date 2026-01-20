<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Dean Clearances';
require_once 'includes/head.php';

$db = Database::getInstance();

// Check if user is Dean or Superadmin
$isDean = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if (in_array($role['role_name'], ['Dean', 'Academic Dean'])) $isDean = true;
}

if (!$isDean && $userRole !== 'superadmin') {
    die('Access denied');
}

// Handle Clearance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId = $_POST['application_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'];
    
    $status = ($action === 'clear') ? 'dean_cleared' : 'dean_rejected';
    
    try {
        $db->query(
            "UPDATE leave_applications SET status = ?, dean_id = ?, dean_cleared_at = NOW(), dean_remarks = ? WHERE id = ?",
            [$status, $adminId, $remarks, $appId]
        );
        setFlashMessage('Clearance submitted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch applications recommended by HOD
$pendingApps = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.department,
            h.firstname as hod_fname, h.surname as hod_sname, la.hod_remarks 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     JOIN admin_users au ON la.admin_id = au.id 
     LEFT JOIN admin_users h ON la.hod_id = h.id
     WHERE la.status = 'hod_recommended' 
     ORDER BY la.hod_recommended_at ASC"
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Dean Clearances</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Review and clear leave applications that have departmental recommendation</p>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if (empty($pendingApps)): ?>
        <div class="card p-10 text-center text-gray-500">
            <i class="fas fa-check-double text-5xl mb-3 block opacity-20 text-blue-500"></i>
            No leave applications awaiting dean clearance.
        </div>
    <?php else: ?>
        <?php foreach ($pendingApps as $app): ?>
            <div class="card shadow-md border-l-4 border-blue-500 overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-xl font-bold mr-4">
                                <?php echo strtoupper($app['firstname'][0] . $app['surname'][0]); ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                                    <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?>
                                </h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($app['department']); ?> | <?php echo htmlspecialchars($app['leave_type']); ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 text-green-700 px-4 py-2 rounded-lg border border-green-100 text-xs">
                            <p class="font-bold uppercase tracking-widest">HOD Recommended</p>
                            <p class="mt-1 font-semibold">By: <?php echo htmlspecialchars($app['hod_fname'] . ' ' . $app['hod_sname']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Period</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    <?php echo date('M j, Y', strtotime($app['start_date'])); ?> to <?php echo date('M j, Y', strtotime($app['end_date'])); ?> (<?php echo $app['duration']; ?> Days)
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Reason</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200 italic">"<?php echo htmlspecialchars($app['reason']); ?>"</p>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 font-bold uppercase mb-1">HOD Remarks</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($app['hod_remarks'] ?: 'No remarks provided.'); ?></p>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-6 flex flex-col gap-4">
                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                        <textarea name="remarks" placeholder="Dean Remarks/Clearance notes..." class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 transition" rows="2"></textarea>
                        
                        <div class="flex gap-3 justify-end">
                            <button type="submit" name="action" value="reject" class="px-6 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-bold transition">
                                <i class="fas fa-times mr-2"></i>Decline
                            </button>
                            <button type="submit" name="action" value="clear" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition">
                                <i class="fas fa-check-double mr-2"></i>Give Clearance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
