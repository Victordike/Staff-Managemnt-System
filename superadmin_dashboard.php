<?php
require_once 'includes/functions.php';
requireSuperAdmin();

$pageTitle = 'Super Admin Dashboard';
require_once 'includes/head.php';

try {
    $db = Database::getInstance();
    
    // Get statistics
    $totalPreUsers = $db->fetchOne("SELECT COUNT(*) as count FROM pre_users")['count'];
    $registeredUsers = $db->fetchOne("SELECT COUNT(*) as count FROM pre_users WHERE is_registered = TRUE")['count'];
    $pendingUsers = $totalPreUsers - $registeredUsers;
    $totalAdmins = $db->fetchOne("SELECT COUNT(*) as count FROM admin_users WHERE is_active = TRUE")['count'];
    
    // Get recent registrations
    $recentRegistrations = $db->fetchAll(
        "SELECT * FROM admin_users ORDER BY created_at DESC LIMIT 10"
    );
} catch (Exception $e) {
    error_log($e->getMessage());
    $totalPreUsers = $registeredUsers = $pendingUsers = $totalAdmins = 0;
    $recentRegistrations = [];
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm mb-1">Total Pre-Users</p>
                <h3 class="text-3xl font-bold"><?php echo $totalPreUsers; ?></h3>
            </div>
            <i class="fas fa-users text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm mb-1">Registered Admins</p>
                <h3 class="text-3xl font-bold"><?php echo $totalAdmins; ?></h3>
            </div>
            <i class="fas fa-user-check text-4xl text-green-200"></i>
        </div>
    </div>
    
    <div class="card bg-gradient-to-br from-yellow-500 to-yellow-600 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm mb-1">Pending Registration</p>
                <h3 class="text-3xl font-bold"><?php echo $pendingUsers; ?></h3>
            </div>
            <i class="fas fa-clock text-4xl text-yellow-200"></i>
        </div>
    </div>
    
    <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm mb-1">Completion Rate</p>
                <h3 class="text-3xl font-bold"><?php echo $totalPreUsers > 0 ? round(($registeredUsers / $totalPreUsers) * 100) : 0; ?>%</h3>
            </div>
            <i class="fas fa-chart-pie text-4xl text-purple-200"></i>
        </div>
    </div>
</div>

<!-- Recent Registrations -->
<div class="card">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-clock mr-2 text-blue-600"></i>Recent Registrations
    </h2>
    
    <?php if (empty($recentRegistrations)): ?>
        <p class="text-gray-500 text-center py-8">No registrations yet</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Staff ID</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Department</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Registered On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRegistrations as $user): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['staff_id']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['surname']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['department']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['official_email']); ?></td>
                            <td class="py-3 px-4"><?php echo formatDate($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
