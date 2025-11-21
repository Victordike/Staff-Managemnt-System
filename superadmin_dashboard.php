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
    
    // Calculate completion rate
    $completionRate = $totalPreUsers > 0 ? round(($registeredUsers / $totalPreUsers) * 100) : 0;
} catch (Exception $e) {
    error_log($e->getMessage());
    $totalPreUsers = $registeredUsers = $pendingUsers = $totalAdmins = 0;
    $recentRegistrations = [];
    $completionRate = 0;
}
?>

<!-- Welcome Banner -->
<div class="card bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white mb-8 shadow-xl">
    <div class="flex flex-col md:flex-row items-center justify-between">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Welcome Back, <?php echo htmlspecialchars($_SESSION['firstname']); ?></h1>
            <p class="text-indigo-100 text-lg">Federal Polytechnic of Oil and Gas - Staff Management System</p>
            <p class="text-indigo-200 text-sm mt-2"><i class="fas fa-calendar mr-2"></i><?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="mt-6 md:mt-0 text-6xl opacity-20"><i class="fas fa-graduation-cap"></i></div>
    </div>
</div>

<!-- Advanced Stats Cards with Progress Indicators -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Pre-Users Card -->
    <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-blue-100 text-sm mb-1 font-semibold">Total Pre-Users</p>
                <h3 class="text-4xl font-bold"><?php echo $totalPreUsers; ?></h3>
            </div>
            <i class="fas fa-users text-5xl text-blue-200 opacity-50"></i>
        </div>
        <div class="w-full bg-blue-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: 100%"></div>
        </div>
    </div>
    
    <!-- Registered Admins Card -->
    <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-green-100 text-sm mb-1 font-semibold">Registered Admins</p>
                <h3 class="text-4xl font-bold"><?php echo $totalAdmins; ?></h3>
            </div>
            <i class="fas fa-user-check text-5xl text-green-200 opacity-50"></i>
        </div>
        <div class="w-full bg-green-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo $totalPreUsers > 0 ? ($totalAdmins / $totalPreUsers) * 100 : 0; ?>%"></div>
        </div>
    </div>
    
    <!-- Pending Registrations Card -->
    <div class="card bg-gradient-to-br from-yellow-500 to-orange-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-yellow-100 text-sm mb-1 font-semibold">Pending Registration</p>
                <h3 class="text-4xl font-bold"><?php echo $pendingUsers; ?></h3>
            </div>
            <i class="fas fa-hourglass-half text-5xl text-yellow-200 opacity-50"></i>
        </div>
        <div class="w-full bg-yellow-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo $totalPreUsers > 0 ? ($pendingUsers / $totalPreUsers) * 100 : 0; ?>%"></div>
        </div>
    </div>
    
    <!-- Completion Rate Card -->
    <div class="card bg-gradient-to-br from-purple-500 to-pink-600 text-white transform hover:scale-105 transition duration-300 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-purple-100 text-sm mb-1 font-semibold">Completion Rate</p>
                <h3 class="text-4xl font-bold"><?php echo $completionRate; ?>%</h3>
            </div>
            <i class="fas fa-chart-pie text-5xl text-purple-200 opacity-50"></i>
        </div>
        <div class="w-full bg-purple-400 rounded-full h-2">
            <div class="bg-white h-2 rounded-full" style="width: <?php echo $completionRate; ?>%"></div>
        </div>
    </div>
</div>

<!-- System Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- System Status -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-server text-green-600 mr-2"></i>System Status
        </h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Database</span>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Active</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Server</span>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Running</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">API</span>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Online</span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-bolt text-yellow-600 mr-2"></i>Quick Actions
        </h3>
        <div class="space-y-2">
            <a href="upload_csv.php" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-3 rounded-lg transition text-center text-sm font-semibold">
                <i class="fas fa-upload mr-2"></i>Upload CSV
            </a>
            <a href="manage_users.php" class="block w-full bg-indigo-100 hover:bg-indigo-200 text-indigo-800 py-2 px-3 rounded-lg transition text-center text-sm font-semibold">
                <i class="fas fa-users-cog mr-2"></i>Manage Users
            </a>
        </div>
    </div>
    
    <!-- Institution Info -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Institution
        </h3>
        <div class="space-y-2 text-sm text-gray-700">
            <div>
                <p class="font-semibold text-gray-800">Federal Polytechnic of Oil and Gas</p>
                <p class="text-gray-600">Effurun, Delta State, Nigeria</p>
            </div>
            <div class="pt-2 border-t">
                <p class="text-xs text-gray-500">Total Users: <span class="font-bold text-gray-800"><?php echo $totalPreUsers; ?></span></p>
                <p class="text-xs text-gray-500">Active Admins: <span class="font-bold text-gray-800"><?php echo $totalAdmins; ?></span></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Registrations -->
<div class="card mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-clock mr-2 text-blue-600"></i>Recent Registrations
    </h2>
    
    <?php if (empty($recentRegistrations)): ?>
        <p class="text-gray-500 text-center py-8">No registrations yet</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Staff ID</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Department</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Registered On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRegistrations as $index => $user): ?>
                        <tr class="border-b border-gray-100 hover:bg-blue-50 transition <?php echo $index % 2 === 0 ? 'bg-gray-50' : ''; ?>">
                            <td class="py-3 px-4 font-semibold text-blue-600"><?php echo htmlspecialchars($user['staff_id']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['surname']); ?></td>
                            <td class="py-3 px-4"><span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold"><?php echo htmlspecialchars($user['department']); ?></span></td>
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($user['official_email']); ?></td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?php echo formatDate($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Animated Information Ticker -->
<div class="ticker-container">
    <div class="ticker-content">
        <span class="ticker-item"><i class="fas fa-bell mr-2 text-yellow-500"></i>Welcome to FPOG Staff Management System</span>
        <span class="ticker-item"><i class="fas fa-info-circle mr-2 text-blue-500"></i>All staff records are securely stored and managed</span>
        <span class="ticker-item"><i class="fas fa-shield-alt mr-2 text-green-500"></i>Your data is protected with industry-standard encryption</span>
        <span class="ticker-item"><i class="fas fa-users mr-2 text-purple-500"></i><?php echo $totalAdmins; ?> active administrators managing the system</span>
        <span class="ticker-item"><i class="fas fa-check-circle mr-2 text-green-500"></i><?php echo $completionRate; ?>% registration completion rate</span>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
