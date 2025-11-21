<?php
require_once 'includes/functions.php';
requireSuperAdmin();

$pageTitle = 'Super Admin Profile';
require_once 'includes/head.php';
?>

<!-- Profile Header -->
<div class="card bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white mb-8 shadow-xl">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        <div class="w-32 h-32 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-5xl font-bold">
            <i class="fas fa-user-shield"></i>
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
