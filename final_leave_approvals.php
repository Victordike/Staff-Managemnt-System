<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Final Leave Approvals';
require_once 'includes/head.php';

$db = Database::getInstance();

// Check if user is Registrar, Rector or Superadmin
$isFinalApprover = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if (in_array($role['role_name'], ['Registrar', 'Rector'])) $isFinalApprover = true;
}

if (!$isFinalApprover && $userRole !== 'superadmin') {
    die('Access denied');
}

// Handle Final Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId = $_POST['application_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'];
    
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    try {
        $db->query(
            "UPDATE leave_applications SET status = ?, approver_id = ?, approved_at = NOW(), approver_remarks = ? WHERE id = ?",
            [$status, $adminId, $remarks, $appId]
        );

        // Deduct from balance if approved
        if ($action === 'approve') {
            $appData = $db->fetchOne("SELECT admin_id, leave_type_id, duration, start_date FROM leave_applications WHERE id = ?", [$appId]);
            if ($appData) {
                $year = date('Y', strtotime($appData['start_date']));
                $db->query(
                    "UPDATE leave_balances 
                     SET used_days = used_days + ?, remaining_days = remaining_days - ? 
                     WHERE admin_id = ? AND leave_type_id = ? AND year = ?",
                    [$appData['duration'], $appData['duration'], $appData['admin_id'], $appData['leave_type_id'], $year]
                );
            }
        }

        setFlashMessage('Application ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch applications verified by Establishment
$pendingApps = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.department, au.staff_id,
            e.firstname as est_fname, e.surname as est_sname, la.establishment_remarks,
            d.firstname as dean_fname, d.surname as dean_sname, la.dean_remarks,
            h.firstname as hod_fname, h.surname as hod_sname, la.hod_remarks 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     JOIN admin_users au ON la.admin_id = au.id 
     LEFT JOIN admin_users e ON la.establishment_id = e.id
     LEFT JOIN admin_users d ON la.dean_id = d.id
     LEFT JOIN admin_users h ON la.hod_id = h.id
     WHERE la.status = 'establishment_verified' 
     ORDER BY la.establishment_verified_at ASC"
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Final Leave Approvals</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Final review and approval of leave applications for Registrar/Rector</p>
</div>

<div class="grid grid-cols-1 gap-8">
    <?php if (empty($pendingApps)): ?>
        <div class="card p-12 text-center text-gray-500 bg-gray-50 border-dashed border-2 border-gray-200">
            <i class="fas fa-check-double text-6xl mb-4 block opacity-10"></i>
            All verified leave applications have been processed.
        </div>
    <?php else: ?>
        <?php foreach ($pendingApps as $app): ?>
            <div class="card shadow-xl border-t-8 border-green-600 overflow-hidden">
                <div class="p-8">
                    <div class="flex flex-col lg:flex-row justify-between gap-6">
                        <div class="flex items-start">
                            <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center text-green-700 text-2xl font-black mr-6 shadow-sm border border-green-200">
                                <?php echo strtoupper($app['firstname'][0] . $app['surname'][0]); ?>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white leading-tight">
                                    <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?>
                                </h3>
                                <p class="text-gray-500 font-medium">ID: <?php echo htmlspecialchars($app['staff_id']); ?> | <?php echo htmlspecialchars($app['department']); ?></p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded tracking-tighter uppercase border border-blue-200">HOD Recommended</span>
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded tracking-tighter uppercase border border-indigo-200">Dean Cleared</span>
                                    <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-1 rounded tracking-tighter uppercase border border-purple-200">Establishment Verified</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 min-w-[250px]">
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Request Summary</p>
                            <h4 class="text-xl font-bold text-blue-600 mb-1"><?php echo htmlspecialchars($app['leave_type']); ?></h4>
                            <p class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $app['duration']; ?> <span class="text-sm font-normal text-gray-500">Days</span></p>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-calendar-alt mr-1"></i> 
                                <?php echo date('d M', strtotime($app['start_date'])); ?> - <?php echo date('d M Y', strtotime($app['end_date'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-8 space-y-6">
                        <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-10 p-4 rounded-xl border-l-4 border-blue-400">
                            <p class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-1">Reason for Leave</p>
                            <p class="text-gray-700 dark:text-gray-400 text-sm italic">"<?php echo htmlspecialchars($app['reason']); ?>"</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">HOD Remarks</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium italic">"<?php echo htmlspecialchars($app['hod_remarks'] ?: 'No comments'); ?>"</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-bold">— <?php echo htmlspecialchars($app['hod_fname']); ?></p>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Dean Remarks</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium italic">"<?php echo htmlspecialchars($app['dean_remarks'] ?: 'No comments'); ?>"</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-bold">— <?php echo htmlspecialchars($app['dean_fname']); ?></p>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Est. Remarks</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium italic">"<?php echo htmlspecialchars($app['establishment_remarks'] ?: 'No comments'); ?>"</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-bold">— <?php echo htmlspecialchars($app['est_fname']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-700">
                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                        <div class="flex flex-col md:flex-row gap-4 items-end">
                            <div class="flex-1 w-full">
                                <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block">Final Approver Remarks</label>
                                <textarea name="remarks" placeholder="Add any final approval notes or conditions here..." class="w-full p-4 rounded-xl border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white focus:ring-4 focus:ring-green-500 transition shadow-inner" rows="2"></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" name="action" value="reject" class="px-8 py-4 bg-white text-red-600 border-2 border-red-100 hover:border-red-600 hover:bg-red-50 rounded-xl font-black transition uppercase tracking-widest text-xs">
                                    Reject
                                </button>
                                <button type="submit" name="action" value="approve" class="px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-black shadow-xl transition transform hover:scale-105 uppercase tracking-widest text-xs">
                                    Approve Leave
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
