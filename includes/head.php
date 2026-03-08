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

// Initialize database connection
$db = Database::getInstance();
$admin_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;

// Notification counters
$unread_notifications_count = 0;
$unread_leave_notifications_count = 0;
$total_unread_count = 0;
$user_pending_documents_count = 0;
$establishment_pending_count = 0;
$registrar_pending_count = 0;

try {
    if ($admin_user_id) {
        $unread_result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM document_notifications WHERE admin_id = ? AND is_read = 0",
            [$admin_user_id]
        );
        $unread_notifications_count = $unread_result['count'] ?? 0;

        $unread_leave_result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM leave_notifications WHERE admin_id = ? AND is_read = 0",
            [$admin_user_id]
        );
        $unread_leave_notifications_count = $unread_leave_result['count'] ?? 0;
        
        $total_unread_count = $unread_notifications_count + $unread_leave_notifications_count;
        
        $user_pending_result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM document_submissions WHERE admin_id = ? AND approval_status IN ('pending', 'establishment_approved')",
            [$admin_user_id]
        );
        $user_pending_documents_count = $user_pending_result['count'] ?? 0;
        
        $establishment_result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM document_submissions WHERE current_stage = 'establishment' AND approval_status = 'pending'",
            []
        );
        $establishment_pending_count = $establishment_result['count'] ?? 0;
        
        $registrar_result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM document_submissions WHERE current_stage = 'registrar' AND approval_status = 'establishment_approved'",
            []
        );
        $registrar_pending_count = $registrar_result['count'] ?? 0;
    }
} catch (Exception $e) {
    // Silently fail
}

// Fetch profile picture from database for admin users
if ($userRole === 'admin' && isset($_SESSION['staff_id'])) {
    try {
        $userProfile = $db->fetchOne(
            "SELECT profile_picture FROM admin_users WHERE staff_id = ?",
            [$_SESSION['staff_id']]
        );
        if ($userProfile && !empty($userProfile['profile_picture'])) {
            $profilePicture = $userProfile['profile_picture'];
        }
    } catch (Exception $e) {
        // Silently fail, use session profile picture
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Staff Management System</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="assets/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        // Initialize theme from localStorage
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
        
        // Accordion toggle function
        function toggleAccordion(button) {
            const content = button.nextElementSibling;
            const isOpen = content.classList.contains('open');
            
            // Close all accordion items
            document.querySelectorAll('.accordion-content').forEach(item => {
                item.classList.remove('open');
            });
            document.querySelectorAll('.accordion-toggle').forEach(toggle => {
                toggle.classList.remove('active');
            });
            
            // Open clicked accordion if it wasn't open
            if (!isOpen) {
                content.classList.add('open');
                button.classList.add('active');
            }
        }
        
        // Auto-open accordion if current page is in it
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
            const activeLinks = document.querySelectorAll('.accordion-link.active');
            
            activeLinks.forEach(link => {
                const content = link.closest('.accordion-content');
                if (content) {
                    const toggle = content.previousElementSibling;
                    if (toggle && toggle.classList.contains('accordion-toggle')) {
                        content.classList.add('open');
                        toggle.classList.add('active');
                    }
                }
            });
        });
        
        // Confirmation Dialog System
        function showConfirmDialog(title, message, onConfirm, onCancel) {
            const dialog = document.getElementById('confirmDialog');
            const dialogTitle = document.getElementById('dialogTitle');
            const dialogMessage = document.getElementById('dialogMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            
            dialogTitle.textContent = title;
            dialogMessage.textContent = message;
            
            confirmBtn.onclick = function() {
                dialog.classList.add('hidden');
                if (onConfirm) onConfirm();
            };
            
            cancelBtn.onclick = function() {
                dialog.classList.add('hidden');
                if (onCancel) onCancel();
            };
            
            dialog.classList.remove('hidden');
        }
        
        function closeConfirmDialog() {
            document.getElementById('confirmDialog').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-950 transition-colors duration-300 overflow-hidden" style="margin: 0; padding: 0;">
    <!-- Advanced Loading Screen -->
    <div id="loadingScreen" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-glow-card">
                <div class="spinner-premium">
                    <span></span>
                </div>
                <h2 class="loading-text">Staff Management System</h2>
                <p class="loading-subtext">Loading<span class="loading-dots"></span></p>
                <div class="loading-progress">
                    <div class="progress-bar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hide loading screen and show content when page loads
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const mainContent = document.querySelector('.flex.h-screen.overflow-hidden.relative');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                loadingScreen.style.transition = 'opacity 0.5s ease-out';
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                    if (mainContent) mainContent.style.opacity = '1';
                }, 500);
            }
        });
    </script>
    
    <div class="flex h-screen overflow-hidden relative" style="opacity: 0; transition: opacity 0.3s ease-in;">
        <!-- Confirmation Dialog Modal -->
        <div id="confirmDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 id="dialogTitle" class="text-lg font-bold text-gray-800 dark:text-white mb-3">Confirm Action</h3>
                <p id="dialogMessage" class="text-gray-700 dark:text-gray-300 mb-6">Are you sure?</p>
                <div class="flex gap-3 justify-end">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                        Cancel
                    </button>
                    <button id="confirmBtn" class="px-4 py-2 text-white rounded-lg transition font-semibold" style="background-color: #dc2626;" onmouseover="this.style.backgroundColor='#b91c1c'" onmouseout="this.style.backgroundColor='#dc2626'">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Overlay for mobile drawer -->
        <div class="md:hidden fixed inset-0 bg-black bg-opacity-50 hidden" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar expanded bg-white dark:bg-gray-800 shadow-xl flex flex-col md:relative transition-colors duration-300">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="sidebar-text">
                    <h2 class="text-xl font-bold text-blue-600 dark:text-blue-400">FPOG SMS</h2>
                </div>
                <button id="toggleSidebar" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Profile Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col items-center">
                    <!-- Profile Avatar -->
                    <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold mb-3 relative">
                        <?php if ($profilePicture && file_exists($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initials); ?>
                        <?php endif; ?>
                        <!-- Profile Icon (appears on collapsed sidebar) -->
                        <div class="collapsed-text hidden absolute -bottom-1 -right-1 bg-blue-600 rounded-full p-2 text-white text-lg border-2 border-white dark:border-gray-800">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <!-- Expanded View (hidden when collapsed) -->
                    <div class="sidebar-text text-center">
                        <h3 class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($fullname); ?></h3>
                        <div class="text-blue-600 dark:text-blue-400 text-xl mt-2">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <?php if ($userRole === 'superadmin'): ?>
                        <a href="superadmin_profile.php" class="sidebar-text mt-3 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                            <i class="fas fa-user-circle mr-1"></i>Profile
                        </a>
                    <?php elseif ($userRole === 'admin'): ?>
                        <a href="admin_profile.php" class="sidebar-text mt-3 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                            <i class="fas fa-user-circle mr-1"></i>Profile
                        </a>
                    <?php endif; ?>
                    
                    <!-- Collapsed View (shown when collapsed) -->
                    <div class="collapsed-text hidden text-center mt-2">
                        <div class="text-xs font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($initials); ?></div>
                        <div class="text-xs text-gray-600 dark:text-gray-300 capitalize mt-1"><?php echo htmlspecialchars($userRole); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4">
                <?php if ($userRole === 'superadmin'): ?>
                    <a href="superadmin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'superadmin_dashboard.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <i class="fas fa-tachometer-alt text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Dashboard</span>
                    </a>
                    
                    <!-- User Management Accordion -->
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="User Management">
                            <i class="fas fa-users text-xl w-6"></i>
                            <span class="sidebar-text ml-3">User Management</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <a href="upload_csv.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload_csv.php' ? 'active' : ''; ?>" data-tooltip="Upload CSV">
                                <i class="fas fa-file-upload text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Upload CSV</span>
                            </a>
                            <a href="manage_users.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>" data-tooltip="Manage Users">
                                <i class="fas fa-user-friends text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Manage Users</span>
                            </a>
                            <a href="manage_admin_roles.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_admin_roles.php' ? 'active' : ''; ?>" data-tooltip="Manage Roles">
                                <i class="fas fa-user-tag text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Manage Roles</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Memo Management Accordion -->
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Memo System">
                            <i class="fas fa-envelope text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Memo System</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <a href="upload_memo.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload_memo.php' ? 'active' : ''; ?>" data-tooltip="Send Memo">
                                <i class="fas fa-paper-plane text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Send Memo</span>
                            </a>
                            <a href="manage_memos.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_memos.php' ? 'active' : ''; ?>" data-tooltip="Manage Memos">
                                <i class="fas fa-tasks text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Manage Memos</span>
                            </a>
                            <a href="superadmin_memo_history.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'superadmin_memo_history.php' ? 'active' : ''; ?>" data-tooltip="Memo History">
                                <i class="fas fa-history text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Memo History</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Document Submissions Link -->
                    <a href="view_document_submissions.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'view_document_submissions.php' ? 'active' : ''; ?>" data-tooltip="Document Submissions">
                        <i class="fas fa-file-archive text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Document Submissions</span>
                    </a>
                <?php elseif ($userRole === 'admin'): ?>
                    <a href="admin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <i class="fas fa-tachometer-alt text-xl w-6"></i>
                        <span class="sidebar-text ml-3">Dashboard</span>
                    </a>
                    
                    <!-- Memo Management Accordion -->
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Memos">
                            <i class="fas fa-envelope text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Memos</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <a href="upload_memo.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload_memo.php' ? 'active' : ''; ?>" data-tooltip="Send Memo">
                                <i class="fas fa-paper-plane text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Send Memo</span>
                            </a>
                            <a href="view_received_memos.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'view_received_memos.php' ? 'active' : ''; ?>" data-tooltip="Received Memos">
                                <i class="fas fa-inbox text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Received Memos</span>
                            </a>
                            <a href="memo_history.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'memo_history.php' ? 'active' : ''; ?>" data-tooltip="Memo History">
                                <i class="fas fa-history text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Memo History</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Document Management Accordion -->
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Documents">
                            <i class="fas fa-file-archive text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Documents</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <a href="upload_documents.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'upload_documents.php' ? 'active' : ''; ?>" data-tooltip="Document Upload">
                                <i class="fas fa-cloud-upload-alt text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Upload Document</span>
                            </a>
                            <a href="document_status.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'document_status.php' ? 'active' : ''; ?>" data-tooltip="Document Status">
                                <i class="fas fa-bell text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Status & Notifications</span>
                                <div class="ml-auto flex gap-1">
                                    <?php if ($user_pending_documents_count > 0): ?>
                                        <span class="bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full" title="Pending documents"><?php echo $user_pending_documents_count; ?></span>
                                    <?php endif; ?>
                                    <?php if ($unread_notifications_count > 0): ?>
                                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full" title="Unread notifications"><?php echo $unread_notifications_count; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <a href="document_history.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'document_history.php' ? 'active' : ''; ?>" data-tooltip="Document History">
                                <i class="fas fa-history text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Document History</span>
                            </a>
                        </div>
                    </div>
                    <!-- Leave Management Accordion -->
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Leave Management">
                            <i class="fas fa-calendar-alt text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Leave Management</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <a href="apply_leave.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'apply_leave.php' ? 'active' : ''; ?>" data-tooltip="Apply for Leave">
                                <i class="fas fa-paper-plane text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Apply for Leave</span>
                            </a>
                            <a href="leave_history.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'leave_history.php' ? 'active' : ''; ?>" data-tooltip="Leave History">
                                <i class="fas fa-history text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Leave History</span>
                                <?php if ($unread_leave_notifications_count > 0): ?>
                                    <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo $unread_leave_notifications_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="resumption_duty.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'resumption_duty.php' ? 'active' : ''; ?>" data-tooltip="Resumption of Duty">
                                <i class="fas fa-walking text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Resumption of Duty</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Leave Approvals Accordion -->
                <?php
                $hasLeaveApprovalRole = false;
                if ($userRole === 'admin' || $userRole === 'superadmin') {
                    $roles = $db->fetchAll("
                        SELECT role_name FROM admin_roles 
                        WHERE admin_id = ? AND removed_at IS NULL
                    ", [$admin_user_id ?? null]);
                    
                    foreach ($roles as $role) {
                        if (in_array($role['role_name'], ['HOD', 'Dean', 'Academic Dean', 'Establishment', 'Registrar', 'Rector'])) {
                            $hasLeaveApprovalRole = true;
                            break;
                        }
                    }
                }
                ?>
                <?php if ($hasLeaveApprovalRole || $userRole === 'superadmin'): ?>
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Leave Approvals">
                            <i class="fas fa-user-check text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Leave Approvals</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <?php
                            $roles = $db->fetchAll("
                                SELECT role_name FROM admin_roles 
                                WHERE admin_id = ? AND removed_at IS NULL
                            ", [$admin_user_id ?? null]);
                            
                            $hasHOD = false;
                            $hasDean = false;
                            $hasEst = false;
                            $hasFinal = false;
                            foreach ($roles as $role) {
                                if ($role['role_name'] === 'HOD') $hasHOD = true;
                                if (in_array($role['role_name'], ['Dean', 'Academic Dean'])) $hasDean = true;
                                if ($role['role_name'] === 'Establishment') $hasEst = true;
                                if (in_array($role['role_name'], ['Registrar', 'Rector'])) $hasFinal = true;
                            }
                            ?>
                            <?php if ($hasHOD || $userRole === 'superadmin'): ?>
                                <a href="hod_leave_approvals.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'hod_leave_approvals.php' ? 'active' : ''; ?>" data-tooltip="HOD Recommendations">
                                    <i class="fas fa-user-tie text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">HOD Recommendations</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($hasDean || $userRole === 'superadmin'): ?>
                                <a href="dean_leave_approvals.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'dean_leave_approvals.php' ? 'active' : ''; ?>" data-tooltip="Dean Clearances">
                                    <i class="fas fa-user-graduate text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Dean Clearances</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($hasEst || $userRole === 'superadmin'): ?>
                                <a href="establishment_leave_approvals.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'establishment_leave_approvals.php' ? 'active' : ''; ?>" data-tooltip="Establishment Verifications">
                                    <i class="fas fa-id-card text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Est. Verifications</span>
                                </a>
                                <a href="manage_leave_balances.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_leave_balances.php' ? 'active' : ''; ?>" data-tooltip="Manage Leave Balances">
                                    <i class="fas fa-coins text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Manage Balances</span>
                                </a>
                                <a href="manage_holidays.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_holidays.php' ? 'active' : ''; ?>" data-tooltip="Manage Holidays">
                                    <i class="fas fa-calendar-day text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Manage Holidays</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($hasFinal || $userRole === 'superadmin'): ?>
                                <a href="final_leave_approvals.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'final_leave_approvals.php' ? 'active' : ''; ?>" data-tooltip="Final Approvals">
                                    <i class="fas fa-check-double text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Final Approvals</span>
                                </a>
                            <?php endif; ?>
                            <a href="leave_approval_history.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'leave_approval_history.php' ? 'active' : ''; ?>" data-tooltip="Approval History">
                                <i class="fas fa-history text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Approval History</span>
                            </a>
                            <a href="leave_reports.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'leave_reports.php' ? 'active' : ''; ?>" data-tooltip="Leave Reports">
                                <i class="fas fa-chart-pie text-lg w-6"></i>
                                <span class="sidebar-text ml-3">Leave Reports</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Document Approval Accordion -->
                <?php
                $hasApprovalRole = false;
                if ($userRole === 'admin' || $userRole === 'superadmin') {
                    $roles = $db->fetchAll("
                        SELECT role_name FROM admin_roles 
                        WHERE admin_id = ? AND removed_at IS NULL
                    ", [$admin_user_id ?? null]);
                    
                    foreach ($roles as $role) {
                        if (in_array($role['role_name'], ['Establishment', 'Registrar'])) {
                            $hasApprovalRole = true;
                            break;
                        }
                    }
                }
                ?>
                <?php if ($hasApprovalRole || $userRole === 'superadmin'): ?>
                    <div class="accordion-item">
                        <button class="sidebar-link accordion-toggle" onclick="toggleAccordion(this)" data-tooltip="Document Approvals">
                            <i class="fas fa-check-double text-xl w-6"></i>
                            <span class="sidebar-text ml-3">Document Approvals</span>
                            <i class="fas fa-chevron-down text-xs ml-auto accordion-icon"></i>
                        </button>
                        <div class="accordion-content">
                            <?php
                            $roles = $db->fetchAll("
                                SELECT role_name FROM admin_roles 
                                WHERE admin_id = ? AND removed_at IS NULL
                            ", [$admin_user_id ?? null]);
                            
                            $hasEstablishment = false;
                            $hasRegistrar = false;
                            foreach ($roles as $role) {
                                if ($role['role_name'] === 'Establishment') $hasEstablishment = true;
                                if ($role['role_name'] === 'Registrar') $hasRegistrar = true;
                            }
                            ?>
                            <?php if ($hasEstablishment || $userRole === 'superadmin'): ?>
                                <a href="establishment_approve_documents.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'establishment_approve_documents.php' ? 'active' : ''; ?>" data-tooltip="Establishment Approvals">
                                    <i class="fas fa-check-circle text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Establishment Approvals</span>
                                    <?php if ($establishment_pending_count > 0): ?>
                                        <span class="ml-auto bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $establishment_pending_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($hasRegistrar || $userRole === 'superadmin'): ?>
                                <a href="registrar_approve_documents.php" class="sidebar-link accordion-link <?php echo basename($_SERVER['PHP_SELF']) === 'registrar_approve_documents.php' ? 'active' : ''; ?>" data-tooltip="Registrar Approvals">
                                    <i class="fas fa-check-circle text-lg w-6"></i>
                                    <span class="sidebar-text ml-3">Registrar Approvals</span>
                                    <?php if ($registrar_pending_count > 0): ?>
                                        <span class="ml-auto bg-purple-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $registrar_pending_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
            
            <!-- Logout -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <a href="logout.php" class="sidebar-link text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900 dark:hover:bg-opacity-20" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt text-xl w-6"></i>
                    <span class="sidebar-text ml-3">Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden w-full md:w-auto">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-900 shadow-sm h-16 flex items-center justify-between px-3 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="flex items-center gap-4">
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="Toggle dark mode">
                        <i class="fas fa-sun text-yellow-500 dark:hidden"></i>
                        <i class="fas fa-moon text-blue-500 hidden dark:inline"></i>
                    </button>
                    <div class="text-right">
                        <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo date('l, F j, Y'); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" id="currentTime"></p>
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
