<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();

// Check if user is Superadmin or Establishment
$isAuthorized = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if ($role['role_name'] === 'Establishment') $isAuthorized = true;
}

if ($userRole === 'superadmin') {
    $isAuthorized = true;
}

if (!$isAuthorized) {
    die('Access denied');
}

// Handle Add Holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $date = $_POST['holiday_date'];
    $name = $_POST['holiday_name'];
    $year = date('Y', strtotime($date));
    
    try {
        $db->query("INSERT INTO holidays (holiday_date, holiday_name, year) VALUES (?, ?, ?)", [$date, $name, $year]);
        setFlashMessage('Holiday added successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Handle Delete Holiday
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $db->query("DELETE FROM holidays WHERE id = ?", [$id]);
        setFlashMessage('Holiday deleted successfully', 'success');
        header('Location: manage_holidays.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

$currentYear = date('Y');
$holidays = $db->fetchAll("SELECT * FROM holidays ORDER BY holiday_date ASC");

$pageTitle = 'Manage Holidays';
require_once 'includes/head.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Public Holidays</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage official holidays to be excluded from leave calculations</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Add Holiday Form -->
    <div class="lg:col-span-1">
        <div class="card shadow-lg">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>Add New Holiday
                </h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Holiday Name</label>
                    <input type="text" name="holiday_name" required placeholder="e.g. Independence Day" class="w-full p-2.5 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Date</label>
                    <input type="date" name="holiday_date" required class="w-full p-2.5 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg shadow-md transition">
                    <i class="fas fa-plus mr-2"></i>Save Holiday
                </button>
            </form>
        </div>
    </div>

    <!-- Holidays List -->
    <div class="lg:col-span-2">
        <div class="card shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Date</th>
                            <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Holiday Name</th>
                            <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-center">Year</th>
                            <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php if (empty($holidays)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No holidays found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($holidays as $h): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="px-6 py-4 font-bold text-gray-800 dark:text-white">
                                        <?php echo date('d M, Y', strtotime($h['holiday_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($h['holiday_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded"><?php echo $h['year']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="?delete=<?php echo $h['id']; ?>" onclick="return confirm('Are you sure you want to delete this holiday?')" class="text-red-600 hover:text-red-800 transition">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/foot.php'; ?>
