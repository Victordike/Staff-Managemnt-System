<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'HOD Recommendations';
require_once 'includes/head.php';

$db = Database::getInstance();

// Check if user is HOD or Superadmin
$isHOD = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if ($role['role_name'] === 'HOD') $isHOD = true;
}

if (!$isHOD && $userRole !== 'superadmin') {
    die('Access denied');
}

// Handle Recommendation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId = $_POST['application_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'];
    
    $status = ($action === 'recommend') ? 'hod_recommended' : 'hod_rejected';
    
    try {
        $db->query(
            "UPDATE leave_applications SET status = ?, hod_id = ?, hod_recommended_at = NOW(), hod_remarks = ? WHERE id = ?",
            [$status, $adminId, $remarks, $appId]
        );
        setFlashMessage('Recommendation submitted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch pending applications for HOD
// In a real system, we might filter by department.
$pendingApps = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.department 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     JOIN admin_users au ON la.admin_id = au.id 
     WHERE la.status = 'pending' 
     ORDER BY la.created_at ASC"
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">HOD Recommendations</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Review and recommend leave applications from your department</p>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if (empty($pendingApps)): ?>
        <div class="card p-10 text-center text-gray-500">
            <i class="fas fa-check-circle text-5xl mb-3 block opacity-20 text-green-500"></i>
            No pending leave applications for recommendation.
        </div>
    <?php else: ?>
        <?php foreach ($pendingApps as $app): ?>
            <div class="card shadow-md hover:shadow-lg transition border-l-4 border-yellow-400 overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl font-bold mr-4">
                                <?php echo strtoupper($app['firstname'][0] . $app['surname'][0]); ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                                    <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?>
                                </h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($app['department']); ?> | <?php echo htmlspecialchars($app['leave_type']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col md:items-end">
                            <span class="text-sm font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-100 mb-1">
                                <?php echo $app['duration']; ?> Days
                            </span>
                            <span class="text-xs text-gray-400">
                                Requested: <?php echo date('M j, Y', strtotime($app['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Period</p>
                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                <?php echo date('M j, Y', strtotime($app['start_date'])); ?> to <?php echo date('M j, Y', strtotime($app['end_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Reason</p>
                            <p class="text-sm text-gray-800 dark:text-gray-200 italic">"<?php echo htmlspecialchars($app['reason']); ?>"</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-6 flex flex-col gap-4">
                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                        <textarea name="remarks" placeholder="HOD Remarks/Recommendation notes..." class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 transition" rows="2"></textarea>
                        
                        <div class="flex gap-3 justify-end">
                            <button type="submit" name="action" value="reject" class="px-6 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-bold transition flex items-center">
                                <i class="fas fa-times-circle mr-2"></i>Decline
                            </button>
                            <button type="submit" name="action" value="recommend" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold shadow-md transition flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>Recommend
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
