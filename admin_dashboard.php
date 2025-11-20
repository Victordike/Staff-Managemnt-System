<?php
require_once 'includes/functions.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
require_once 'includes/head.php';

try {
    $db = Database::getInstance();
    $staffId = $_SESSION['staff_id'];
    
    // Get user profile
    $profile = $db->fetchOne(
        "SELECT * FROM admin_users WHERE staff_id = ?",
        [$staffId]
    );
} catch (Exception $e) {
    error_log($e->getMessage());
    $profile = null;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Welcome Card -->
    <div class="lg:col-span-2 card bg-gradient-to-br from-blue-500 to-indigo-600 text-white">
        <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h2>
        <p class="text-blue-100">Federal Polytechnic of Oil and Gas - Staff Management System</p>
        <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <i class="fas fa-id-badge text-2xl mb-2"></i>
                <p class="text-sm text-blue-100">Staff ID</p>
                <p class="font-bold text-lg"><?php echo htmlspecialchars($staffId); ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <i class="fas fa-briefcase text-2xl mb-2"></i>
                <p class="text-sm text-blue-100">Department</p>
                <p class="font-bold text-lg"><?php echo htmlspecialchars($profile['department'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Info -->
    <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Quick Info
        </h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-600">Position</p>
                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($profile['position'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($profile['official_email'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Phone</p>
                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($profile['phone_number'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Profile Details -->
<?php if ($profile): ?>
<div class="card">
    <h2 class="text-xl font-bold text-gray-800 mb-6">
        <i class="fas fa-user-circle mr-2 text-blue-600"></i>My Profile Details
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Personal Information -->
        <div>
            <h3 class="font-semibold text-gray-700 mb-3 pb-2 border-b">Personal Information</h3>
            <div class="space-y-2">
                <div>
                    <p class="text-xs text-gray-500">Full Name</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['firstname'] . ' ' . $profile['othername'] . ' ' . $profile['surname']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Date of Birth</p>
                    <p class="text-sm font-medium"><?php echo formatDate($profile['date_of_birth']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Gender</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['sex']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Marital Status</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['marital_status']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Employment Information -->
        <div>
            <h3 class="font-semibold text-gray-700 mb-3 pb-2 border-b">Employment Information</h3>
            <div class="space-y-2">
                <div>
                    <p class="text-xs text-gray-500">Department</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['department']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Position</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['position']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Employment Type</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['type_of_employment']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Date of Assumption</p>
                    <p class="text-sm font-medium"><?php echo formatDate($profile['date_of_assumption']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Bank Information -->
        <div>
            <h3 class="font-semibold text-gray-700 mb-3 pb-2 border-b">Bank Information</h3>
            <div class="space-y-2">
                <div>
                    <p class="text-xs text-gray-500">Bank Name</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['bank_name']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Account Name</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['account_name']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Account Number</p>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($profile['account_number']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/foot.php'; ?>
