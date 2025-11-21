<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireSuperAdmin();

$pageTitle = 'Super Admin Profile';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;
        
        if (in_array($_FILES['profile_picture']['type'], $allowed) && $_FILES['profile_picture']['size'] <= $max_size) {
            $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $upload_path = 'uploads/profile_pictures/' . $filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                try {
                    $db = Database::getInstance();
                    $db->query("UPDATE users SET profile_picture = ? WHERE id = ?", [$upload_path, $_SESSION['user_id']]);
                    $_SESSION['profile_picture'] = $upload_path;
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }
}

require_once 'includes/head.php';
?>

<!-- Profile Header -->
<div class="card bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white mb-8 shadow-xl">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        <div class="relative">
            <div class="w-32 h-32 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-5xl font-bold overflow-hidden">
                <?php if (isset($_SESSION['profile_picture']) && $_SESSION['profile_picture']): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <button onclick="document.getElementById('photoUploadForm').classList.toggle('hidden')" class="absolute bottom-0 right-0 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full">
                <i class="fas fa-camera"></i>
            </button>
        </div>
        <div class="flex-1">
            <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></h1>
            <p class="text-indigo-100 text-lg mb-3">System Administrator</p>
            <div class="flex gap-6 flex-wrap">
                <div>
                    <p class="text-indigo-200 text-sm">Username</p>
                    <p class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <div>
                    <p class="text-indigo-200 text-sm">Role</p>
                    <p class="text-xl font-bold capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
                <div>
                    <p class="text-indigo-200 text-sm">Status</p>
                    <p class="text-lg font-bold"><span class="bg-green-100 text-green-800 px-3 py-1 rounded-full">Active</span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Upload Form -->
<div id="photoUploadForm" class="card mb-8 hidden bg-blue-50 dark:bg-blue-900">
    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Update Your Photo/Passport</h3>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Select Photo/Passport</label>
            <input type="file" name="profile_picture" accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer" required>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">JPG, PNG, or GIF (Max 5MB)</p>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="btn-primary px-6">
                <i class="fas fa-upload mr-2"></i>Upload Photo
            </button>
            <button type="button" onclick="document.getElementById('photoUploadForm').classList.add('hidden')" class="btn-secondary px-6">Cancel</button>
        </div>
    </form>
</div>

<!-- Account Information -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-blue-500">
        <i class="fas fa-user-circle mr-2 text-blue-600"></i>Account Information
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-2">First Name</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['firstname']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-lg border border-green-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-2">Last Name</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['lastname']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-6 rounded-lg border border-purple-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-2">Username</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-6 rounded-lg border border-yellow-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-2">Role</p>
            <p class="text-2xl font-bold text-gray-800 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
    </div>
</div>

<!-- System Access -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-green-500">
        <i class="fas fa-key mr-2 text-green-600"></i>System Access & Permissions
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold">Dashboard</h3>
                <i class="fas fa-chart-bar text-3xl opacity-50"></i>
            </div>
            <p class="text-blue-100">Full access to system dashboard and analytics</p>
            <div class="mt-4 bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-semibold w-fit">Enabled</div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold">User Management</h3>
                <i class="fas fa-users-cog text-3xl opacity-50"></i>
            </div>
            <p class="text-green-100">Full control over user accounts and permissions</p>
            <div class="mt-4 bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-semibold w-fit">Enabled</div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold">System Settings</h3>
                <i class="fas fa-cogs text-3xl opacity-50"></i>
            </div>
            <p class="text-purple-100">Administrative access to system configuration</p>
            <div class="mt-4 bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-semibold w-fit">Enabled</div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-indigo-500">
        <i class="fas fa-info-circle mr-2 text-indigo-600"></i>System Information
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <p class="text-sm text-gray-600 font-semibold mb-1">System Name</p>
            <p class="text-lg font-bold text-gray-800">FPOG Staff Management System</p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <p class="text-sm text-gray-600 font-semibold mb-1">Institution</p>
            <p class="text-lg font-bold text-gray-800">Federal Polytechnic of Oil and Gas</p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <p class="text-sm text-gray-600 font-semibold mb-1">Location</p>
            <p class="text-lg font-bold text-gray-800">Effurun, Delta State, Nigeria</p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <p class="text-sm text-gray-600 font-semibold mb-1">Last Activity</p>
            <p class="text-lg font-bold text-gray-800"><?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</div>

<!-- Animated Information Ticker -->
<div class="ticker-container">
    <div class="ticker-content">
        <span class="ticker-item"><i class="fas fa-crown mr-2 text-yellow-500"></i>Welcome to Super Admin Panel</span>
        <span class="ticker-item"><i class="fas fa-shield-alt mr-2 text-green-500"></i>You have full administrative access</span>
        <span class="ticker-item"><i class="fas fa-lock mr-2 text-blue-500"></i>All actions are logged and monitored</span>
        <span class="ticker-item"><i class="fas fa-users mr-2 text-purple-500"></i>Manage all staff and system settings</span>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
