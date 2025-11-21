<?php
require_once 'includes/functions.php';
requireAdmin();

$pageTitle = 'My Profile';
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

<!-- Profile Header -->
<div class="card bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white mb-8 shadow-xl">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        <div class="w-32 h-32 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-5xl font-bold">
            <?php echo htmlspecialchars(getInitials($_SESSION['firstname'], $_SESSION['lastname'])); ?>
        </div>
        <div class="flex-1">
            <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></h1>
            <p class="text-indigo-100 text-lg mb-3"><?php echo htmlspecialchars($profile['position'] ?? 'N/A'); ?></p>
            <div class="flex gap-6 flex-wrap">
                <div>
                    <p class="text-indigo-200 text-sm">Staff ID</p>
                    <p class="text-xl font-bold"><?php echo htmlspecialchars($staffId); ?></p>
                </div>
                <div>
                    <p class="text-indigo-200 text-sm">Department</p>
                    <p class="text-xl font-bold"><?php echo htmlspecialchars($profile['department'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-indigo-200 text-sm">Email</p>
                    <p class="text-lg font-bold"><?php echo htmlspecialchars($profile['official_email'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($profile): ?>
<!-- Complete Profile Details -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-blue-500">
        <i class="fas fa-address-card mr-2 text-blue-600"></i>Personal Information
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Full Name</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['firstname'] . ' ' . $profile['othername'] . ' ' . $profile['surname']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Date of Birth</p>
            <p class="text-lg font-bold text-gray-800"><?php echo formatDate($profile['date_of_birth']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Gender</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['sex']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Marital Status</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['marital_status']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-rose-50 p-4 rounded-lg border border-red-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Phone Number</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['phone_number'] ?? 'N/A'); ?></p>
        </div>
        <div class="bg-gradient-to-br from-cyan-50 to-blue-50 p-4 rounded-lg border border-cyan-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Personal Email</p>
            <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($profile['personal_email'] ?? 'N/A'); ?></p>
        </div>
    </div>
</div>

<!-- Employment Information -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-green-500">
        <i class="fas fa-briefcase mr-2 text-green-600"></i>Employment Information
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Department</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['department']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Position</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['position']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Employment Type</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['type_of_employment']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Date of Assumption</p>
            <p class="text-lg font-bold text-gray-800"><?php echo formatDate($profile['date_of_assumption']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-rose-50 p-4 rounded-lg border border-red-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Official Email</p>
            <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($profile['official_email']); ?></p>
        </div>
    </div>
</div>

<!-- Bank Information -->
<div class="card shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-purple-500">
        <i class="fas fa-university mr-2 text-purple-600"></i>Bank & Financial Information
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Bank Name</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['bank_name']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Account Name</p>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($profile['account_name']); ?></p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Account Number</p>
            <p class="text-lg font-bold text-gray-800 font-mono"><?php echo htmlspecialchars($profile['account_number']); ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Animated Information Ticker -->
<div class="ticker-container">
    <div class="ticker-content">
        <span class="ticker-item"><i class="fas fa-bell mr-2 text-yellow-500"></i>Welcome to your profile page</span>
        <span class="ticker-item"><i class="fas fa-shield-alt mr-2 text-green-500"></i>Your information is securely protected</span>
        <span class="ticker-item"><i class="fas fa-lock mr-2 text-blue-500"></i>All data is encrypted with SSL/TLS</span>
        <span class="ticker-item"><i class="fas fa-info-circle mr-2 text-indigo-500"></i>Keep your information updated</span>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
