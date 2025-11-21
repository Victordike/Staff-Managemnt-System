<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$userRole = $_SESSION['role'] ?? '';
$fullname = $_SESSION['fullname'] ?? 'User';
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname = $_SESSION['lastname'] ?? '';
$profilePicture = $_SESSION['profile_picture'] ?? null;
$initials = getInitials($firstname, $lastname);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Staff Management System</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden relative">
        <!-- Overlay for mobile drawer -->
        <div class="md:hidden fixed inset-0 bg-black bg-opacity-50 hidden" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar expanded bg-white shadow-xl flex flex-col md:relative">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div class="sidebar-text">
                    <h2 class="text-xl font-bold text-blue-600">FPOG SMS</h2>
                </div>
                <button id="toggleSidebar" class="text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Profile Section -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold mb-3">
                        <?php if ($profilePicture && file_exists($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initials); ?>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-text text-center">
                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($fullname); ?></h3>
                        <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($userRole); ?></p>
                    </div>
                    <?php if ($userRole === 'superadmin'): ?>
                        <a href="superadmin_profile.php" class="sidebar-text mt-3 text-sm text-blue-600 hover:text-blue-800 transition">
                            <i class="fas fa-user-circle mr-1"></i>Profile
                        </a>
                    <?php elseif ($userRole === 'admin'): ?>
                        <a href="admin_profile.php" class="sidebar-text mt-3 text-sm text-blue-600 hover:text-blue-800 transition">
                            <i class="fas fa-user-circle mr-1"></i>Profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4">
                <?php if ($userRole === 'superadmin'): ?>
                    <a href="superadmin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'superadmin_dashboard.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <i class="fas fa-tachometer-alt text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Dashboard</span>
                    </a>
                    <a href="upload_csv.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload_csv.php' ? 'active' : ''; ?>" data-tooltip="Upload CSV">
                        <i class="fas fa-file-upload text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Upload CSV</span>
                    </a>
                    <a href="manage_users.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>" data-tooltip="Manage Users">
                        <i class="fas fa-users text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Manage Users</span>
                    </a>
                <?php elseif ($userRole === 'admin'): ?>
                    <a href="admin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <i class="fas fa-tachometer-alt text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Dashboard</span>
                    </a>
                    <a href="my_profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_profile.php' ? 'active' : ''; ?>" data-tooltip="My Profile">
                        <i class="fas fa-user text-xl w-6"></i>
                        <span class="sidebar-text ml-3">My Profile</span>
                    </a>
                    <a href="my_records.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_records.php' ? 'active' : ''; ?>" data-tooltip="My Records">
                        <i class="fas fa-file-alt text-xl w-6"></i>
                        <span class="sidebar-text ml-3">My Records</span>
                    </a>
                <?php endif; ?>
            </nav>
            
            <!-- Logout -->
            <div class="p-4 border-t border-gray-200">
                <a href="logout.php" class="sidebar-link text-red-600 hover:bg-red-50" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt text-xl w-6"></i>
                    <span class="sidebar-text ml-3">Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden w-full md:w-auto">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-3 sm:px-6">
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-600"><?php echo date('l, F j, Y'); ?></p>
                        <p class="text-xs text-gray-500" id="currentTime"></p>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-6">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="mb-6 bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded-lg">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
