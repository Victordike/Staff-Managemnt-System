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
    <div class="lg:col-span-2 card bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 text-white shadow-xl">
        <div class="flex flex-col md:flex-row items-start justify-between">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h2>
                <p class="text-indigo-100 text-lg">Federal Polytechnic of Oil and Gas - Staff Management System</p>
                <p class="text-indigo-200 text-sm mt-3"><i class="fas fa-calendar mr-2"></i><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="text-5xl opacity-20 mt-4 md:mt-0"><i class="fas fa-user-circle"></i></div>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-lg p-4 transition cursor-pointer border border-white border-opacity-20">
                <i class="fas fa-id-badge text-3xl mb-2"></i>
                <p class="text-sm text-indigo-100 font-semibold">Staff ID</p>
                <p class="font-bold text-2xl mt-1"><?php echo htmlspecialchars($staffId); ?></p>
            </div>
            <div class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-lg p-4 transition cursor-pointer border border-white border-opacity-20">
                <i class="fas fa-briefcase text-3xl mb-2"></i>
                <p class="text-sm text-indigo-100 font-semibold">Department</p>
                <p class="font-bold text-xl mt-1"><?php echo htmlspecialchars($profile['department'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Info Card -->
    <div class="card bg-gradient-to-br from-green-50 to-blue-50 shadow-lg">
        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-3 border-b-2 border-green-500">
            <i class="fas fa-lightning-bolt text-green-600 mr-2"></i>Quick Info
        </h3>
        <div class="space-y-4">
            <div class="bg-white rounded-lg p-3 hover:shadow-md transition">
                <p class="text-xs text-gray-500 font-semibold uppercase">Position</p>
                <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($profile['position'] ?? 'N/A'); ?></p>
            </div>
            <div class="bg-white rounded-lg p-3 hover:shadow-md transition">
                <p class="text-xs text-gray-500 font-semibold uppercase">Email</p>
                <p class="font-semibold text-gray-800 text-sm truncate"><?php echo htmlspecialchars($profile['official_email'] ?? 'N/A'); ?></p>
            </div>
            <div class="bg-white rounded-lg p-3 hover:shadow-md transition">
                <p class="text-xs text-gray-500 font-semibold uppercase">Phone</p>
                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($profile['phone_number'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg hover:shadow-xl transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-semibold">Employment Status</p>
                <p class="text-3xl font-bold mt-2"><?php echo htmlspecialchars($profile['type_of_employment'] ?? 'N/A'); ?></p>
            </div>
            <i class="fas fa-briefcase text-5xl text-blue-200 opacity-50"></i>
        </div>
    </div>
    
    <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white shadow-lg hover:shadow-xl transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-semibold">Date Registered</p>
                <p class="text-2xl font-bold mt-2"><?php echo formatDate($profile['created_at']); ?></p>
            </div>
            <i class="fas fa-calendar-check text-5xl text-green-200 opacity-50"></i>
        </div>
    </div>
    
    <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg hover:shadow-xl transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-semibold">Account Status</p>
                <p class="text-3xl font-bold mt-2"><span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">Active</span></p>
            </div>
            <i class="fas fa-user-check text-5xl text-purple-200 opacity-50"></i>
        </div>
    </div>
</div>

<!-- Profile Details -->
<?php if ($profile): ?>
<div class="card shadow-xl">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-blue-500">
        <i class="fas fa-address-card mr-2 text-blue-600"></i>Complete Profile Details
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

<!-- Animated Information Ticker -->
<div class="ticker-container mt-8">
    <div class="ticker-content">
        <span class="ticker-item"><i class="fas fa-bell mr-2 text-yellow-500"></i>Welcome to FPOG Staff Management System</span>
        <span class="ticker-item"><i class="fas fa-shield-alt mr-2 text-green-500"></i>Your personal information is securely stored and protected</span>
        <span class="ticker-item"><i class="fas fa-lock mr-2 text-blue-500"></i>All communications are encrypted with SSL/TLS</span>
        <span class="ticker-item"><i class="fas fa-check-circle mr-2 text-green-500"></i>Your profile is complete and verified</span>
        <span class="ticker-item"><i class="fas fa-star mr-2 text-yellow-500"></i>Keep your information updated regularly</span>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
