<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();

// Check if user is Establishment or Superadmin
$isEst = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if ($role['role_name'] === 'Establishment') $isEst = true;
}

if (!$isEst && $userRole !== 'superadmin') {
    die('Access denied');
}

$currentYear = date('Y');
$today = date('Y-m-d');

// Filters
$yearFilter = $_GET['year'] ?? $currentYear;
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['leave_type'] ?? '';

// Build Query
$query = "
    SELECT la.*, lt.name as leave_type, au.firstname, au.surname, au.staff_id, au.department
    FROM leave_applications la 
    JOIN leave_types lt ON la.leave_type_id = lt.id 
    JOIN admin_users au ON la.admin_id = au.id
    WHERE YEAR(la.start_date) = ?
";
$params = [$yearFilter];

if ($statusFilter) {
    $query .= " AND la.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $query .= " AND la.leave_type_id = ?";
    $params[] = $typeFilter;
}

$query .= " ORDER BY la.created_at DESC";
$applications = $db->fetchAll($query, $params);

// Stats
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'approved' AND start_date <= ? AND end_date >= ? THEN 1 ELSE 0 END) as on_leave
    FROM leave_applications
    WHERE YEAR(start_date) = ?
", [$today, $today, $yearFilter]);

// Leave Types for filter
$leaveTypes = $db->fetchAll("SELECT * FROM leave_types");

$pageTitle = 'Leave Reports';
require_once 'includes/head.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Leave Statistics & Reports</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Comprehensive overview of leave usage across the institution</p>
    </div>
    <div>
        <button onclick="exportToCSV()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition flex items-center">
            <i class="fas fa-file-csv mr-2 text-xl"></i> Export to CSV
        </button>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="card p-6 border-t-4 border-blue-500 shadow-sm">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Total Requests</p>
        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $stats['total'] ?: 0; ?></h3>
    </div>
    <div class="card p-6 border-t-4 border-green-500 shadow-sm">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Approved</p>
        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $stats['approved'] ?: 0; ?></h3>
    </div>
    <div class="card p-6 border-t-4 border-yellow-500 shadow-sm">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Currently On Leave</p>
        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $stats['on_leave'] ?: 0; ?></h3>
    </div>
    <div class="card p-6 border-t-4 border-orange-500 shadow-sm">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pending</p>
        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $stats['pending'] ?: 0; ?></h3>
    </div>
    <div class="card p-6 border-t-4 border-red-500 shadow-sm">
        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Rejected</p>
        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $stats['rejected'] ?: 0; ?></h3>
    </div>
</div>

<!-- Filters -->
<div class="card p-6 mb-8 shadow-sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="space-y-2">
            <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Year</label>
            <select name="year" class="w-full p-2.5 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <?php for($i = $currentYear; $i >= $currentYear-2; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo $yearFilter == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="space-y-2">
            <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Leave Type</label>
            <select name="leave_type" class="w-full p-2.5 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <option value="">All Types</option>
                <?php foreach ($leaveTypes as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php echo $typeFilter == $type['id'] ? 'selected' : ''; ?>><?php echo $type['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="space-y-2">
            <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Status</label>
            <select name="status" class="w-full p-2.5 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $statusFilter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Applications Table -->
<div class="card overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table id="reportsTable" class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Staff</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Type</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Period</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Days</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Applied On</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">No leave applications found matching filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></p>
                                <p class="text-[10px] text-gray-500"><?php echo $app['staff_id']; ?> | <?php echo $app['department']; ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($app['leave_type']); ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                <?php echo date('d M, Y', strtotime($app['start_date'])); ?> to <?php echo date('d M, Y', strtotime($app['end_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-gray-800 dark:text-white">
                                <?php echo $app['duration']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                                        'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                        'completed' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'hod_recommended' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                        'dean_cleared' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'establishment_verified' => 'bg-cyan-100 text-cyan-800 border-cyan-200'
                                    ];
                                    $class = $statusClasses[$app['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>
                                <span class="<?php echo $class; ?> px-2 py-1 rounded text-[10px] font-bold border uppercase tracking-tighter">
                                    <?php echo str_replace('_', ' ', $app['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">
                                <?php echo date('d M Y, H:i', strtotime($app['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById("reportsTable");
    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            // Clean text content
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
            row.push('"' + text + '"');
        }
        csv.push(row.join(","));
    }
    
    const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "leave_report_<?php echo $yearFilter; ?>.csv");
    document.body.appendChild(link);
    link.click();
}
</script>

<?php require_once 'includes/foot.php'; ?>
